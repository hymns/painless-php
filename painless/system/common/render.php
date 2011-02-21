<?php

class PainlessRender
{
    protected $response = NULL;

    public function process( $response )
    {
        // set the response for use later 
        $this->response = $response;

        // perform pre render stuff
        $this->preRender( );

        // render the view
        $output = $this->render( );

        // perform post rendering stuff
        $this->postRender( $output );

        return $output;
    }

    protected function preRender( )
    {
        // validate the default content type
    }

    protected function render( )
    {
        // Localize the variables
        $response       = $this->response;
        $method         = $response->method;
        $module         = $response->module;
        $workflow       = $response->workflow;
        $contentType    = ( $response->contentType ) ? $response->contentType : 'html';

        $view = NULL;
        if ( empty( $module ) && empty( $workflow ) )
        {
            // Load the base class instead if no module or workflow is found
            $view = Painless::get( 'system/view/view' );
            $view->response = $response;
        }
        else
        {
            // Load the correct view
            $view = Painless::get( "view/$module/$workflow" );
            $view->response = $response;

            // Get the output from the view by running the appropriate method. Once
            // the method has been run, it's safe to assume that $view has properly
            // post-processed all necessary data and payload, and that now the
            // compiler should have enough information to render the output
            if ( $view->preProcess( ) ) $view->$method( );
            $view->postProcess( );
        }

        // Load the appropriate view compiler
        $compiler = Painless::get( "view-compiler/$contentType" );

        // If the content type is not suppoted, $compiler will be NULL. Handle
        // the error here
        if ( NULL === $compiler )
        {
            // Use the default HTML compiler
            $compiler = Painless::get( "view-compiler/html" );
        }

        // Return the processed output
        return $compiler->process( $view );
    }

    protected function postRender( $output )
    {
        // let the implementer process stuff here
        return $output;
    }
}

class PainlessRenderException extends ErrorException { }
