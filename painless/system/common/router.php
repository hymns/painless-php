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

namespace Painless\System\Common;

use \Painless\System\Workflow\Request as Request;
use \Painless\System\Workflow\Response as Response;

class Router
{
    /**
     * The default way to parse the URI
     * @var array   a default array of URI parameter
     */
    protected $defaultRoute = array( 'module', 'controller' );

    public function process( $entry, $uri = '' )
    {
        // Localize the values
        $method     = 'get';
        $original   = $uri;

        // First, parse the $uri. They come in two formats, either:
        // [method] [uri] or just [uri]. Let's see if it's the former.
        $pos = strpos( $uri, ' ' );
        if ( FALSE !== $pos )
        {
            $method = strtolower( substr( $uri, 0, $pos ) );
            $uri = trim( substr( $uri, $pos + 1 ) );
        }

        // The URI is ready for processing now. Pass to the appropriate processing
        // function. Maybe in the future we should use a finite state machine
        // instead?
        switch( $entry )
        {
            case \Painless::RUN_HTTP :
                return $this->processHttp( $method, $uri );
            case \Painless::RUN_CLI :
                return $this->processCli( $method, $uri );
            case \Painless::RUN_APP :
                return $this->processApp( $method, $uri );
            default :
                return FALSE;
        }
    }

    protected function processHttp( $method, $uri )
    {
        // Localize the variables
        $module     = '';
        $controller = '';
        $param      = array( );

        // Determine and set the APP_URL env var
        $url        = ( (isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'];

        // $_SERVER['SCRIPT_NAME'] can, in contrast to $_SERVER['PHP_SELF'], not
        // be modified by a visitor.
        if ( $dir = trim( dirname( $_SERVER['SCRIPT_NAME'] ), '\,/' ) )
            $url .= "/$dir";

        // Set the APP_URL env var into Core
        \Painless::app( )->env( \Painless::APP_URL, $url );

        // If the method is empty, read it from REQUEST_METHOD
        if ( empty( $method ) ) $method = strtolower( $method );

        // If the URI is emtpy, read it from the server array
        if ( empty( $uri ) )
        {
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

        // At this point, the URI has been split into an array. Pass it to
        // mapUri to map to the correct module and controller.
        list( $module, $controller, $param, $contentType ) = $this->mapUri( $uri );

        return \Painless::manufacture( 'request', $method, $module, $controller, $param, $contentType, $_SERVER['HTTP_USER_AGENT'] );
    }

    protected function processCli( $method, $uri )
    {
        // Localize the variables
        $module     = '';
        $controller = '';
        $param      = array( );

        // If it's an APP call, a URI must be given
        if ( empty( $uri ) )
            return $request;

        // Check if argv is set in the Core (which should be the case in the
        // bootstrap process if writing for a CLI app)
        $argv = \Painless::app( )->env( \Painless::CLI_ARGV );
        if ( ! empty( $arv ) )
        {
            // TODO: Finish this
        }
        
        return \Painless::manufacture( 'request', $method, $module, $controller, $param, '', PHP_SAPI );
    }

    protected function processApp( $method, $uri )
    {
        // Localize the variables
        $module     = '';
        $controller = '';
        $param      = array( );

        // If it's an APP call, a URI must be given
        if ( empty( $uri ) )
            return $request;

        // TODO: Finish this

        return \Painless::manufacture( 'request', $method, $module, $controller, $param, '', 'PainlessPHP Internal App [v' . \Painless::CORE_VERSION . ']' );
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
     
    public function dispatch( $method, $module, $workflow, $contentType, $params, $agent )
    {
        if ( ! Beholder::notifyUntil( 'router.pre', array( $method, $module, $workflow, $contentType, $params, $agent ) ) )
            return FALSE;

        $woObj = \Painless::load( "controller/$module/$workflow" );

        if ( empty( $woObj ) ) throw new ErrorException( "Unable to find workflow [$module/$workflow]" );
        $woObj->init( $module, $workflow );

        // construct the workflow
        $woObj->request( $method, $params, $contentType, $agent );
        $response = $woObj->run( );
        if ( ! ( $response instanceof Response ) ) throw new ErrorException( "Invalid return type from the workflow dispatch" );

        return $response;
    }*/

    /**
     * Processes the URI and returns the parameter array, as well as mapping out
     * module, workflow, and content type
     * @param array $uri            the URI in an array
     * @return array                an array of $module, $controller, $params and
     *                              $contentType
     */
    protected function mapUri( $uri )
    {
        // Grab dependencies
        $config = \Painless::load( 'system/common/config' );

        // Localize the variables
        $module         = '';
        $controller     = '';
        $contentType    = '';
        $params         = array( );

        // Load the URI format from the routes config
        $routes = $config->get( 'routes.uri.config' );

        // Use the default routing if not configured (auto-routing)
        if ( ! is_array( $routes ) )
            $routes = $this->defaultRoute;

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
                    $routeMap = $config->get( 'routes.alias' );
                    if ( isset( $routeMap[$alias] ) )
                        list( $module, $controller ) = $routeMap[$alias];

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
                elseif ( 'controller' === $con )
                {
                    // Grab the controller
                    $controller = $uri[$i];
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

        // Ensure that module and controller are defined. If they are not, try to
        // load them from the config file
        if ( empty( $module ) )
        {
            $module = $config->get( 'routes.uri.default.module' );
        }
        if ( empty( $controller ) )
        {
            $controller = $config->get( 'routes.uri.default.workflow' );
        }

        return array( $module, $controller, $params, $contentType );
    }
}