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
namespace Painless\System\Workflow;

class Request
{
    /**
     * Supported Content Types
     */
    const HTML      = 'html';
    const JSON      = 'json';
    const FORM      = 'form';
    const RSS       = 'rss';

    /**
     * Request methods that should be implemented by the workflows
     */
    const GET       = 'get';
    const POST      = 'post';
    const PUT       = 'put';
    const DELETE    = 'delete';

    /**
     * Request methods that are automatically handled by workflows themselves
     */
    const OPTIONS   = 'options';

    /**
     * Parameter style
     */
    const PS_INDEX  = 1;            // Individual params are referenced by their index ID
                                    // (e.g. $this->getParam( 0 ) on "/user/profile/page/1" would return "page")
    
    const PS_PAIR   = 2;            // Params are paired up with the previous param being the ID
                                    // (e.g. $this->getParam( 'page' ) on "/user/profile/page/1" would return 1)

    const PS_ASSOC  = 3;            // Params are passed in as an associative array, so no processing is needed.

    const PS_CONFIG = 4;            // Params are named resources defined by the routes config
                                    // (e.g. if the workflow says "/:action/:page-no",
                                    // $this->getParam( 'page-no' ) on "/user/profile/page/1" would return 1)

    const PS_DEFER  = 5;            // Defers the parameter parsing to the invoked workflow

    /**
     * The current method requested
     * @var string  
     */
    public $method              = self::GET;

    /**
     * The current content type requested
     * @var string  
     */
    public $contentType         = self::HTML;

    /**
     * The agent that made the request. Note that if the agent is 'painless',
     * then it should be understood as an internal call (most probably used to
     * construct composite views)
     * @var string  
     */
    public $agent               = 'painless';

    /**
     * The parameters that are passed in along with the request
     * @var array   
     */
    public $params              = array( );

    /**
     * How to parse the parameter string
     * @var int     
     */
    public $paramStyle          = self::PS_PAIR;

    //--------------------------------------------------------------------------
    public function param( $key )
    {
        // if the key cannot be found in the parameter array, return $default
        if ( isset( $this->params[$key] ) )
            return $this->params[$key];

        return FALSE;
    }

    //--------------------------------------------------------------------------
    public function params( $params = NULL, $append = FALSE, $parseStyle = 0 )
    {
        if ( NULL !== $params )
        {
            $style = ( $parseStyle !== 0 ) ?: $this->paramStyle;

            // Parse the parameter string using PS_INDEX
            if ( $style === self::PS_INDEX )
            {
                $params = array_values( $params );
            }
            // Parse the parameter string using PS_PAIR
            elseif ( $style === self::PS_PAIR )
            {
                $count = count( $params );
                $tmp = array( );

                // make sure the size of $params is an odd number. If not, trim the last
                // element off
                if ( ( $count % 2 ) !== 0 )
                {
                    unset ( $params[ $count - 1 ] );

                    // Re-count the parameter array
                    $count = count( $params );
                }

                // Pair 'em up side by side; send 'em off in the dark of night. Watch
                // them through your infra-sights; and don't you scream when the
                // zombies bite...
                for( $i = 0; $i < $count; $i += 2 )
                {
                    $tmp[$params[$i]] = $params[$i + 1];
                }

                unset( $params );
                $params = $tmp;
            }
            // Parse the parameter string using PS_CONFIG
            elseif ( $style === self::PS_CONFIG )
            {
                // load the routes config file
                $config = \Painless::load( 'system/common/config' );
                $routes = $config->get( 'routes.uri.map' );

                if ( empty( $routes ) )
                    throw new \ErrorException( 'PS_CONFIG parameter parsing style can only be used if routes are properly set up (routes.uri.map)' );
                if ( empty( $this->parent ) )
                    throw new \ErrorException( 'PS_CONFIG will not work if the invoking workflow was not instantiated before-hand. Please ensure that the router had instantiated the workflow and set a reference to it in $this->workflow before calling init( )' );

                $module     = $this->module;
                $controller = $this->controller;
                $method     = $this->method;

                // construct the dispatch path
                $key = "$module/$controller";
                $map = array( );

                // see if there's a mapping for that workflow
                if ( ! isset( $routes[$method][$key] ) )
                {
                    if ( ! isset( $routes['*'][$key] ) )
                        throw new \ErrorException( "The route map [$map] is not found in the routes config. Please make sure the route map exists." );
                    else
                        $map = $routes['*'][$key];
                }
                else
                {
                    $map = $routes[$method][$key];
                }

                // start parsing the $params array by assigning them their respective keys
                $tmp = array( );
                $count = count( $params );
                for( $i = 0; $i < $count; $i++ )
                {
                    if ( ! isset( $map[$i] ) ) break;

                    $tmp[$map[$i]] = $params[$i];
                }
                unset( $params );
                $params = $tmp;
            }
            // Parse the parameter string using PS_DEFER
            elseif ( $style === self::PS_DEFER )
            {
                /* TODO: Finish this later
                if ( empty( $this->controller ) )
                        throw new \ErrorException( 'PS_DEFER will not work if the invoking workflow was not instantiated before-hand. Please ensure that the router had instantiated the workflow and set a reference to it in $this->workflow before calling init( )' );
                if ( ! method_exists( $this->controller, 'processParams' ) ) throw new \ErrorException( 'PS_DEFER requires the invoking workflow [' . get_class( $this->workflow ) . '] to implement the method processParams( $params ) to work.' );

                $params = $this->controller->processParams( $params );
                 */
            }

            if ( $append )
                $this->params = array_merge( $this->params, $params );
            else
                $this->params = $params;
        }

        return $this->params;
    }
}