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

    const PS_CONFIG = 3;            // Params are named resources defined by the routes config
                                    // (e.g. if the workflow says "/:action/:page-no",
                                    // $this->getParam( 'page-no' ) on "/user/profile/page/1" would return 1)

    const PS_DEFER  = 4;            // Defers the parameter parsing to the invoked workflow

    /**
     * The current method requested
     * @var string  must match the list of methods supported by getMethodList()
     */
    public $method              = self::GET;

    /**
     * The current content type requested
     * @var string  must match the list of views supported by PainlessView
     */
    public $contentType         = self::HTML;

    /**
     * The agent that made the request. Note that if the agent is 'painless',
     * then it should be understood as an internal call (most probably used to
     * construct composite views)
     * @var string  the agent that invoked this call (e.g. a browser, remote REST call, etc)
     */
    public $agent               = 'painless';

    /**
     * The parameters that are passed in along with the request
     * @var array   an array of parameters compiled from the URI that is passed in
     */
    public $params              = array( );

    /**
     * How to parse the parameter string
     * @var int     must match either one of the PS_* constants
     */
    public $paramStyle          = self::PS_PAIR;

    /**
     * A reference to the invoking workflow (may cause *RECURSION* when doing a var_dump)
     * @var PainessWorkflow an instance of PainlessWorkflow
     */
    public $parent              = NULL;

    /**
     * Whether or not the parameter has been properly initialized
     * @var boolean TRUE if the request is properly initialized, FALSE if otherwise
     */
    protected $isInitialized    = FALSE;

    /**
     * Initializes the request accordingly
     * @param string $method        the method (GET, PUT, POST, etc) invoked
     * @param string $params        the URI string that is to be converted into a parameter list
     * @param string $contentType   the type of content the request is expecting
     * @param string $agent         the invoking agent of this request (origin)
     */
    public function init( $method, $paramStr, $contentType, $agent )
    {
        // setter functions are used because the implementor might want to perform
        // validation on each setter call. For example, the implementor might wish
        // to ensure that only supported content types are passed in, and in the
        // extension class add in the validation code into setContentType()
        $this->setMethod( $method )
             ->setParams( $paramStr )
             ->setContentType( $contentType )
             ->setAgent( $agent );

        // don't forget this to allow error detection during getParam
        $this->isInitialized = TRUE;
    }

    /**
     * Retrieves a request parameter
     * @param mixed $key            the parameter's key (int for PS_INDEX, string for the rest)
     * @param boolean $default
     * @return mixed
     */
    public function getParam( $key = '', $default = FALSE )
    {
        // don't proceed if the request is not initialized
        if ( ! $this->isInitialized ) return $default;

        // if no key is specified, return the entire array. Useful for functions
        // that need to access the parameter array heavily, as reading from a
        // local variable is much faster than invoking a function call
        if ( empty( $key ) && 0 !== $key )
            return $this->params;

        // if the key cannot be found in the parameter array, return $default
        if ( isset( $this->params[$key] ) )
            return $this->params[$key];
        else
            return $default;
    }

    /**
     * Returns a list of possible HTTP/REST methods supported implicitly by this
     * request.
     * @return array an indexed array whose value are the methods supported 
     */
    public function getMethodList( )
    {
        return array(
            Request::GET,
            Request::POST,
            Request::DELETE,
            Request::OPTIONS,
            Request::CONNECT,
        );
    }

    /**
     * Sets a parameter parsing style for the request. Must be called BEFORE
     * init( ) is called, because setParams( ) occurs within init( ).
     * @param int $style            the parameter style to set
     */
    public function setParamStyle( $style )
    {
        // Check if the style is supported
        if ( $style !== self::PS_INDEX && $style !== self::PS_PAIR && $style !== self::PS_CONFIG && $style !== self::PS_DEFER ) return;

        $this->paramStyle = $style;
    }

    /**
     * Setter for $this->method
     * @param string $method        the invoked method
     * @return PainlessRequest      returns itself to allow function chaining
     */
    protected function setMethod( $method )
    {
        if ( ! empty( $method ) )
            $this->method = $method;
        return $this;
    }

    /**
     * Setter for $this->params
     * @param mixed $params         either an array or a string
     * @return PainlessRequest      returns itself to allow function chaining
     */
    protected function setParams( $params )
    {
        if ( empty( $params ) ) return $this;

        // convert $param into an array if not already so
        if ( ! is_array( $params ) )
        {
            $params = explode( '/', $params );
            if ( FALSE === $params ) throw new RequestException( "Malformed parameter string [$params]" );
        }

        // parse the parameter string as an array
        $style = $this->paramStyle;

        // parse the parameter string using PS_INDEX
        if ( $style === self::PS_INDEX )
        {
            $params = array_values( $params );
        }
        // parse the parameter string using PS_PAIR
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

            for( $i = 0; $i < $count; $i += 2 )
            {
                $tmp[$params[$i]] = $params[$i + 1];
            }

            unset( $params );
            $params = $tmp;
        }
        // parse the parameter string using PS_CONFIG
        elseif ( $style === self::PS_CONFIG )
        {
            // load the routes config file
            $config = Painless::get( 'system/common/config' );
            $routes = $config->get( 'routes.uri.map' );

            if ( empty( $routes ) ) throw new RequestException( 'PS_CONFIG parameter parsing style can only be used if routes are properly set up (routes.uri.map)' );
            if ( empty( $this->parent ) ) throw new RequestException( 'PS_CONFIG will not work if the invoking workflow was not instantiated before-hand. Please ensure that the router had instantiated the workflow and set a reference to it in $this->workflow before calling init( )' );

            $module     = $this->parent->module;
            $workflow   = $this->parent->name;
            $method     = $this->method;

            // construct the workflow key
            $key = "$module/$workflow";
            $map = array( );

            // see if there's a mapping for that workflow
            if ( ! isset( $routes[$method][$key] ) )
            {
                if ( ! isset( $routes['*'][$key] ) )
                    throw new RequestException( "The route map [$map] is not found in the routes config. Please make sure the route map exists." );
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
        // parse the parameter string using PS_DEFER
        elseif ( $style === self::PS_DEFER )
        {
            if ( empty( $this->workflow ) ) throw new RequestException( 'PS_DEFER will not work if the invoking workflow was not instantiated before-hand. Please ensure that the router had instantiated the workflow and set a reference to it in $this->workflow before calling init( )' );
            if ( ! method_exists( $this->workflow, 'processParams' ) ) throw new RequestException( 'PS_DEFER requires the invoking workflow [' . get_class( $this->workflow ) . '] to implement the method processParams( $params ) to work.' );

            $params = $this->workflow->processParams( $params );
        }

        $this->params = $params;
        return $this;
    }

    /**
     * Setter for $this->contentType
     * @param string $contentType   the type of content to return
     * @return PainlessRequest      returns itself to allow function chaining
     */
    protected function setContentType( $contentType )
    {
        $this->contentType = $contentType;
        return $this;
    }

    /**
     * Setter for $this->agent
     * @param string $agent         the invoking agent
     * @return PainlessRequest      returns itself to allow function chaining
     */
    protected function setAgent( $agent )
    {
        $this->agent = $agent;
        return $this;
    }
}

class RequestException extends \ErrorException { }