<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class PainlessJsonCompiler extends PainlessBaseCompiler
{
    public function process( $view )
    {
        // Localize the data
        $response   = $view->response;
        $status     = $response->status;
        $message    = $response->message;
        $payload    = $response->payload;

        // Create the headers
        header( "HTTP1.1/ $status $message" );
        header( 'Content-Type: application/json' );

        $out = $this->handleStatus( $response );

        // if there's no handler for that status, simply return the payload
        if ( FALSE === $out ) $out = $payload;
        
        return json_encode( $out );
    }

    protected function handle302( $response )
    {
        header( 'Location: ' . $response->get( PainlessView::PATH ) );
        return FALSE;
    }
}