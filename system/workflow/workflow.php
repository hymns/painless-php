<?php
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
     * Creates a new request and attach to this workflow
     * @param string $method        the method/action invoked by the request
     * @param array $params         a parameter array to initialize the workflow with
     * @param string $contentType   the content type that is invoked
     * @param string $agent         the invoking agent
     * @return PainlessWorkflow     returns itself to facilitate method chaining
     */
    public function setRequest( $method, $params, $contentType = PainlessRequest::HTML, $agent = 'painless' )
    {
        // remember to get a new instance of the request
        $request = Painless::get( 'system/workflow/request', LP_LOAD_NEW );

        // use the defaults if $contentType is empty
        if ( empty( $contentType ) ) $contentType = PainlessRequest::HTML;
        if ( empty( $agent ) ) $agent = 'painless';

        // initialize the request
        $request->parent = & $this;
        $request->init( $method, $params, $contentType, $agent );

        $this->request = $request;
        return $this;
    }

    /**
     * Creates a response object. Usually called at the end of any invoked methods
     * @param int $status       a valid HTTP/REST status code
     * @param string $message   the message explaining the status of the response
     * @param mixed $payload    the payload of the workflow
     * @return PainlessResponse a response object
     */
    public function setResponse( $status, $message, $payload = array( ) )
    {
        // remember to get a new instance of the response
        $response = Painless::get( 'system/workflow/response', LP_LOAD_NEW );

        $response->setWorkflow( $this );
        $response->status = (int) $status;
        $response->message = $message;
        $response->payload = $payload;

        $this->response = $response;
        return $this;
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
        foreach( $methods as $i => $method )
        {
            if ( ! method_exists( $this, $method ) )
                unset( $methods[$i] );
        }

        return $methods;
    }
}

class PainlessWorkflowException extends ErrorException { }