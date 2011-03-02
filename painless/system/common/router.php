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
     * The queue of URI that is saved and checked to prevent redundancy
     * @var array   the key is the URI segment called, while the value is the full URI with the method
     */
    protected $uri = array( );

    /**
     * The default way to parse the URI
     * @var array   a default array of URI parameter
     */
    protected $defaultRoute = array( 'module', 'workflow' );

    /**
     * Processes the request and automatically discover the method, module, workflow, parameter string
     * and the invoking agent.
     * @param string $uri       an optional URI string. If nothing is passed in, it'll assume its a HTTP request call
     * @param boolean $dispatch set to TRUE (default) to proceed with dispatching, FALSE to return the processed values
     */
    public function process( $uri = '', $dispatch = TRUE )
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

            // If no method is provided, default to get
            if ( empty( $method ) ) $method = 'get';
        }
        // This process call came from HTTP or REST
        else
        {
            // save the base dir for the templates
            $baseRoot = (isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) ? 'https' : 'http';
            $baseUrl = $baseRoot . '://' . $_SERVER['HTTP_HOST'];

            // $_SERVER['SCRIPT_NAME'] can, in contrast to $_SERVER['PHP_SELF'], not
            // be modified by a visitor.
            if ( $dir = trim( dirname( $_SERVER['SCRIPT_NAME'] ), '\,/' ) )
            {
                $baseUrl .= "/$dir";
            }

            define( 'APP_URL', $baseUrl . '/' );
            unset( $baseRoot );
            unset( $baseUrl );

            $agent = $_SERVER['HTTP_USER_AGENT'];

            // Make sure to get the method from REQUEST_METHOD if none is provided
            // in $uri
            if ( empty( $method ) ) $method = strtolower( $_SERVER['REQUEST_METHOD'] );
        }

        // Process the URI into an array
        $uri = $this->getUri( $uri );

        // Process the URI to find out the module, workflow, content type and the parameter string
        $params = $this->processUri( $uri, $module, $workflow, $contentType );

        // Save the URI into the router to keep track of the routes
        $this->saveUri( $uri, $method, $params );

        // At this point we have a $method, $agent, $module, $workflow, $contentType and $params
        if ( $dispatch )
            return $this->dispatch( $method, $module, $workflow, $contentType, $params, $agent );
        else
            return array( $method, $module, $workflow, $contentType, $params, $agent );
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
        $woObj->init( $module, $workflow );

        // construct the workflow
        $woObj->request( $method, $params, $contentType, $agent );
        $response = $woObj->run( );
        if ( ! ( $response instanceof PainlessResponse ) ) throw new PainlessRouterException( "Invalid return type from the workflow dispatch" );

        return $response;
    }

    /**
     * Tries to build the URI array either from the parameter or from the request
     * headers
     * @param string $uri   the URI string to pass in for processing
     * @return array        the URI broken down into an array
     */
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

    /**
     * Processes the URI and returns the parameter array, as well as mapping out
     * module, workflow, and content type
     * @param array $uri            the URI in an array
     * @param string $module        the module to map to
     * @param string $workflow      the workflow to map to
     * @param string $contentType   the content type of this request
     * @return array                an array of parameters
     */
    protected function processUri( $uri, & $module, & $workflow, & $contentType )
    {
        // Grab dependencies
        $config = Painless::get( 'system/common/config' );

        $params = array( );

        // Load the URI format from the routes config
        $routes = $config->get( 'routes.uri.config' );

        // Use the default routing if not configured (auto-routing)
        if ( ! is_array( $routes ) ) $routes = $this->defaultRoute;

        // Process the URI list
        $count = count( $uri );
        for( $i = 0; $i < $count; $i++ )
        {
            if ( empty( $uri[$i] ) ) continue;

            if ( isset( $routes[$i] ) )
            {
                $con = $routes[$i];
                if ( 'alias' === $con )
                {
                    // Get the workflow and module mapping, and then append the
                    // rest of the URI into the params array. No point proceeding
                    // further as alias don't play well with module and workflow
                    list( $module, $workflow ) = $this->mapAlias( $uri[$i] );

                    // Only do this if this is not the end of the URI array
                    if ( $i !== $count )
                    {
                        $params = array_values( array_merge( $params, array_slice( $uri, $i + 1 ) ) );
                        break;
                    }
                }
                elseif ( 'module' === $con )
                {
                    // Grab the module
                    $module = $uri[$i];
                }
                elseif ( 'workflow' === $con )
                {
                    // Grab the workflow
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

    protected function mapAlias( $alias )
    {
        // Grab the dependencies
        $config = Painless::get( 'system/common/config' );

        // Grab the routes
        $routes = $config->get( 'routes.alias' );
        if ( isset( $routes[$alias] ) ) return $routes[$alias];

        return array( FALSE, FALSE );
    }

    protected function saveUri( $uri, $method, $params )
    {
        // Rebuild the URI string
        $uri = implode( '/', $uri );
        
        $this->uri[$uri] = "$method $uri";
    }

    public function getOrigin( )
    {
        // TODO: Don't use array_shift!
        return array_shift( $this->uri );
    }
}

class PainlessRouterException extends ErrorException { }
class PainlessWorkflowNotFoundException extends ErrorException { }