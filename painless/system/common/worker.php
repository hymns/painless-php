<?php

namespace Painless\System\Common;

class Worker
{

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
    
    public function request( )
    {
        
    }

    public function response( $status = 0, $message = '', $payload = NULL )
    {
        // Double check $status first. If it's not an INT, assume it's a response
        // object
        if ( ! is_int( $status ) && ! empty( $status ) )
        {
            \Painless::load( 'system/workflow/response', LP_DEF_ONLY );
            if ( ! ( $status instanceof Response ) )
                throw new ErrorException( '$status must only be an int or an instance of PainlessResponse' );

            $this->response = $status;
        }
        elseif ( ! empty( $status ) )
        {
            // remember to get a new instance of the response
            $response = \Painless::load( 'system/workflow/response', LP_LOAD_NEW );
            $response->status = (int) $status;
            $response->message = $message;
            $response->payload = $payload;

            $this->response = $response;
        }
        return $this->response;
    }
}