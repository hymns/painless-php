<?php

/*
 * Painless Framework - a lightweight enterprise-level PHP framework
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
    public static $cache = array( );

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
        // make sure all env consts are set
        if ( !defined( 'APP_PATH' ) )
            throw new PainlessException( 'APP_PATH is not defined', 1 );
        if ( !defined( 'IMPL_PATH' ) )
            throw new PainlessException( 'IMPL_PATH is not defined', 2 );
        if ( !defined( 'IMPL_NAME' ) )
            throw new PainlessException( 'IMPL_NAME is not defined', 3 );

        // set default values for non-critical env consts if none are set
        defined( 'ERROR_REPORTING' ) or define( 'ERROR_REPORTING', E_ALL | E_STRICT );
        defined( 'DEPLOY_PROFILE' ) or define( 'DEPLOY_PROFILE', 'development' );
        defined( 'NSTOK' ) or define( 'NSTOK', '/' );

        // instantitate a version of the loader first if none provided. Usually,
        // to improve performance, if the implementor decides to use their own
        // version of the loader, it would be advisable to perform the initialization
        // in index.php and pass in the loader through the parameter $loader
        // rather than leaving it as a NULL
        if ( NULL === $loader )
        {
            require_once PL_PATH . 'system/common/loader' . EXT;
            $loader = new PainlessLoader;

            // replace itself with a proper loader
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

function array_get_clean( &$array, $key, $defaultReturn = FALSE )
{
    if ( isset( $array[$key] ) )
    {
        $value = $array[$key];
        unset( $array[$key] );
        return $value;
    }

    return $defaultReturn;
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