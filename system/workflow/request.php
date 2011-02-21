<?php

class PainlessRequest
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
    const PS_INDEX  = 0;            // Individual params are referenced by their index ID
                                    // (e.g. $this->getParam( 0 ) on "/user/profile/page/1" would return "page")
    
    const PS_PAIR   = 1;            // Params are paired up with the previous param being the ID
                                    // (e.g. $this->getParam( 'page' ) on "/user/profile/page/1" would return 1)

    const PS_CONFIG  = 2;           // Params are named resources defined by the routes config
                                    // (e.g. if the workflow says "/:action/:page-no",
                                    // $this->getParam( 'page-no' ) on "/user/profile/page/1" would return 1)

    const PS_DEFER  = -1;           // Defers the parameter parsing to the invoked workflow

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
        if ( empty( $key ) )
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
            PainlessRequest::GET,
            PainlessRequest::POST,
            PainlessRequest::DELETE,
            PainlessRequest::OPTIONS,
            PainlessRequest::CONNECT,
        );
    }

    /**
     * Setter for $this->method
     * @param string $method        the invoked method
     * @return PainlessRequest      returns itself to allow function chaining
     */
    protected function setMethod( $method )
    {
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
            if ( FALSE === $params ) throw new PainlessRequestException( "Malformed parameter string [$params]" );
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
            if ( ( $count % 2 ) !== 0 ) unset ( $params[ $count - 1 ] );

            for( $i = 0; $i < $count; $i += 2 )
            {
                $tmp[$params[$i]] = $tmp[$params[$i + 1]];
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

            if ( empty( $routes ) ) throw new PainlessRequestException( 'PS_CONFIG parameter parsing style can only be used if routes are properly set up (routes.uri.map)' );
            if ( empty( $this->workflow ) ) throw new PainlessRequestException( 'PS_CONFIG will not work if the invoking workflow was not instantiated before-hand. Please ensure that the router had instantiated the workflow and set a reference to it in $this->workflow before calling init( )' );

            $module     = $this->workflow->module;
            $workflow   = $this->workflow->name;
            $method     = $this->method;

            // construct the workflow key
            $key = "$module/$workflow";
            $map = array( );

            // see if there's a mapping for that workflow
            if ( ! isset( $routes[$key][$method] ) )
            {
                if ( ! isset( $routes[$key]['*'] ) )
                    throw new PainlessRequestException( "The route map [$map] is not found in the routes config. Please make sure the route map exists." );
                else
                    $map = $routes[$key]['*'];
            }
            else
            {
                $map = $routes[$key][$method];
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
            if ( empty( $this->workflow ) ) throw new PainlessRequestException( 'PS_DEFER will not work if the invoking workflow was not instantiated before-hand. Please ensure that the router had instantiated the workflow and set a reference to it in $this->workflow before calling init( )' );
            if ( ! method_exists( $this->workflow, 'processParams' ) ) throw new PainlessRequestException( 'PS_DEFER requires the invoking workflow [' . get_class( $this->workflow ) . '] to implement the method processParams( $params ) to work.' );

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

class PainlessRequestException extends ErrorException { }