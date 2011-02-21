<?php

class PainlessView
{
    const PATH = '_tpl';

    public $response = NULL;

    /**
     * Redirects either to an external resource or to an internal workflow
     * @param string $path  the path to redirect to
     */
    protected function redirect( $path )
    {
        $path = strtolower( $path );

        // check if this is an external path
        if ( stripos( $path, 'http://' ) === FALSE && stripos( $path, 'https://' ) === FALSE )
        {
            $path = APP_URL . $path;
        }

        // rebuild the response
        $response           = & $this->response;
        $response->status   = 302;
        $response->message  = "Redirect";
        $response->assign( self::PATH, $path );
    }

    /**
     * Forward is used when the intended output exists in the same view but different
     * method. For example, it is used for form validation. When a user submits a form,
     * most likely it will become a POST or PUT method instead of GET (which returns
     * the form itself). Instead of creating a view template that is identical with the
     * GET method (which would then violate the DRY principle), a forward( ) should be
     * called to "callback" the original get( ) function in the view.
     * @param string $method the method of the same view to forward to
     */
    protected function forward( $method = 'get' )
    {
        // Change the current response's method
        $method = strtolower( $method );
        $this->response->method = $method;

        return $this->$method( );
    }

    /**
     * Assigns a value to the payload
     * @param string $key   the key to assign to the response's payload
     * @param mixed $value  the value to assign to the response's payload
     */
    protected function assign( $key, $value )
    {
        $this->response->set( $key, $value );
    }

    /**
     * Pre processes the view
     * @return boolean  TRUE if the preProcessing succeeds
     */
    public function preProcess( )
    {
        return TRUE;
    }

    /**
     * Post processes the view
     */
    public function postProcess( )
    {
        // Localize the variables
        $response   = $this->response;
        $module     = $response->module;
        $workflow   = $response->workflow;
        $method     = $response->method;

        // Construct the PATH value
        $this->assign( self::PATH, "$module/tpl/$workflow.$method.tpl" );
    }
}
