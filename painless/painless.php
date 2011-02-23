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

define( 'PL_VERSION', '1.0' );

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
     * -------------------------------------------------------------------------
     * PainlessException codes:
     *
     * 1 - APP_PATH is not defined
     * 2 - IMPL_PATH is not defined
     * 3 - IMPL_NAME is not defined
     * 4 - PHP version is less than 5.1.2
     * -------------------------------------------------------------------------
     */
    public static function log( $message )
    {
        if ( ! isset( self::$loader ) ) return FALSE;
        
        $log = self::$loader->get( 'system/common/log' );
        $log->info( $message );
    }

    /**
     * Loads a package to be used
     * @static
     * @author  Ruben Tan Long Zheng <ruben@rendervault.com>
     * @param string $name  the dash delimited name of the package to load
     * @param string $path  (optional) the absolute path of where the package is
     */
    public static function package( $name, $path = '' )
    {
        // Convert the dash delimited $name into a pascal case
        $cn = dash_to_pascal( $name );

        // No point including the file if the class already exists
        if ( ! class_exists( $cn, FALSE ) )
        {

            // If no path is provided, or the file specified by the $path does not
            // exists, use the default convention instead
            if ( empty( $path ) || ! file_exists( $path ) )
            {
                $path = IMPL_PATH . '../' . $name . '/' . $name . '.php';
            }

            require_once $path;

            if ( ! class_exists( $cn, FALSE ) ) throw new ErrorException( "$cn not defined inside [$path]" );
        }

        // Bootstrap the package itself
        $cn::bootstrap( );
    }

    /**
     * Bootstraps this service locator and initializes the engine. Always call this
     * function first before attempting to run any services or components from
     * Painless.
     *
     * @static
     * @author	Ruben Tan Long Zheng <ruben@rendervault.com>
     * @copyright   Copyright (c) 2009, Rendervault Solutions
     * @return	object	the component that is requested
     */
    public static function bootstrap( $loader = NULL )
    {
        // Make sure all env consts are set
        if ( ! defined( 'APP_PATH' ) )
            throw new ErrorException( 'APP_PATH is not defined', 1 );
        if ( ! defined( 'IMPL_PATH' ) )
            throw new ErrorException( 'IMPL_PATH is not defined', 2 );
        if ( ! defined( 'IMPL_NAME' ) )
            throw new ErrorException( 'IMPL_NAME is not defined', 3 );

        // Set default values for non-critical env consts if none are set
        defined( 'ERROR_REPORTING' ) or define( 'ERROR_REPORTING', E_ALL | E_STRICT );
        defined( 'DEPLOY_PROFILE' ) or define( 'DEPLOY_PROFILE', 'development' );
        defined( 'NSTOK' ) or define( 'NSTOK', '/' );

        // Make sure the paths have a trailing slash
        $appPath    = APP_PATH;
        $implPath   = IMPL_PATH;
        $plPath     = PL_PATH;
        if ( $appPath[count( $appPath ) - 1] !== '/' ) $appPath .= '/';
        if ( $implPath[count( $implPath ) - 1] !== '/' ) $implPath .= '/';
        if ( $plPath[count( $plPath ) - 1] !== '/' ) $plPath .= '/';

        // Instantitate a version of the loader first if none provided. Usually,
        // to improve performance, if the implementor decides to use their own
        // version of the loader, it would be advisable to perform the initialization
        // in index.php and pass in the loader through the parameter $loader
        // rather than leaving it as a NULL
        if ( NULL === $loader )
        {
            require_once PL_PATH . 'system/common/loader' . EXT;
            $loader = new PainlessLoader;

            // Replace itself with a proper loader
            $loader = $loader->get( 'system/common/loader' );
        }
        
        self::$loader = $loader;
        self::$core = $loader->get( 'system/common/core' );

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

/* * -----------------------------------------------------------------------------
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