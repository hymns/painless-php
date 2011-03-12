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

defined( 'DEV' ) or define( 'DEV', 'dev' );
defined( 'LIVE' ) or define( 'LIVE', 'live' );
defined( 'TEST' ) or define( 'TEST', 'test' );
defined( 'STAGE' ) or define( 'STAGE', 'stage' );

defined( 'LP_DEF_CORE' ) or define( 'LP_DEF_CORE', 1 );                 // load the definition for the core component
defined( 'LP_DEF_EXT' ) or define( 'LP_DEF_EXT', 2 );                   // load the definition for the extended component
defined( 'LP_CACHE_CORE' ) or define( 'LP_CACHE_CORE', 4 );             // instantiate the core component and cache it
defined( 'LP_CACHE_EXT' ) or define( 'LP_CACHE_EXT', 8 );               // instantiate the extended component and cache it
defined( 'LP_RET_CORE' ) or define( 'LP_RET_CORE', 16 );                // returns the core component. If this cannot be done, it'll return a NULL
defined( 'LP_RET_EXT' ) or define( 'LP_RET_EXT', 32 );                  // returns the extended component. If this cannot be done, it'll return the core component instead
defined( 'LP_SKIP_CACHE_LOAD' ) or define( 'LP_SKIP_CACHE_LOAD', 64 );  // skip the cache lookup inside the loader

defined( 'LP_ALL' ) or define( 'LP_ALL', 63 );                          // short for LP_DEF_CORE | LP_DEF_EXT | LP_CACHE_CORE | LP_CACHE_EXT | LP_RET_CORE | LP_RET_EXT
defined( 'LP_LOAD_NEW' ) or define( 'LP_LOAD_NEW', 127 );               // short for LP_DEF_CORE | LP_DEF_EXT | LP_CACHE_CORE | LP_CACHE_EXT | LP_RET_CORE | LP_RET_EXT | LP_SKIP_CACHE_LOAD
defined( 'LP_DEF_ONLY' ) or define( 'LP_DEF_ONLY', 3 );                 // short for LP_DEF_CORE | LP_DEF_EXT
defined( 'LP_EXT_ONLY' ) or define( 'LP_EXT_ONLY', 42 );                // short for LP_DEF_EXT | LP_CACHE_EXT | LP_RET_EXT
defined( 'LP_CORE_ONLY' ) or define( 'LP_CORE_ONLY', 21 );              // short for LP_DEF_CORE | LP_CACHE_CORE | LP_RET_CORE

defined( 'APP_NAME' ) or define( 'APP_NAME',  'app_name' );
defined( 'APP_PATH' ) or define( 'APP_PATH',  'app_path' );
defined( 'RES_PATH' ) or define( 'RES_PATH', 'res_path' );
defined( 'CORE_PATH' ) or define( 'CORE_PATH', 'core_path' );
defined( 'PROFILE' ) or define( 'PROFILE', 'profile' );

/**
 * The core Painless class acts as a service locator primarily. It's built to
 * accomodate multiple apps running at the same time, and each app will get their
 * own instance of Core. This means that it is possible to run multiple apps out
 * of one single codebase.
 * 
 * To make a call to another app:
 *  Painless::request( 'GET app://[app-name]' )->execute( );
 *      > 200 OK
 *  Painless::request( 'GET app://[app-name]/[module]/[workflow]/[params]' )->execute( );
 *  or
 *  Painless::request( 'POST app://[app-name]/[module]/[workflow]/[params]' )->payload( '{ "id":"resource-id","data":"foobar"}' )->execute( );
 *
 * @author  Ruben Tan Long Zheng <ruben@rendervault.com>
 * @copyright   Copyright (c) 2009, Rendervault Solutions
 */
class Painless
{   
    private static $app  = array( );
    private static $curr = '';

    //--------------------------------------------------------------------------
    /**
     * Tests the current profile of an app
     * @param string $profileToMatch    a profile to match with
     * @return boolean                  TRUE if the profile matches, FALSE if otherwise
     */
    public static function isProfile( $profileToMatch )
    {
        $core = static::app( );
        return ( $core->env( PROFILE ) === $profileToMatch );
    }

    //--------------------------------------------------------------------------
    /**
     * Sets or gets a Core instance associated with an app
     * @param string $name  the name of the app. If none provided, the current
     *                      app will be used
     * @param object $core  the Core object that is to be associatd with the app
     * @return Core         returns an instance of Core
     */
    public static function app( $name = '', $core = NULL )
    {    
        if ( NULL === $core && isset( static::$app[static::$curr] ) && empty( $name ) )
        {
            return static::$app[static::$curr];
        }
        elseif ( NULL === $core && isset( static::$app[$name] ) )
        {
            return static::$app[$name];
        }
        elseif ( NULL === $core && ! isset( static::$app[$name] ) )
        {
            return NULL;
        }
        
        static::$app[$name] = $core;
    }

    //--------------------------------------------------------------------------
    /**
     * Shorthand to load a component from the current app
     * @param string $component the name of the component to be loaded
     * @param int $opt          the loading parameters
     * @return object           the loaded component
     */
    public static function load( $component, $opt = LP_ALL )
    {
        return static::app( )->com( 'system/common/loader' )->load( $component, $opt );
    }

    //--------------------------------------------------------------------------
    /**
     * Initializes an application
     * @param string $appName       the name of the application (dash-delimited)
     * @param string $appPath       the path of the application (dash-delimited)
     * @param boolean $useExtLoader set to TRUE to have the loader check for the
     *                              existence of an extended loader inside the
     *                              app's extensions, or FALSE to save time and
     *                              cycles
     */
    public static function initApp( $appName, $appPath, $useExtLoader = TRUE )
    {
        // Append a backslash to $implPath if none is provided
        ( $appPath[count( $appPath ) - 1] !== '/' ) or $appPath .= '/';
        
        // Instantiate the Core. Here's the thing - both Core (which contains
        // instances of components, environment variables, etc) and Loader (which
        // handles loading of components) can be extended by the App, and thus
        // we will need to do some creative loading here.
        //
        // First, check if there's an extended version of a loader inside the 
        // app's extensions. If there is (and $useExtLoader is set to TRUE),
        // instantiate that and use it to load the Core. Then save the loader
        // into Core.
        $loaderPath = __DIR__ . '/system/common/loader' . EXT;
        require_once $loaderPath;
        $core = \Painless\System\Common\Loader::init( $appName, $appPath, __DIR__ . '/', $useExtLoader );
        
        // Register the app
        Painless::app( $appName , $core );

        // Set the registered app as the active one
        static::$curr = $appName;

        // Register an autoloader
        spl_autoload_register( '\Painless\System\Common\Loader::autoload' );

        return $core;
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
    return ucwords( $string );
}

function dash_to_underscore( $string )
{
    return str_replace( '-', '_', $string );
}

function dash_to_namespace( $string )
{
    // TODO: Use preg_replace for this
    $sp = explode( '/', $string );
    foreach( $sp as $i => $s )
    {
        $sp[$i] = dash_to_pascal( $s );
    }
    return implode( '\\', $sp );
}

function namespace_to_dash( $string )
{
    return str_replace( '\\', '/', strtolower( preg_replace( "/([a-z])([A-Z]{1})/", "$1\-$2", $string ) ) );
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