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
 */
namespace Painless\System\Common;

defined( 'PHP_VER' ) or define( 'PHP_VER', phpversion( ) );

class Core
{    
    /* Container for all environment variables */
    protected $env  = array( );
    
    /* Container for all loaded components */
    protected $com  = array( );

    /* The originating request */
    protected $origin = NULL;

    /* The current active request */
    protected $active = NULL;
    
    //--------------------------------------------------------------------------
    /**
     * Set or get an environment variable
     * @param string $key   the key of the environment variable
     * @param string $val   the value to set in $this->env[$key]
     * @return mixed        if a $val is provided, then return the Core instance
     *                      to allow for method chaining. Otherwise, return the
     *                      value in $this->env[$key]. 
     */
    public function env( $key, $val = NULL )
    {
        if ( NULL === $val && isset( $this->env[$key] ) )
        {
            return $this->env[$key];
        }
        elseif ( ! isset( $this->env[$key] ) )
        {
            return NULL;
        }
        
        $this->env[$key] = $val;
        return $this;
    }
    
    //--------------------------------------------------------------------------
    /**
     * Set or get a component
     * @param string $uri   the namespace/uri of the component
     * @param object $obj   the value to set in $this->com[$uri]
     * @return mixed        if a $obj is provided, then return the Core instance
     *                      to allow for method chaining. Otherwise, return the
     *                      value in $this->com[$uri].
     */
    public function com( $uri, $obj = NULL )
    {
        if ( NULL === $obj && isset( $this->com[$uri] ) )
        {
            return $this->com[$uri];
        }
        elseif ( ! isset( $this->com[$uri] ) )
        {
            return NULL;
        }
        
        $this->com[$uri] = $obj;
        return $this;
    }
    
    /**
     * processes the current request and returns a response
     */
    public function dispatch( )
    {
        Beholder::notify( 'core.dispatch.pre' );
        
        // start the session on every dispatch
        //$session = $this->load( 'system/common/session' );
        //$session->start( );

        // check and load the router
        $router = $this->load( 'system/common/router' );

        try
        {
            // let the router process the business logic
            $response = $router->process( );
        }
        catch( PainlessWorkflowNotFoundException $e )
        {
            // construct a 404 response
            $response = $this->load( 'system/workflow/response', LP_LOAD_NEW );
            $response->status = 404;
            $response->message = 'Unable to locate workflow';
        }
        catch( ErrorException $e )
        {
            $response = $this->load( 'system/workflow/response', LP_LOAD_NEW );
            $response->status = 500;
            $response->message = $e->getMessage( );
        }
        
        Beholder::notify( 'core.dispatch.post' );

        // pass the control to the renderer
        $render = $this->load( 'system/common/render' );
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

        $router = $this->load( 'system/common/router' );
        return $router->dispatch( $method, $module, $workflow, $contentType, $params, $agent );
    }

    public function run( $cmd = '' )
    {
        // Here we need to determine the entry point. There are only 3 of them:
        // HTTP (includes REST calls), CLI (including cron jobs) and APP. The
        // first two are fairly self-explanatory, but APP needs more explanation
        // on this front.
        //
        // APP can come in through two ways - internal calls and service calls.
        // Internal calls happen when an APP is located in the same machine as
        // Painless, by registering its source as a file path inside the app
        // registry. If a URL instead is provided, Painless would make a curl
        // call instead of simply including its files, which would then become
        // a HTTP/REST call.
        //
        //  Example:
        //      Painless::request( 'GET app://flight-plan/id/123' );
        //      will first search for the app's path inside the registry, and
        //      if it looks like this:
        //          $config['apps']['flight-plan'] = '/usr/local/web/htdocs/flight-plan';
        //      then it is a local call, and if it looks like this:
        //          $config['apps']['flight-plan'] = 'http://flight-plan.foo.com:8003';
        //      then it is a REST call.

        // Get the router
        $router = \Painless::load( 'system/common/router' );

        
    }
}