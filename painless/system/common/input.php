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
 * PainlessInput is inspired by CodeIgniter's (http://www.codeigniter.com) input
 * class, using the same access structure. Here's Ellis Lab's copyright notice
 * over "usage" of their code in a modified way:

    CodeIgniter License Agreement

    Copyright (c) 2008 - 2011, EllisLab, Inc.
    All rights reserved.

    This license is a legal agreement between you and EllisLab Inc. for the use of
    CodeIgniter Software (the "Software"). By obtaining the Software you agree to
    comply with the terms and conditions of this license.

    Permitted Use

    You are permitted to use, copy, modify, and distribute the Software and its
    documentation, with or without modification, for any purpose, provided that the
    following conditions are met:

    * A copy of this license agreement must be included with the distribution.
    * Redistributions of source code must retain the above copyright notice in all
      source code files.
    * Redistributions in binary form must reproduce the above copyright notice in
      the documentation and/or other materials provided with the distribution.
    * Any files that have been modified must carry notices stating the nature of
      the change and the names of those who changed them.
    * Products derived from the Software must include an acknowledgment that they
      are derived from CodeIgniter in their documentation and/or other materials
      provided with the distribution.
    * Products derived from the Software may not be called "CodeIgniter", nor may
      "CodeIgniter" appear in their name, without prior written permission from
      EllisLab, Inc.

    Indemnity

    You agree to indemnify and hold harmless the authors of the Software and any
    contributors for any direct, indirect, incidental, or consequential third-party
    claims, actions or suits, as well as any related expenses, liabilities, damages,
    settlements or fees arising from your use or misuse of the Software, or a
    violation of any terms of this license.

    Disclaimer of Warranty

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESSED OR
    IMPLIED, INCLUDING, BUT NOT LIMITED TO, WARRANTIES OF QUALITY, PERFORMANCE,
    NON-INFRINGEMENT, MERCHANTABILITY, OR FITNESS FOR A PARTICULAR PURPOSE.

    Limitations of Liability

    YOU ASSUME ALL RISK ASSOCIATED WITH THE INSTALLATION AND USE OF THE SOFTWARE.
    IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS OF THE SOFTWARE BE LIABLE FOR
    CLAIMS, DAMAGES OR OTHER LIABILITY ARISING FROM, OUT OF, OR IN CONNECTION WITH
    THE SOFTWARE. LICENSE HOLDERS ARE SOLELY RESPONSIBLE FOR DETERMINING THE
    APPROPRIATENESS OF USE AND ASSUME ALL RISKS ASSOCIATED WITH ITS USE, INCLUDING
    BUT NOT LIMITED TO THE RISKS OF PROGRAM ERRORS, DAMAGE TO EQUIPMENT, LOSS OF
    DATA OR SOFTWARE PROGRAMS, OR UNAVAILABILITY OR INTERRUPTION OF OPERATIONS.
 */

namespace Painless\System\Common;

class Input
{

    protected $ipAddress = FALSE;
    protected $userAgent = FALSE;
    private $isInit = FALSE;

    public function __destruct( )
    {
        // clean up all the arrays
        unset( $_POST );
        unset( $_GET );
        unset( $_FILES );
    }

    public function init( )
    {
        if ( Beholder::notifyUntil( 'input.init', $this ) )
            $this->isInit = TRUE;
    }

    /**
     * Fetch an item from the GET array
     *
     * @param string $index     an key to search for in the array (none will return the entire array)
     * @param boolean $default  the default value to return
     * @return value/array      return a value or the entire array
     */
    public function get( $index = '', $default = FALSE )
    {
        if ( ! $this->isInit ) $this->init( );
        return $this->fetchFromArray( $_GET, $index, $default );
    }

    /**
     * Fetch an item from the POST array
     *
     * @param string $index     an key to search for in the array (none will return the entire array)
     * @param boolean $default  the default value to return
     * @return value/array      return a value or the entire array
     */
    public function post( $index = '', $default = FALSE )
    {
        if ( ! $this->isInit ) $this->init( );
        return $this->fetchFromArray( $_POST, $index, $default );
    }

    /**
     * Fetch an item from the FILE array
     *
     * @param string $index     an key to search for in the array (none will return the entire array)
     * @param boolean $default  the default value to return
     * @return value/array      return a value or the entire array
     */
    public function file( $index = '', $default = FALSE )
    {
        if ( ! $this->isInit ) $this->init( );
        return $this->fetchFromArray( $_FILES, $index, $default );
    }
	
	/**
     * Fetch an item from the COOKIE array
     *
     * @param string $index     an key to search for in the array (none will return the entire array)
     * @param boolean $default  the default value to return
     * @return value/array      return a value or the entire array
     */
    public function cookie( $index = '', $default = FALSE )
    {
        if ( ! $this->isInit ) $this->init( );
        return $this->fetchFromArray( $_COOKIE, $index, $default );
    }

    /**
     * Fetch an item from the REQUEST array
     *
     * @param string $index     an key to search for in the array (none will return the entire array)
     * @param boolean $default  the default value to return
     * @return value/array      return a value or the entire array
     */
    public function request( $index = '', $default = FALSE )
    {
        if ( ! $this->isInit ) $this->init( );
        return $this->fetchFromArray( $_REQUEST, $index, $default );
    }

    /**
     * Fetch an item from the SERVER array
     *
     * @param string $index     an key to search for in the array (none will return the entire array)
     * @param boolean $default  the default value to return
     * @return value/array      return a value or the entire array
     */
    public function server( $index = '', $default = FALSE )
    {
        if ( ! $this->isInit ) $this->init( );
        return $this->fetchFromArray( $_SERVER, $index, $default );
    }

    /**
     * Fetch the IP Address
     *
     * @access	public
     * @return	string
     */
    public function ipAddress( )
    {
        if ( ! $this->isInit ) $this->init( );

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
    protected function fetchFromArray( $array, $index = '', $default = FALSE )
    {
        $return = FALSE;

        // return an entire array if no index is specified
        if ( empty( $index ) )
            $return = $array;
        else
            $return = array_get( $array, $index, $default );

        return $return;
    }

}


