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
    protected $env      = array( );
    
    /* Container for all loaded components */
    protected $com      = array( );

    /* The originating request */
    protected $origin   = NULL;

    /* The current active request */
    protected $active   = NULL;

    /* Container for all executions */
    protected $elog     = array( );
    
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
        elseif ( NULL === $val && ! isset( $this->env[$key] ) )
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
        elseif ( NULL === $obj && ! isset( $this->com[$uri] ) )
        {
            return NULL;
        }
        
        $this->com[$uri] = $obj;
        return $this;
    }

    public function execute( $entry, $cmd = '' )
    {
        // Here we need to determine the entry point. There are only 3 of them:
        // HTTP (includes REST calls), CLI (including cron jobs) and APP. The
        // first two are fairly self-explanatory, but APP needs more explanation
        // on this front.
        //
        // When a request to APP is made, Painless would automatically convert it
        // to either HTTP or APP. First it'll look inside the app registry, and
        // check the app's path. If the path starts with a http://, it'll convert
        // the call to a HTTP call instead, and if not, it'll assume its a file
        // path and use APP as is.
        //
        //  Example:
        //      Painless::request( 'GET app://flight-plan/id/123' );
        //      will first search for the app's path inside the registry, and
        //      if it looks like this:
        //          $config['apps']['flight-plan'] = '/usr/local/web/htdocs/flight-plan';
        //      then it is a local call, and if it looks like this:
        //          $config['apps']['flight-plan'] = 'http://flight-plan.foo.com:8003';
        //      then it is a REST call.

        // Load the router into the Core registry
        $router = \Painless::load( 'system/common/router' );

        // Localize the response variable
        $response = FALSE;

        // Send the command to the router to process, which will create a request
        // object containing all routing information (as well as some extra info
        // like agent string, content type, etc)
        if ( \Painless::RUN_HTTP === $entry || \Painless::RUN_CLI === $entry || \Painless::RUN_APP === $entry || \Painless::RUN_INTERNAL )
        {
            // Send the router the command to receive a request
            $request = $router->process( $entry, $cmd );

            // If $request is FALSE, something baaaaadddddd has happened inside
            // the router. Use a 500 error response instead of dispatching it.
            if ( FALSE === $request )
                $response = \Painless::manufacture( 'response', 500, 'Fatal error when trying to process the command in router. See log for more details.' );
            // Dispatch the request
            else
                $response = $router->dispatch( $request );
        }
        // Handle the error of an invalid entry point
        else
        {
            // Manufacture a 500 error status response object
            $response = \Painless::manufacture( 'response', 500, 'Invalid entry point' );
        }

        // At this point, save both the request and response to the core's
        // command log, along with the original command
        $this->log( $entry, $cmd, $request, $response );

        // Get the renderer
        $render = \Painless::load( 'system/common/render' );

        // Process the request and response to get an output
        return $render->process( $request, $response );
    }

    public function log( $entry, $cmd, \Painless\System\Workflow\Request $request, \Painless\System\Workflow\Response $response )
    {
        $this->elog[] = array( $entry, $cmd, $request, $response );
    }
}