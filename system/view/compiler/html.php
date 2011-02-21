<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class PainlessHtmlCompiler extends PainlessBaseCompiler
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
        header( 'Content-Type: text/html' );

        // See if there's an appropriate status handler for this response code
        $path = $this->handleStatus( $response );

        // Load the file if it exists in the universe
        if ( file_exists( $path ) )
        {
            ob_start( );
            require $path;
            return ob_end_flush( );
        }

        return '';
    }

    protected function handleStatus( $response )
    {
        $out = parent::handleStatus( $response );

        // Localize the data
        $module     = $response->module;
        $workflow   = $response->workflow;
        $method     = $response->method;

        if ( FALSE === $out ) $out = "$module/tpl/$workflow.$method.tpl";

        return $out;
    }

    protected function handle301( $response )
    {
        header( 'Location: ' . $response->get( PainlessView::PATH ) );
        return FALSE;
    }

    protected function handle302( $response )
    {
        return IMPL_PATH . 'view/error-301.tpl';
    }

    protected function handle404( $response )
    {
        return IMPL_PATH . 'view/error-404.tpl';
    }
}