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
 * One router, many workflows. 
 *
 * Each router records a list of workflows run in succession, and allow these
 * workflows to share a common data domain. This is to say these workflows can
 * freely pass data amongst each other.
 */

class PainlessRouter
{

    /**
     * The stack of workflows that had been loaded by this router
     * @var array   an array of PainlessWorkflow instances
     */
    public $workflows = array( );

    /**
     * The default way to parse the URI
     * @var array   a default array of URI parameter
     */
    protected $defaultRouteConfig = array( 'module', 'workflow', 'param-all' );

    /**
     * Processes the request and automatically discover the method, module, workflow, parameter string
     * and the invoking agent.
     * @param string $uri       an optional URI string. If nothing is passed in, it'll assume its a HTTP request call
     */
    public function process( $uri = '' )
    {
        // Pre-define the variables
        $module         = '';
        $workflow       = '';
        $method         = '';
        $agent          = '';
        $contentType    = '';
        $params         = array( );

        // Note that the $uri that is passed in can come in two formats:
        //
        // [method] [param]/[param]/[param]     e.g. GET user/profile
        // or
        // [param]/[param]/[param]              e.g. user/profile
        //
        // We need the $uri to be the latter, so we split the string now
        $pos = strpos( $uri, ' ' );
        if ( FALSE !== $pos )
        {
            $method = strtolower( substr( $uri, 0, $pos ) );
            $uri = trim( substr( $uri, $pos + 1 ) );
        }

        // This process call came from CLI or CRON
        if ( ! isset( $_SERVER['HTTP_HOST'] ) )
        {
            $agent = PHP_SAPI;

            // In CLI mode, $uri CANNOT be empty
            if ( empty( $uri ) )
                throw new PainlessRouterException( '$uri that is passed into process( ) cannot be NULL when Painless is running in CLI mode' );

            // In CLI mode, $uri MUST be a string
            if ( ! is_string( $uri ) )
                throw new PainlessRouterException( '$uri that is passed into process( ) must be a string when Painless is running in CLI mode' );            
        }
        // This process call came from HTTP or REST
        else
        {
            $agent = $_SERVER['HTTP_USER_AGENT'];

            // Make sure to get the method from REQUEST_METHOD if none is provided
            // in $uri
            if ( empty( $method ) ) $method = $_SERVER['REQUEST_METHOD'];
        }

        // Process the URI into an array
        $uri = $this->getUri( $uri );

        // Process the URI to find out the module, workflow, content type and the parameter string
        $params = $this->processUri( $uri, $module, $workflow, $contentType );

        // At this point we have a $method, $agent, $module, $workflow, $contentType and $params
        return $this->dispatch( $method, $module, $workflow, $contentType, $params, $agent );
    }

    /**
     * Dispatches to the workflow directly
     * @param string $method        GET, POST, PUT, etc
     * @param string $module        the name of the module to dispatch to
     * @param string $workflow      the workflow to dispatch to
     * @param string $contentType   the type of the content invoked
     * @param string $params        the parameter string/array to save into the request
     * @param string $agent         the invoking agent
     * @return PainlessResponse     returns an instance of the PainlessResponse object
     */
    public function dispatch( $method, $module, $workflow, $contentType, $params, $agent )
    {
        $woObj = Painless::get( "workflow/$module/$workflow" );

        if ( empty( $woObj ) ) throw new PainlessWorkflowNotFoundException( "Unable to find workflow [$module/$workflow]" );

        $woObj->name = $workflow;
        $woObj->module = $module;

        // construct the workflow
        $woObj->request( $method, $params, $contentType, $agent );

        $woObj->$method( );
        $response = $woObj->response;
        if ( ! ( $response instanceof PainlessResponse ) ) throw new PainlessRouterException( "Invalid return type from the workflow dispatch" );

        return $response;
    }

    protected function getUri( $uri = '' )
    {
        // If $uri is not passed in, assume that this is an external routing call,
        // meaning the agent is either an RPC, a REST call or an HTTP agent.
        if ( empty( $uri ) )
        {
            // Get the URI segments into an array
            $requestURI = explode( '/', $_SERVER['REQUEST_URI'] );
            $scriptName = explode( '/', $_SERVER['SCRIPT_NAME'] );
            $count = count( $requestURI );

            // Run through the array to remove the base
            for ( $i = 0; $i < $count; $i++ )
            {
                if ( ! isset( $scriptName[$i] ) ) $scriptName[$i] = '';
                if ( ! isset( $requestURI[$i] ) ) $requestURI[$i] = '';
                if ( $requestURI[$i] === $scriptName[$i] ) continue;

                $uri[] = $requestURI[$i];
            }
        }
        // If $uri is not empty but isn't an array either, we assume that it is
        // a string
        elseif ( is_string( $uri ) )
        {
            $uri = explode( '/', $uri );
        }

        return $uri;
    }

    protected function processUri( $uri, & $module, & $workflow, & $contentType )
    {
        // Grab dependencies
        $config = Painless::get( 'system/common/config' );

        $params = array( );

        // Load the URI format from the routes config
        $routes = $config->get( 'routes.uri.config' );

        // Use the default routing if not configured (auto-routing)
        if ( ! is_array( $routes ) ) $routes = $this->defaultRouteConfig;

        // Process the URI list
        $count = count( $uri );
        for( $i = 0; $i < $count; $i++ )
        {
            if ( empty( $uri[$i] ) ) continue;

            if ( isset( $routes[$i] ) )
            {
                $con = $routes[$i];
                if ( 'module' === $con )
                {
                    $module = $uri[$i];
                }
                elseif ( 'workflow' === $con )
                {
                    $workflow = $uri[$i];
                }
                elseif ( 'param' === $con )
                {
                    $params[] = $uri[$i];
                }
            }
            else
            {
                $params = array_values( array_merge( $params, array_slice( $uri, $i ) ) );
                break;
            }
        }

        // Now, we try to determine the content type by checking the last URI
        // segment for a dotted notation
        $count = count( $params );
        if ( $count > 0 )
        {
            $last = $params[$count - 1];
            $pos = strpos( $last, '.' );
            if ( FALSE !== $pos )
            {
                // extract the content type from the segment
                $contentType = substr( $last, $pos + 1 );

                // Make sure the content type is valid
                if ( empty( $contentType ) ) $contentType = 'html';

                // remove the content type from the last URI segment
                $params[$count - 1] = substr( $last, 0, $pos );
            }
        }

        // Ensure that module and workflow are defined. If they are not, try to
        // load them from the config file
        if ( empty( $module ) )
        {
            $module = $config->get( 'routes.uri.default.module' );
        }

        if ( empty( $workflow ) )
        {
            $workflow = $config->get( 'routes.uri.default.workflow' );
        }

        // Join the URI back into a string
        $params = implode( '/', $params );

        return $params;
    }
}

class PainlessRouterException extends ErrorException { }
class PainlessWorkflowNotFoundException extends ErrorException { }