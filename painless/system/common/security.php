<?php
class PainlessSecurity
{
    const POLICY_WHITELIST = 'whitelist';
    const POLICY_BLACKLIST = 'blacklist';

    protected $policy = 'blacklist';
    protected $acl = array( );

    public $identity = NULL;

    protected function loadAcl( )
    {
        if ( empty( $this->acl ) )
            $this->acl = Painless::get( 'system/common/config' )->get( 'acl.*' );
    }

    /*
     * Check if user is allowed to execute a particular
     * module.workflow.method
     *
     * @param string $namespace     {module}.{workflow}.{method}
     * @param string|array $matchRoles     the role to match with
     * @return boolean
     */

    public function isAllowed( $namespace, $matchRoles = '' )
    {
        $this->loadAcl( );
        
        $roles = array( );

        if ( '' === $matchRoles )
        {
            // get the user identity object
            $user = $this->getIdentity( );
            // if user is not logged in
            if ( NULL === $user )
                $roles = array('public');
            else
                $roles = array_keys( $user['roles'] );
        }
        else
        {
            if ( ! is_array( $matchRoles ) )
                $matchRoles = array( $matchRoles );
            
            $roles = array_keys( $matchRoles );
        }

        // get a list of "roles" allowed by $namespace.
        $associatedRoles = array_get( $this->acl, 'acl.' . $namespace, array( ) );
        if ( !empty( $associatedRoles ) )
        {
            $associatedRoles = explode( ',', $associatedRoles );

            // get the intersect between the associated roles (from the ACL) and
            // the current user's roles. If there is a match, the result's array
            // size should be more than 0
            $diff = count( array_intersect( $associatedRoles, $roles ) );

            // in a blacklist policy, all resources are off limits unless the acl
            // explicitly allows it. Vice versa for whitelists.
            if ( ( $diff > 0 && $this->policy === self::POLICY_BLACKLIST ) || ( $diff === 0 && $this->policy === self::POLICY_WHITELIST ) )
                return TRUE;
        }

        return FALSE;
    }

    /*
     * Check if user is logged in
     *
     * @return boolean
     */

    public function isLoggedIn( )
    {
        $user = $this->getIdentity( );
        return ( $user != NULL );
    }

    /*
     * Retrieves information about the logged in user.
     *
     * @return object
     */

    public function getIdentity( )
    {
        if ( ! empty( $this->identity ) )
            return $this->identity;

        $user = Painless::get( 'system/common/session' )->get( 'user' );
        return $user;
    }

    /**
     * A shorthand to retrieve the user ID
     *
     * @return int the user's ID
     */
    public function getIdentityId( $key = 'id' )
    {
        $user = $this->getIdentity( );
        return array_get( $user, $key, 0 );
    }

    /*
     * Logs the user in. (after checking everything)
     *
     * @return nothing
     */

    public function login( $user )
    {
        // remove the salt & password for safety purposes
        unset( $user['password'] );
        unset( $user['salt'] );

        Painless::get( 'system/common/session' )->set( 'user', $user );

        $this->identity = $user;
    }

    /*
     * Logout the user.
     *
     * @return nothing
     */

    public function logout( )
    {
        Painless::get( 'system/common/session' )->destroy( );

        $this->identity = NULL;
    }

    /*
     * Tries a bunch of methods to get entropy in order
     * of preference and returns as soon as it has something
     *
     * @param int $size     length of the random string.
     * @return string
     */

    public function generateEntropy( $size = 23 )
    {
        // use mcrypt with urandom if we're on 5.3+
        if ( version_compare( PHP_VERSION, '5.3.0', '>=' ) )
            return mcrypt_create_iv( $size, MCRYPT_DEV_URANDOM );

        // otherwise try ssl (beware - it may slow down your app by a few milliseconds)
        if ( function_exists( 'openssl_random_pseudo_bytes' ) )
        {
            $entropy = openssl_random_pseudo_bytes( $size, $strong );

            // skip ssl since it wasn't using the strong algo
            if ( $strong )
                return $entropy;
        }

        // try to read from the unix RNG
        if ( is_readable( '/dev/urandom' ) && ( $handle = fopen( '/dev/urandom', 'rb' ) ) )
        {
            $entropy = fread( $handle, $size );
            fclose( $handle );

            return $entropy;
        }

        // Warning !
        // from here on, the entropy is considered weak
        // so you may want to consider just throwing
        // an exception to realize that your code is running
        // in an insecure way
        // try to read from the windows RNG
        if ( class_exists( 'COM' ) )
        {
            try
            {
                $com = new COM( 'CAPICOM.Utilities.1' );
                $entropy = base64_decode( $com->GetRandom( $size, 0 ) );
                return $entropy;
            }
            catch ( Exception $e )
            {
                throw new PainlessSecurityException( $e );
            }
        }

        // last solution.. barely better than nothing
        return substr( uniqid( mt_rand( ), true ), $size );
    }

    /*
     * Grabs entropy and hashes it to normalize the output
     *
     * @param string $algo hash algorithm to use, defaults to whirlpool
     * @return string
     */

    public function getUniqueHash( $algo = 'whirlpool' )
    {
        $entropy = $this->generateEntropy( );
        return hash( $algo, $entropy );
    }

    /**
     * generate a password from a clear text with a salt value
     *
     * @param string $password the password to be generated
     * @param string $salt the salt to be generated
     * @return string an SHA512 generated 256-digit hexadecimal hash
     */
    public function hashPassword( $password, $salt )
    {
        return hash( 'sha512', $salt . $password );
    }

    /**
     * creates a salt hash using a date string
     * 
     * @param string $dateStr a date string
     * @return string a md5 salt hash
     */
    public function generateSalt( )
    {
        return substr( $this->getUniqueHash( ), 0, 50 );
    }
}

class PainlessSecurityException extends Exception { }