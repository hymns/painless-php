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
        if ( Painless::isProfile( DEV ) )
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
        $contentType    = 'none';
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