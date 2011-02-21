<?php

class PainlessResponse
{
    /**-------------------------------------------------------------------------
     * The HTTP/REST status code of the response. Note that by default, PainlessModel
     * uses 200 (successful) and 400 (failed) only
     * @var int     a HTTP/REST status code
     */
    public $status      = 0;

    /**-------------------------------------------------------------------------
     * The message explaining the status of the response. When used internally,
     * this is the one that gets logged into the logs.
     * @var string  a message string explaining the status of the response
     */
    public $message     = '';

    /**-------------------------------------------------------------------------
     * The payload returned by the request. Usually an associative array so that
     * the view can easily compile it into the proper format.
     * @var mixed   the payload returned from the request
     */
    public $payload     = NULL;

    /**-------------------------------------------------------------------------
     * A reference to the invoking workflow
     * @var PainlessWorkflow    the workflow that invoked this response
     */
    public $parent      = NULL;

    public $workflow    = '';
    public $module      = '';
    public $method      = '';
    public $agent       = '';
    public $contentType = '';

    public function setWorkflow( $workflow )
    {
        $this->parent       = $workflow;
        $this->workflow     = $workflow->name;
        $this->module       = $workflow->module;
        $this->method       = $workflow->request->method;
        $this->agent        = $workflow->request->agent;
        $this->contentType  = $workflow->request->contentType;
    }

    public function set( $key, $data )
    {
        // Replace all keys with underscores
        if ( strpos( $key, '-' ) ) $key = str_replace( '-', '_', $key );

        $this->payload[$key] = $data;
    }

    public function get( $key )
    {
        return array_get( $this->payload, $key, FALSE );
    }
}