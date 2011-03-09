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

// @PAINFUL

namespace Painless\System\Common;

class PainlessCookie
{
    protected $salt = 'This is workfloo';
    protected $expiration = 0;
    protected $path = '/';
    protected $domain = NULL;
    protected $secure = FALSE;
    protected $httpOnly = FALSE;

    /**
     * READ-ONLY! Used to determine if the user has cookies enabled
     * @var boolean TRUE if cookies are enabled for the user, FALSE if otherwise
     */
    protected $hasCookies = FALSE;

    public function __construct( )
    {
        if ( ! isset( $_COOKIE["PHPSESSID"] ) && ! isset( $_GET["PHPSESSID"] ) )
        {
            // handle cookie not found here
        }
        elseif ( ! isset( $_COOKIE["PHPSESSID"] ) && isset( $_GET["PHPSESSID"] ) )
        {
            $this->hasCookies = TRUE;
        }
        else
        {
            $this->hasCookies = FALSE;
        }

    }

    public function hasCookies( )
    {
        return $this->hasCookies;
    }

    public function get( $key, $default = NULL )
    {
        
    }

    public function set( $key, $value, $expire = NULL )
    {
        
    }

    public function delete( $name )
    {
        
    }

    protected function generateSalt( )
    {
        
    }
}