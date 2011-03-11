<?php

namespace Sample\Module\Hello\Controller;

class HelloWorldController extends \Painless\System\Workflow\Controller
{
    public function get( )
    {
        // Get the parameters
        $name = $this->request( )->param( 'name' );

        // Send back the response
        return $this->response( 200, 'OK', array( $name ) );
    }
}