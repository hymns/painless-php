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
namespace Painless\System\String;

// TODO: PROGRESS = 60% 

class Convention
{
    /* Constants for different conventions */
    const CAMEL             = 1;            // firstWordSmallCaps
    const PASCAL            = 2;            // FirstWordUpperCaps
    const DASH              = 3;            // dash-delimited-convention
    const UNDERSCORE        = 4;            // underscore_delimited_convention
    const PHP_NAMESPACE     = 5;            // Php\Namespace\Using\PascalConvention
    
    //--------------------------------------------------------------------------
    /**
     * Converts a string of various conventions to a camel-style convention (e.g.
     * foo-bar to fooBar)
     * @param string $str   the string to be converted
     * @param int $origin   the type of convention that $str uses
     * @return string       the converted string
     */
    public  function camel( $str, $origin )
    {
        switch( $origin )
        {
            case self::PASCAL :
                $str[0] = strtoupper( $str[0] );
                return $str;
            
            case self::DASH :
                return strtolower( preg_replace( "/([a-z])([A-Z]{1})/", "$1\-$2", $str ) );
            
            case self::UNDERSCORE :
                return strtolower( preg_replace( "/([a-z])([A-Z]{1})/", "$1_$2", $str ) );
            
            // Convention conversions that are not supported
            case self::CAMEL :
            case self::PHP_NAMESPACE :
            default :
                return $str;
        }
    }
    
    //--------------------------------------------------------------------------
    /**
     * Converts a string of various conventions to a pascal-style convention (e.g.
     * foo-bar to FooBar).
     * @param string $str   the string to be converted
     * @param int $origin   the type of convention that $str uses
     * @return string       the converted string
     */
    public function pascal( $str, $origin )
    {
        switch( $origin )
        {
            case self::CAMEL :
                $str[0] = strtolower( $str[0] );
                return $str;
            
            case self::DASH :
                return strtolower( preg_replace( "/([a-z])([A-Z]{1})/", "$1\-$2", $str ) );
            
            case self::UNDERSCORE :
                return strtolower( preg_replace( "/([a-z])([A-Z]{1})/", "$1_$2", $str ) );
            
            // Convention conversions that are not supported
            case self::PASCAL :
            case self::PHP_NAMESPACE :
            default :
                return $str;
        }
    }
    
    //--------------------------------------------------------------------------
    /**
     * Converts a string of various conventions to a dash-delimited convention
     * in all lower case (e.g. FooBar to foo-bar)
     * @param string $str   the string to be converted
     * @param int $origin   the type of convention that $str uses
     * @return string       the converted string
     */
    public function dash( $str, $origin )
    {
        switch( $origin )
        {
            case self::CAMEL :
                $str[0] = strtolower( $str[0] );
                return $str;
            
            case self::PASCAL :
                return preg_replace( '/(^|-)(.)/e', "strtoupper('\\2')", $str );
            
            case self::UNDERSCORE :
                return strtolower( str_replace( '_', '-', $str ) );
                
            case self::PHP_NAMESPACE :
                // TODO: Use preg_replace for this
                $sp = explode( '/', $str );
                foreach( $sp as $i => $s )
                {
                    $sp[$i] = $this->pascal( $s, self::PASCAL );
                }
                return implode( '\\', $sp );
            
            // Convention conversions that are not supported
            case self::DASH :
            default :
                return $str;
        }
    }
    
    //--------------------------------------------------------------------------
    /**
     * Converts a string of various conventions to an underscore-delimited
     * convention in all lower case (e.g. FooBar to foo_bar)
     * @param string $str   the string to be converted
     * @param int $origin   the type of convention that $str uses
     * @return string       the converted string
     */
    public function underscore( $str, $origin )
    {
        
    }
    
    //--------------------------------------------------------------------------
    /**
     * Converts a string of various conventions to a PHP namespace (e.g. foo-bar
     * to Foo\Bar)
     * @param string $str   the string to be converted
     * @param int $origin   the type of convention that $str uses
     * @return string       the converted string
     */
    public function phpNamespace( $str, $origin )
    {
        
    }
}