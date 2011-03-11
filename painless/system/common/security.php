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

namespace Painless\System\Common;

class Security
{
    const POLICY_WHITELIST = 'whitelist';
    const POLICY_BLACKLIST = 'blacklist';

    protected $policy = 'blacklist';
    protected $acl = array( );

    public $identity = NULL;

    protected function loadAcl( )
    {
        if ( empty( $this->acl ) )
            $this->acl = \Painless::load( 'system/common/config' )->get( 'acl.*' );
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

        $user = \Painless::load( 'system/common/session' )->get( 'user' );
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

        \Painless::load( 'system/common/session' )->set( 'user', $user );

        $this->identity = $user;
    }

    /*
     * Logout the user.
     *
     * @return nothing
     */

    public function logout( )
    {
        \Painless::load( 'system/common/session' )->destroy( );

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
        if ( class_exists( 'COM', FALSE ) )
        {
            try
            {
                $com = new COM( 'CAPICOM.Utilities.1' );
                $entropy = base64_decode( $com->GetRandom( $size, 0 ) );
                return $entropy;
            }
            catch ( Exception $e )
            {
                throw new SecurityException( $e );
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