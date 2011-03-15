<?php

namespace Painless\System\Common;

class Worker
{
    /**
     * A request object of this workflow
     * @var PainlessRequest     an instance of the PainlessRequest object
     */
    public $request     = NULL;

    /**
     * A response object of this Workflow
     * @var PainlessResponse    an instance of the PainlessResponse object
     */
    public $response    = NULL;

    public function reset( )
    {
        $this->request = NULL;
        $this->response = NULL;
        
        return $this;
    }
    
    public function request( $method = '', $module = '', $controller = '', $params = array( ), $contentType = '', $agent = '' )
    {
        // Double check $method first. if it's an object, assume it's a request
        // object
        if ( is_object( $method ) )
        {
            // Return FALSE if $method is not a valid request object
            if ( ! ( $method instanceof \Painless\System\Workflow\Request ) )
                return FALSE;
            else
                $this->request = $method;
        }
        elseif( ! empty( $method ) )
        {
            // Remember to get a new instance of the request
            $request = \Painless::load( 'system/workflow/request', LP_LOAD_NEW );
            $request->agent( $agent );
            $request->method( $method );
            $request->module( $module );
            $request->controller( $controller );
            $request->params( $params );
            $request->contentType( $contentType );

            $this->request = $request;
        }

        return $this->request;
    }

    public function response( $status = 0, $message = '', $payload = NULL )
    {
        // Double check $status first. If it's an object, assume it's a response
        // object
        if ( is_object( $status ) )
        {
            // Return a 500 internal error if $status is not a valid response object
            if ( ! ( $status instanceof \Painless\System\Workflow\Response ) )
                $this->response( 500, '$status must only be an int or an instance of \Painless\System\Workflow\Response' );
            else
                $this->response = $status;
        }
        elseif ( ! empty( $status ) )
        {
            // Remember to get a new instance of the response
            $response = \Painless::load( 'system/workflow/response', LP_LOAD_NEW );
            $response->status( $status );
            $response->message( $message );
            $response->payload( $payload );

            $this->response = $response;
        }
        
        return $this->response;
    }
}