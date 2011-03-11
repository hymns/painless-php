<?php

namespace Sample\Module\Hello\View;

class HelloWorldView extends \Painless\System\View\View
{
    public function get( )
    {
        // Get the payload from the controller's request
        $name = $this->request( )->param( 'name' );
        
    }
}