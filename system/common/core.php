<?php

/**
 * PainlessCore is the core class, and is always included in every request. It serves
 * as a central registry for all loaded components, workflows, etc, and mainly acts
 * as the data store for Painless (the designated component locator).
 *
 * 
 */

define( 'PHP_VER', phpversion( ) );

class PainlessCore
{
    protected $errorReportLevel = 0;

    public function __construct( $useCustomExceptionPage = TRUE, $useCustomErrorPage = TRUE )
    {
        // save the base dir for the templates
        // protocol-agnostic URL is temporarily disabled until we have full HTTPS support.
        //$base_url = '//' . $_SERVER['HTTP_HOST'];

        $base_root = (isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) ? 'https' : 'http';
        $base_url = $base_root . '://' . $_SERVER['HTTP_HOST'];

        // $_SERVER['SCRIPT_NAME'] can, in contrast to $_SERVER['PHP_SELF'], not
        // be modified by a visitor.
        if ( $dir = trim( dirname( $_SERVER['SCRIPT_NAME'] ), '\,/' ) )
        {
            $base_url .= "/$dir";
        }

        define( 'APP_URL', $base_url . '/' );

        // set the error reporting level to highest
        if ( !defined( 'ERROR_LEVEL' ) )
        {
            $this->errorReportLevel = E_STRICT | E_ALL;
            error_reporting( $this->errorReportLevel );
        }
        else
        {
            $this->errorReportLevel = ERROR_LEVEL;
            error_reporting( $this->errorReportLevel );
        }

        // set to show errors
        if ( DEPLOY_PROFILE === 'development' )
        {
            ini_set( 'display_errors', 1 );
        }

        // define the error handlers if necessary
        if ( $useCustomExceptionPage )
        {
            set_exception_handler( array( $this, 'handleException' ) );
        }

        if ( $useCustomErrorPage )
        {
            set_error_handler( array( $this, 'handleError' ) );
        }
    }

    public function __destruct( )
    {
        // destroy the logger first because it might still need a database
        // connection
        unset( $this->components['system/common/logger'] );
        unset( $this->components['system/common/database'] );
    }

    public function getErrorReportLevel( )
    {
        return $this->errorReportLevel;
    }
    /**
     * processes the current request and returns a response
     */
    public function dispatch( )
    {
        // start the session on every dispatch
        $session = Painless::get( 'system/common/session' );
        $session->start( );

        // check and load the router
        $router = Painless::get( 'system/common/router' );

        try
        {
            // let the router process the business logic
            $response = $router->process( );
        }
        catch( PainlessWorkflowNotFoundException $e )
        {
            // construct a 404 response
            $response = Painless::get( 'system/workflow/response', LP_LOAD_NEW );
            $response->status = 404;
            $response->message = 'Unable to locate workflow';
        }
        catch( ErrorException $e )
        {
            $response = Painless::get( 'system/workflow/response', LP_LOAD_NEW );
            $response->status = 500;
            $response->message = $e->getMessage( );
        }

        // pass the control to the renderer
        $render = Painless::get( 'system/common/render' );
        $output = $render->process( $response );

        return $output;
    }

    public function exec( $uri )
    {
        // split the URI up
        $segments = explode( ' ', $uri );
        $method = 'GET';
        $uri = '';

        if ( count( $segments ) == 2 )
        {
            $method = $segments[0];
            $uri = explode( '/', $segments[1] );
        }

        $module         = $uri[0];
        $workflow       = $uri[1];
        $params         = ( count( $uri ) > 2 ) ? array_slice( $uri, 2 ) : array( );
        $contentType    = 'html';
        $agent          = 'painless';

        $router = Painless::get( 'system/common/router' );
        return $router->dispatch( $method, $module, $workflow, $contentType, $params, $agent );
    }

    /**
     * internal error handling function to output a properly formatted stack
     * trace in Painless's error page
     *
     * @param int $errNo
     * @param string $errStr
     * @param string $errFile
     * @param string $errLine
     */
    public function handleError( $errNo, $errStr, $errFile, $errLine )
    {
        $error = Painless::get( 'system/common/error' );
        $error->handleError( $errNo, $errStr, $errFile, $errLine );
    }

    /**
     * internal error handling function to output a properly formatted stack
     * trace in Painless's exception page
     *
     * @param object $exception an exception object
     */
    public function handleException( $exception )
    {
        $error = Painless::get( 'system/common/error' );
        $error->handleException( $exception );
    }
}

class PainlessCoreException extends ErrorException { }