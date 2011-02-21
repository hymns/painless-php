<?php

/**
 * The requirement for PainlessSession is that it should be able to automatically
 * create itself, without the need for the user to explicitly call start or stop.
 *
 * Cases to handle:
 * 1) if cookies are disabled, store the session ID in the URI itself
 * 2) if cookies are enabled
 * 3) if HTTPS is enabled
 * 4) full-site HTTPS
 * 5) selecting HTTPS URI
 * 5) AUTOSTART
 */
class PainlessSession
{
    protected $useNamespace = TRUE;         // uses namespaces to store session data (TRUE by default)
    protected $regenIdPerReq = FALSE;        // regenerates session ID on every request (TRUE by default)
    
    protected $started = FALSE;
    protected $namespace = IMPL_NAME;

    protected $data = array( );

    /**
     * starts the session
     * @return boolean return TRUE if session is craeted successfully
     */
    public function start( )
    {
        if ( ! $this->started )
        {
            // get the session parameters
            // TODO: Work on this

            // start the session only once
            session_start( );

            // begins a new session by regenerating a new session ID
            if ( $this->regenIdPerReq )
                $this->regenerateSessionId( );

             // remember to save a reference to the session data here
            if ( $this->useNamespace && ! empty( $this->namespace ) )
            {
                // initialize the session namespace
                if ( ! isset( $_SESSION[$this->namespace] ) )
                {
                    $_SESSION[$this->namespace] = array( );
                }

                $this->data = & $_SESSION[$this->namespace];
            }
            else
            {
                $this->data = & $_SESSION;
            }

            // register the session's close function
            register_shutdown_function( array( $this, 'close' ) );

            $this->started = TRUE;
        }

        return TRUE;
    }

    // If got existing session, regenerate the session Id, then start a new session.
    // If no session is created yet, it creates a new session.
    // NOTE: All data WILL be retained.
    public function regenerateSessionId( )
    {
        // get dependencies.
        $security = Painless::get( 'system/common/security' );
        $config = Painless::get( 'system/common/config' );

        // get hash algorithm to use for session key generation.
        $hashAlgo = $config->get( 'session.id.hash_algo' );
	
        // Save the existing session's data.
        $existingSessionData = $_SESSION;
	session_write_close( );
	
	$newSessionId = $security->getUniqueHash( $hashAlgo );
        session_id( $newSessionId ); // unable to regenerate session ID. Need to re-test this soon.
	
	session_start( );
	
	// Save the existing session data back to current session.
        $_SESSION = $existingSessionData;

        return $newSessionId;
    }

    public function get( $key, $isFlashData = FALSE )
    {
        // lazy initialization
        $this->start( );
        if ( isset( $this->data[$key] ) )
        {
                $data = $this->data[$key];
                if ( $isFlashData )
                {
                        $this->delete( $key );
                }

                return $data;
        }

        return NULL;
    }

    public function set( $key, $value )
    {
        // lazy initialization
        $this->start( );
	
        $this->data[$key] = $value;
    }

    public function delete( $key = '' )
    {
        // lazy initialization
        $this->start( );

        if ( $key !== '' )
        {
            unset( $this->data[$key] );
        }
        else
        {
            session_unset( );
        }
    }

    public function destroy( )
    {
        // destroy the session
        session_destroy( );

        // regenerate the session ID so that the session is cleaned up
        if ( $this->regenIdPerReq )
        {
                $this->regenerateSessionId( );
        }

        if ( $this->useNamespace )
        {
            unset( $_SESSION[$this->namespace] );
        }
        else
        {
            unset( $_SESSION );
        }

        // make sure the session is gone
        $status = session_id( );
    }

    public function close( )
    {
        session_write_close( );
        return TRUE;
    }
}