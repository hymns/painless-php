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

defined( 'EXT' ) or define( 'EXT', '.php' );

define( 'DEV', 'dev' );
define( 'LIVE', 'live' );
define( 'TEST', 'test' );
define( 'STAGE', 'stage' );

/**
 * The class Painless is built as a concept of a singleton registry, where it is
 * a static alias to PainlessCore and provides the facilities to load components
 * easily. One of the other functions that Painless can handle is dependency
 * injection - either globally, or by targetting specific components.
 *
 * @author  Ruben Tan Long Zheng <ruben@rendervault.com>
 * @copyright   Copyright (c) 2009, Rendervault Solutions
 */
class Painless
{
    /* Loading parameters */
    const LP_DEF        = 1;
    const LP_SEEK       = 2;
    const LP_CACHE      = 4;
    const LP_RET        = 8;
    const LP_ALL        = 15;       // LP_DEF | LP_CACHE | LP_RET | LP_SEEK
    const LP_LOAD_NEW   = 9;        // LP_DEF | LP_RET
    
    /* Logging parameters */
    const LOG_INFO      = 'info';
    const LOG_ERROR     = 'error';
    const LOG_WARNING   = 'warning';
    
    public static $apps = array( );
    
    public $profile     = '';
    public $core        = NULL;
    public $loader      = NULL;

    /**
     * Checks what profile is this package using (DEV, LIVE, etc)
     * @param string $type  a profile type to check against
     * @return boolean      TRUE if it matches, FALSE if otherwise 
     */
    public static function isProfile( $type )
    {
        if ( static::get( )->profile === $type )
            return TRUE;

        return FALSE;
    }
    
    public static function profile( $val = '' )
    {
        $painless = static::get( );
        if ( ! empty( $val ) )
            return $painless->profile;
        
        $painless->profile = $val;
        return $painless;
    }

    /**
     * A shorthand to log messages
     * @static
     * @param string $message   the message to log
     * @return void 
     */
    public static function log( $type, $message )
    {   
        $log = Painless::load( 'com://system/common/log' );
        $log->set( $type, $message );
    }
    
    /**
     * 
     * @param string $uri
     * @param mixed $payload
     * @param array $attachments
     * @return mixed 
     */
    public static function request( $uri, $payload = FALSE, $attachments = array( ) )
    {
        return static::$core->request( $uri, $payload, $attachments );
    }
    
    public static function get( $appName )
    {
        if ( empty( static::$apps[$appName] ) )
            static::$apps[$appName] = new static;
        
        return static::$apps[$appName];
    }
    
    public static function execute( $appName, $appPath, $useExtLoader = TRUE )
    {
        $painless = static::get( $appName );
        
        // Set default values for non-critical env consts if none are set
        defined( 'ERROR_REPORTING' ) or define( 'ERROR_REPORTING', E_ALL | E_STRICT );
        defined( 'NS' ) or define( 'NS', '/' );
        ( ! empty( static::$PROFILE ) ) or self::$PROFILE = DEV;
        
        // Append a backslash to $implPath if none is provided
        if ( end( $appPath ) !== '/' ) $appPath .= '/';
        
        // See if we have a loader or not
        $loaderPath = __DIR__ . '/system/common/loader' . EXT;
        if ( TRUE === $useExtLoader )
        {
            // Check if there's an extended loader in the application's directory
            $extLoaderPath = $appPath . 'system/common/loader' . EXT;
            if ( file_exists( $extLoaderPath ) )
                $loaderPath = $extLoaderPath;
        }
        
        require $loaderPath;
        $cn = '\\' . dash_to_pascal( $appName ) . '\\System\\Common\\Loader';
        $loader = new $cn;
        
        // Set the application's paths
        $loader->env( 'app_name', $appPath );
        $loader->env( 'app_path', $appPath );
        $loader->env( 'core', __DIR__ . '/' );
        
        // Save the loader here
        $painless->loader = $loader;
        $painless->core = $loader->load( 'system/common/core' );
        
        // Include the fearsome Beholder (Event Dispatcher)
        require_once __DIR__ . '/beholder.php';
        Beholder::init( );
        
        return $painless->core->execute( );
    }
}

/* -----------------------------------------------------------------------------
 * Bunch of useful functions
 * ---------------------------------------------------------------------------- */

function array_get( $array, $key, $defaultReturn = FALSE )
{
    return isset( $array[$key] ) ? $array[$key] : $defaultReturn;
}

function dash_to_pascal( $string )
{
    return preg_replace( '/(^|-)(.)/e', "strtoupper('\\2')", $string );
}

function dash_to_camel( $string )
{
    $string = preg_replace( '/(^|-)(.)/e', "strtoupper('\\2')", $string );
    $string[0] = strtolower( $string[0] );
    return $string;
}

function dash_to_underscore( $string )
{
    return str_replace( '-', '_', $string );
}

function underscore_to_pascal( $string )
{
    return preg_replace( '/(^|_)(.)/e', "strtoupper('\\2')", $string );
}

function underscore_to_camel( $string )
{
    $string = preg_replace( '/(^|_)(.)/e', "strtoupper('\\2')", $string );
    $string[0] = strtolower( $string[0] );
    return $string;
}

function camel_to_underscore( $string )
{
    return strtolower( preg_replace( "/([a-z])([A-Z]{1})/", "$1_$2", $string ) );
}

function camel_to_dash( $string )
{
    return strtolower( preg_replace( "/([a-z])([A-Z]{1})/", "$1\-$2", $string ) );
}

// http://php.dzone.com/news/generate-search-engine-friendl
function generate_seo_link( $string )
{
    $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
    $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
    return  strtolower(
                preg_replace(
                    array( '/[^a-zA-Z0-9 -]/', '/[ -]+/', '/^-|-$/' ),
                    array( '', '-', '' ),
                    str_replace( $a,  $b, $string )
                )
            );
}

/* -----------------------------------------------------------------------------
 * List of triggers
 * ---------------------------------------------------------------------------- */

/*
 * PainlessRender:
 *  - [nu] render.pre
 *  - [no] render.post( output )
 * 
 * PainlessMysql:
 *  - [nu] mysql.execute.pre
 *  - [no] mysql.execute.post( data )
 * 
 */