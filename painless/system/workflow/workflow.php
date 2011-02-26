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

// Load the definitions of PainlessRequest and PainlessResponse's class definitions
Painless::get( 'system/workflow/request', LP_DEF_ONLY );
Painless::get( 'system/workflow/response', LP_DEF_ONLY );

class PainlessWorkflow
{
    /**
     * The workflow's dash-notation name
     * @var string              the workflow's name
     */
    public $name = 'painless';

    /**
     * The module's dash-notation name
     * @var string              the workflow's module name
     */
    public $module = '';

    /**
     * A request object of this workflow
     * @var PainlessRequest     an instance of the PainlessRequest object
     */
    public $request = NULL;

    /**
     * A response object of this Workflow
     * @var PainlessResponse    an instance of the PainlessResponse object
     */
    public $response = NULL;

    /**
     * before( ) and after( ) are called by the router both before dispatching
     * and after dispatching. These hooks are useful for writing in general
     * behaviors to the workflow that applies to all methods.
     */
    public function preRequest( )   { }
    public function before( )       { }
    public function after( )        { }

    /**
     * Initializes the workflow. Override this to use a custom parameter parsing
     * style for the request.
     */
    public function init( $module, $workflow )
    {
        $this->name = $workflow;
        $this->module = $module;

        // Remember to get a new instance of the request
        $request = Painless::get( 'system/workflow/request', LP_LOAD_NEW );
        $this->request = $request;
    }

    /**
     * Runs the workflow depending on the method requested
     * @return PainlessResponse     the response object returned by the method function
     */
    public function run( )
    {
        // Check if the method exists or not
        if ( ! method_exists( $this, $method ) ) return $this->response( 405, 'Method not supported' );

        $this->before( );
        $this->response = $this->$method;
        $this->after( );

        return $this->response;
    }

    /**
     * Creates a new request and attach to this workflow
     * @param string $method        the method/action invoked by the request
     * @param array $params         a parameter array to initialize the workflow with
     * @param string $contentType   the content type that is invoked
     * @param string $agent         the invoking agent
     * @return PainlessWorkflow     returns itself to facilitate method chaining
     */
    public function request( $method, $params, $contentType = PainlessRequest::HTML, $agent = 'painless' )
    {
        $request = $this->request;

        // Use the defaults if $contentType is empty
        if ( empty( $contentType ) ) $contentType = PainlessRequest::HTML;
        if ( empty( $agent ) ) $agent = 'painless';

        // Initialize the request
        $request->parent = & $this;
        $request->init( $method, $params, $contentType, $agent );

        $this->request = $request;
        return $this;
    }

    /**
     * Creates a response object. Usually called at the end of any invoked methods
     * @param int|PainlessResponse $status  a valid HTTP/REST status code
     * @param string $message               the message explaining the status of the response
     * @param mixed $payload                the payload of the workflow
     * @return PainlessResponse             a response object
     */
    public function response( $status, $message = '', $payload = array( ) )
    {
        // Double check $status first. If it's not an INT, assume it's a response
        // object
        if ( ! is_int( $status ) )
        {
            $this->response = $status;
        }
        else
        {
            // remember to get a new instance of the response
            $response = Painless::get( 'system/workflow/response', LP_LOAD_NEW );

            $response->setWorkflow( $this );
            $response->status = (int) $status;
            $response->message = $message;
            $response->payload = $payload;

            $this->response = $response;
        }

        return $this->response;
    }

    /**
     * Returns a list of actions supported by this workflow
     * @return array    an array of actions/methods supported by this workflow
     */
    public function options( )
    {
        $request = Painless::get( 'system/common/router/response' );
        $methods = $request->getMethodList( );

        // Run through the list of methods supported by the request object and then
        // cross-reference them to the list of methods available on this workflow.
        // If they don't exist, simply remove them from the method list and then
        // return the result. This would give the caller a good idea of what kind
        // of functions that particular workflow supports.
        $supported = array( );
        foreach( $methods as $i => $method )
        {
            if ( ! method_exists( $this, $method ) )
                $supported[] = $methods[$i];
        }

        return $methods;
    }
}

class PainlessWorkflowException extends ErrorException { }