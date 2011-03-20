<?php
/**
 * Painless PHP - the painless path to development
 *
 * Copyright (c) 2011, Tan Long Zheng (soggie)
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  * Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  * Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *  * Neither the name of Rendervault Solutions nor the names of its
 *    contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package     Painless PHP
 * @author      Tan Long Zheng (soggie) <ruben@rendervault.com>
 * @copyright   2011 Tan Long Zheng (soggie) <ruben@rendervault.com>
 * @license     BSD 3 Clause (New BSD)
 * @link        http://painless-php.com
 */

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

namespace Painless\System\Common;

class Session
{
    protected $useNamespace = TRUE;         // uses namespaces to store session data (TRUE by default)
    protected $regenIdPerReq = FALSE;        // regenerates session ID on every request (TRUE by default)

    protected $started = FALSE;
    protected $namespace = '';

    protected $data = array( );

    //--------------------------------------------------------------------------
    public function __construct( )
    {
        // Initialize the namespace
        $this->namespace = \Painless::app( )->env( \Painless::APP_NAME );
    }

    //--------------------------------------------------------------------------
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

    //--------------------------------------------------------------------------
    // If got existing session, regenerate the session Id, then start a new session.
    // If no session is created yet, it creates a new session.
    // NOTE: All data WILL be retained.
    public function regenerateSessionId( )
    {
        // get dependencies.
        $security = \Painless::load( 'system/common/security' );
        $config = \Painless::load( 'system/common/config' );

        // get hash algorithm to use for session key generation.
        $hashAlgo = $config->get( 'session.id.hash_algo' );
	
        // Save the existing session's data.
        $existingSessionData = $_SESSION;
	session_write_close( );
	
	$newSessionId = $security->uniqueHash( $hashAlgo );
        session_id( $newSessionId ); // unable to regenerate session ID. Need to re-test this soon.
	
	session_start( );
	
	// Save the existing session data back to current session.
        $_SESSION = $existingSessionData;

        return $newSessionId;
    }

    //--------------------------------------------------------------------------
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

    //--------------------------------------------------------------------------
    public function set( $key, $value )
    {
        // lazy initialization
        $this->start( );
	
        $this->data[$key] = $value;
    }

    //--------------------------------------------------------------------------
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

    //--------------------------------------------------------------------------
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

    //--------------------------------------------------------------------------
    public function close( )
    {
        session_write_close( );
        return TRUE;
    }
}