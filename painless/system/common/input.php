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

// TODO: Credit Codeigniter in copyright notice

class PainlessInput
{

    protected $useXssClean = FALSE;
    protected $xssHash = '';
    protected $ipAddress = FALSE;
    protected $userAgent = FALSE;
    protected $allowGetArray = FALSE;
    protected $sanitizer = NULL;
    private $isInit = FALSE;

    //---------------------------------------------------------------
    /**
     * Constructor
     *
     * Sets whether to globally enable the XSS processing
     * and whether to allow the $_GET array
     *
     * @access	public
     */
    public function __construct( )
    {

    }

    public function __destruct( )
    {
        // clean up all the arrays
        unset( $_POST );
        unset( $_GET );
        unset( $_FILES );
    }

    public function init( )
    {
        if ( !$this->isInit )
        {
            $this->sanitizer = Painless::get( 'system/common/sanitizer' );
            $config = Painless::get( 'system/common/config' );

            $this->useXssClean = ( $config->get( 'system.input.useXssFiltering' ) === TRUE ) ? TRUE : FALSE;
            $this->sanitizeGlobals( );

            $this->isInit = TRUE;
        }
    }

    /**
     * Fetch an item from the GET array
     *
     * @param string $index an key to search for in the array (none will return the entire array)
     * @param boolean $xssClean set to TRUE to clean up XSS
     * @return value/array return a value or the entire array
     */
    public function get( $index = '', $xssClean = FALSE )
    {
        $this->init( );
        return $this->fetchFromArray( $_GET, $index, $xssClean );
    }

    /**
     * Fetch an item from the POST array
     *
     * @param string $index an key to search for in the array (none will return the entire array)
     * @param boolean $xssClean set to TRUE to clean up XSS
     * @return value/array return a value or the entire array
     */
    public function post( $index = '', $xssClean = FALSE )
    {
        $this->init( );
        return $this->fetchFromArray( $_POST, $index, $xssClean );
    }

    /**
     * Fetch an item from the FILE array
     *
     * @param string $index an key to search for in the array (none will return the entire array)
     * @param boolean $xssClean set to TRUE to clean up XSS
     * @return value/array return a value or the entire array
     */
    public function file( $index = '', $xssClean = FALSE )
    {
        $this->init( );
        return $this->fetchFromArray( $_FILES, $index, $xssClean );
    }

    /**
     * Fetch an item from the REQUEST array
     *
     * @param string $index an key to search for in the array (none will return the entire array)
     * @param boolean $xssClean set to TRUE to clean up XSS
     * @return value/array return a value or the entire array
     */
    public function request( $index = '', $xssClean = FALSE )
    {
        $this->init( );
        return $this->fetchFromArray( $_REQUEST, $index, $xssClean );
    }

    /**
     * Fetch an item from the SERVER array
     *
     * @param string $index an key to search for in the array (none will return the entire array)
     * @param boolean $xssClean set to TRUE to clean up XSS
     * @return value/array return a value or the entire array
     */
    public function server( $index = '', $xss_clean = FALSE )
    {
        $this->init( );
        return $this->fetchFromArray( $_SERVER, $index, $xss_clean );
    }

    /**
     * Fetch the IP Address
     *
     * @access	public
     * @return	string
     */
    public function ipAddress( )
    {
        $this->init( );

        if ( $this->ipAddress !== FALSE )
        {
            return $this->ipAddress;
        }

        if ( $this->server( 'REMOTE_ADDR' ) && $this->server( 'HTTP_CLIENT_IP' ) )
        {
            $this->ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif ( $this->server( 'REMOTE_ADDR' ) )
        {
            $this->ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        elseif ( $this->server( 'HTTP_CLIENT_IP' ) )
        {
            $this->ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif ( $this->server( 'HTTP_X_FORWARDED_FOR' ) )
        {
            $this->ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if ( $this->ipAddress === FALSE )
        {
            $this->ipAddress = '0.0.0.0';
            return $this->ipAddress;
        }

        if ( strstr( $this->ipAddress, ',' ) )
        {
            $x = explode( ',', $this->ipAddress );
            $this->ipAddress = end( $x );
        }

        if ( !$this->sanitizer->validIp( $this->ipAddress ) )
        {
            $this->ipAddress = '0.0.0.0';
        }

        return $this->ipAddress;
    }

    /**
     * User Agent
     *
     * @access	public
     * @return	string
     */
    public function userAgent( )
    {
        $this->init( );

        if ( $this->userAgent !== FALSE )
        {
            return $this->userAgent;
        }

        $this->userAgent = (!isset( $_SERVER['HTTP_USER_AGENT'] ) ) ? FALSE : $_SERVER['HTTP_USER_AGENT'];

        return $this->userAgent;
    }

    /**
     * Sanitize Globals
     *
     * This function does the following:
     *
     * Unsets $_GET data (if query strings are not enabled)
     *
     * Standardizes newline characters to \n
     *
     * @access	protected
     * @return	void
     */
    protected function sanitizeGlobals( )
    {
        // Would kind of be "wrong" to unset any of these GLOBALS
        $protected = array( '_SERVER', '_GET', '_POST', '_FILES',
            '_REQUEST', '_SESSION', '_ENV', 'GLOBALS',
            'HTTP_RAW_POST_DATA', 'system_folder', 'application_folder',
            'BM', 'EXT', 'CFG', 'URI', 'RTR', 'OUT', 'IN' );

        // Clean $_GET Data
        $_GET = $this->cleanInputData( $_GET );

        // Clean $_POST Data
        $_POST = $this->cleanInputData( $_POST );

        // Clean $_COOKIE Data
        $_COOKIE = $this->cleanInputData( $_COOKIE );
    }

    /**
     * Clean Input Data
     *
     * This is a helper function. It escapes data and
     * standardizes newline characters to \n
     *
     * @access	protected
     * @param	string
     * @return	string
     */
    protected function cleanInputData( $str )
    {
        if ( is_array( $str ) )
        {
            $new_array = array( );
            foreach ( $str as $key => $val )
            {
                $new_array[$this->cleanInputKeys( $key )] = $this->cleanInputData( $val );
            }
            return $new_array;
        }

        // We strip slashes if magic quotes is on to keep things consistent
        if ( get_magic_quotes_gpc ( ) )
        {
            $str = stripslashes( $str );
        }

        // Should we filter the input data?
        if ( $this->useXssClean === TRUE )
        {
            $str = $this->sanitizer->xssClean( $str );
        }

        // Standardize newlines
        if ( strpos( $str, "\r" ) !== FALSE )
        {
            $str = str_replace( array( "\r\n", "\r" ), "\n", $str );
        }

        return $str;
    }

    /**
     * Clean Keys
     *
     * This is a helper function. To prevent malicious users
     * from trying to exploit keys we make sure that keys are
     * only named with alpha-numeric text and a few other items.
     *
     * @access	protected
     * @param	string
     * @return	string
     */
    protected function cleanInputKeys( $str )
    {
        if ( !preg_match( "/^[a-z0-9:_\/-]+$/i", $str ) )
        {
            trigger_error( sprintf( 'Invalid key: %s', $str ), E_USER_ERROR );
        }

        return $str;
    }

    /**
     * Fetch from array
     *
     * This is a helper function to retrieve values from global arrays
     *
     * @access	protected
     * @param	array
     * @param	string
     * @param	bool
     * @return	string
     */
    protected function fetchFromArray( $array, $index = '', $xssClean = FALSE )
    {
        // return an entire array if no index is specified
        if ( empty( $index ) )
        {
            if ( $xssClean === TRUE )
            {
                foreach ( $array as $index => $value )
                {
                    $array[$index] = $this->sanitizer->xssClean( $value );
                }
            }
            return $array;
        }

        if ( !isset( $array[$index] ) )
        {
            return NULL;
        }

        if ( $xssClean === TRUE )
        {
            return $this->sanitizer->xssClean( $array[$index] );
        }

        return $array[$index];
    }

}
