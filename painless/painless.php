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

define( 'CORE_VERSION', '1.0' );

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
    public static $core = NULL;
    public static $loader = NULL;

    /**
     * CORE_PATH - the path to this file (painless.php)
     * IMPL_PATH - the path to the implementor
     * IMPL_NAME - the name of the implementor
     * @var string  env paths and names
     */
    public static $CORE_PATH    = '';
    public static $IMPL_PATH    = '';
    public static $IMPL_NAME    = '';
    public static $PROFILE      = '';

    /**
     * The original command that invoked this HTTP Request/REST Call/CLI Process
     * @var string  this will stay empty until the first call to $router builds it
     */
    public static $origin = '';

    /**
     * Checks what profile is this package using (DEV, LIVE, etc)
     * @param string $type  a profile type to check against
     * @return boolean      TRUE if it matches, FALSE if otherwise 
     */
    public static function isProfile( $type )
    {
        if ( self::$PROFILE === $type )
            return TRUE;

        return FALSE;
    }
    
    /**
     * Gets data from a namespaced stream:
     *  http://     - uses the HTTP client to get a HTTP resource
     *  rest://     - uses the REST client to get a REST resource
     *  package://  - uses an internal client to get a resource from another package
     *  workflow:// - uses an internal client to get a resource from a workflow in the same package
     * 
     * The syntax goes like this: [method] [protocol]://[namespace/uri]
     * 
     * For example:
     *  GET http://api.google.com/foo/bar   - makes a HTTP call to http://api.google.com/foo/bar
     *  PUT package://flight-plan/id/123    - creates a new flight plan with the id 123
     * 
     * @static
     * @param string $uri       the URI to send a request to
     * @param mixed $data       the data to attach to the request
     * @param PainlessResponse  a response object
     */
    public static function request( $uri, $data )
    {
        return;
    }

    /**
     * A shorthand to log messages
     * @static
     * @param string $message   the message to log
     * @return void 
     */
    public static function log( $message )
    {
        if ( ! isset( self::$loader ) ) return FALSE;
        
        $log = self::$loader->get( 'system/common/log' );
        $log->info( $message );
    }

    /**
     * Bootstraps this service locator and initializes the engine. Always call this
     * function first before attempting to run any services or components from
     * Painless.
     * @static
     * @author	Ruben Tan Long Zheng <ruben@rendervault.com>
     * @copyright   Copyright (c) 2009, Rendervault Solutions
     * @return	object	the component that is requested
     */
    public static function bootstrap( $implName, $implPath, $loader = NULL )
    {
        // Make sure all env consts are set        
        if ( empty( $implPath ) )
            throw new ErrorException( 'Implementor\'s path is not defined', 2 );
        
        if ( empty( $implName ) )
            throw new ErrorException( 'Implementor\'s name is not defined', 3 );

        // Set default values for non-critical env consts if none are set
        defined( 'ERROR_REPORTING' ) or define( 'ERROR_REPORTING', E_ALL | E_STRICT );
        defined( 'NSTOK' ) or define( 'NSTOK', '/' );
        ( ! empty( self::$PROFILE ) ) or self::$PROFILE = DEV;

        // Set the system paths
        self::$CORE_PATH = dirname( __FILE__ ) . '/';
        self::$IMPL_PATH = $implPath;
        self::$IMPL_NAME = $implName;

        // Instantitate a version of the loader first if none provided. Usually,
        // to improve performance, if the implementor decides to use their own
        // version of the loader, it would be advisable to perform the initialization
        // in index.php and pass in the loader through the parameter $loader
        // rather than leaving it as a NULL
        if ( NULL === $loader )
        {
            require_once self::$CORE_PATH . 'system/common/loader' . EXT;
            $loader = new PainlessLoader;

            // Replace itself with a proper loader
            $loader = $loader->get( 'system/common/loader' );
        }
        
        self::$loader = $loader;
        self::$core = $loader->get( 'system/common/core' );
        
        // Include the fearsome Beholder
        require_once self::$CORE_PATH . 'beholder.php';
        Beholder::init( );

        return self::$core;
    }

    /**
     * Service locator function to load a component.
     *
     * @static
     * @author  Ruben Tan Long Zheng <ruben@rendervault.com>
     * @copyright   Copyright (c) 2009, Rendervault Solutions
     * @param	string	$namespace	the namespace of the component to load from
     * @param	array	$options	a list of load options like LP_DEF_ONLY, LP_EXT_ONLY, etc
     * @return	object	the component that is requested
     */
    public static function get( $namespace, $options = LP_ALL )
    {
        // offload the loading back to PainlessCore
        return self::$loader->get( $namespace, $options );
    }

    /**
     * This executes an operation, which most of the time would be a workflow
     * referenced by a URI namespace. In the future this should be expanded to
     * be able to execute shell commands, cascading workflows, conditional
     * workflows, and so forth.
     *
     * @static
     * @author  Ruben Tan Long Zheng <ruben@rendervault.com>
     * @copyright   Copyright (c) 2009, Rendervault Solutions
     * @param   string    $op    the operation to run. In most use cases, this is a URI
     * @return  mixed   the result of the operation that is executed
     */
    public static function exec( $op = '' )
    {
        return self::$core->exec( $op );
    }

    /**
     * Autoloads a class in the system
     * @param string $cn    the class name to load
     */
    public static function autoload( $cn )
    {
        self::$loader->autoload( $cn );
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
    return str_replace( ' ', '', ucwords( str_replace( CNTOK, ' ', $string ) ) );
}

function dash_to_camel( $string )
{
    $string = str_replace( ' ', '', ucwords( str_replace( CNTOK, ' ', $string ) ) );
    $string[0] = strtolower( $string[0] );
    return $string;
}

function dash_to_underscore( $string )
{
    return str_replace( '-', '_', $string );
}

function underscore_to_pascal( $string )
{}

function underscore_to_camel( $string )
{
    if ( FALSE !== strpos( $string, '_' ) )
    {
        $arr = explode( '_', $string );
        $count = count( $arr );
        $string = '';
        for( $i = 0; $i < $count; $i++ )
        {
            if ( $i !== 0 ) $arr[$i] = ucwords( $arr[$i] );
            $string .= $arr[$i];
        }
    }

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