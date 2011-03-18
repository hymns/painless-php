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
    /* Core version */
    const VERSION           = '1.0';

    /* Base load parameters */
    const LP_DEF_BASE       = 1;                // load the definition for the core component
    const LP_DEF_APP        = 2;                // load the definition for the extended component
    const LP_CACHE_BASE     = 4;                // instantiate the core component and cache it
    const LP_CACHE_APP      = 8;                // instantiate the extended component and cache it
    const LP_RET_BASE       = 16;               // returns the core component. If this cannot be done, it'll return a NULL
    const LP_RET_APP        = 32;               // returns the extended component. If this cannot be done, it'll return the core component instead
    const LP_SKIP_CACHE     = 64;               // skip the cache lookup

    /* Shorthand load parameters */
    const LP_ALL            = 63;               // short for LP_DEF_CORE | LP_DEF_EXT | LP_CACHE_CORE | LP_CACHE_EXT | LP_RET_CORE | LP_RET_EXT
    const LP_LOAD_NEW       = 127;              // short for LP_DEF_CORE | LP_DEF_EXT | LP_CACHE_CORE | LP_CACHE_EXT | LP_RET_CORE | LP_RET_EXT | LP_SKIP_CACHE_LOAD
    const LP_DEF_ONLY       = 2;                // short for LP_DEF_CORE | LP_DEF_EXT
    const LP_APP_ONLY       = 42;               // short for LP_DEF_EXT | LP_CACHE_EXT | LP_RET_EXT
    const LP_BASE_ONLY      = 21;               // short for LP_DEF_CORE | LP_CACHE_CORE | LP_RET_CORE

    /* Profile names */
    const DEV               = 'dev';
    const LIVE              = 'live';
    const STAGE             = 'stage';

    /* Environment variable names */
    const APP_NAME          = 'app_name';       // the canonical name of the app
    const APP_PATH          = 'app_path';       // the path to the app
    const APP_URL           = 'app_url';        // the URL of the app (if it's a HTTP app) (no params)
    const PAGE_URL          = 'page_url';       // the URL of the page
    const RES_PATH          = 'res_path';       // the path to the app's resources
    const CORE_PATH         = 'core_path';      // the path to this file
    const CLI_ARGV          = 'cli_argv';       // the arguments that are passed in from the command line
    const PROFILE           = 'profile';        // the current deployment profile (DEV, LIVE or STAGE)

    /* Entry points */
    const RUN_HTTP          = 1;                // run this app as a HTTP app
    const RUN_CLI           = 2;                // run this app as a CLI app
    const RUN_APP           = 3;                // run this app with an internal call (one app calling anohter)
    const RUN_INTERNAL      = 4;                // run this request as an intra-app controller to controller call

    private static $app     = array( );
    private static $curr    = '';

    //--------------------------------------------------------------------------
    /**
     * Tests the current profile of an app
     * @param string $profileToMatch    a profile to match with
     * @return boolean                  TRUE if the profile matches, FALSE if otherwise
     */
    public static function isProfile( $profileToMatch )
    {
        $core = static::app( );
        return ( $core->env( \Painless::PROFILE ) === $profileToMatch );
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
            return static::$app[static::$curr];
        elseif ( NULL === $core && isset( static::$app[$name] ) )
            return static::$app[$name];
        elseif ( NULL === $core && ! isset( static::$app[$name] ) )
            return NULL;
        
        static::$app[$name] = $core;
    }

    //--------------------------------------------------------------------------
    /**
     * Shorthand to load a component from the current app
     * @param string $component the name of the component to be loaded
     * @param int $opt          the loading parameters
     * @return object           the loaded component
     */
    public static function load( $component, $opt = \Painless::LP_ALL )
    {
        return static::app( )->com( 'system/common/loader' )->load( $component, $opt );
    }

    //--------------------------------------------------------------------------
    /**
     * Manufactures a component using the passed in parameters
     * @param string $component the name of the component to be loaded
     * @param array $opt        the loading parameters
     * @return object           the manufactured component
     */
    public static function manufacture( )
    {
        // Get the list of arguments passed in
        $args = func_get_args( );

        // Don't continue if no component specified
        if ( empty( $args ) || ! is_string( $args[0] ) )
            throw new \ErrorException( 'You need to specify a component type to manufacture by setting it as the first argument of manufacture( ).' );

        // Extract the first segment of $args as the component type
        $component = array_shift( $args );
        
        // All clear. Create the factory and manufacture away!
        $factory = \Painless::load( 'system/common/factory' );

        // Don't continue if the component is not suppoted!
        if ( ! method_exists( $factory, $component ) )
            throw new \ErrorException( 'Trying to manufacture a non-supported component [' . $component . ']' );

        // Create the component
        $com = call_user_func_array( array( $factory, $component ), $args );

        // Handle errors if necessary
        if ( FALSE === $com )
            throw new \ErrorException( 'Unable to manufacture the component requested [' . $request . ']' );

        return $com;
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
        ( $appPath[strlen( $appPath ) - 1] !== '/' ) and $appPath .= '/';
        
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
        \Painless::app( $appName , $core );

        // Set the registered app as the active one
        static::$curr = $appName;

        // Register an autoloader
        spl_autoload_register( '\Painless\System\Common\Loader::autoload' );

        return $core;
    }

    //--------------------------------------------------------------------------
    /**
     * Executes a request command
     * @param string $entry         the entry type of the app
     * @param string $cmd           the command to run
     */
    public static function execute( $entry, $cmd = '' )
    {
        return static::app( )->execute( $entry, $cmd );
    }
}

/* -----------------------------------------------------------------------------
 * Bunch of useful functions
 * -----------------------------------------------------------------------------
 */

function array_get( $array, $key, $defaultReturn = FALSE )
{
    return isset( $array[$key] ) ? $array[$key] : $defaultReturn;
}

function is_empty( $var )
{
    return empty( $var );
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
    return str_replace( '\\', '/', strtolower( preg_replace( "/([a-z])([A-Z]{1})/", "$1-$2", $string ) ) );
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