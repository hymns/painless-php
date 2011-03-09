<?php

/**
 * Painless PHP - the painless path to development
 *
 * Copyright (c) 2011, Tan Long Zheng (soggie)
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  * Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  * Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *  * Neither the name of Rendervault Solutions nor the names of its
 *    contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package     Painless PHP
 * @author      Tan Long Zheng (soggie) <ruben@rendervault.com>
 * @copyright   2011 Tan Long Zheng (soggie) <ruben@rendervault.com>
 * @license     BSD 3 Clause (New BSD)
 * @link        http://painless-php.com
 */

namespace Painless\System\Workflow;

class Response
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
        // Initialize the payload array if none available
        if ( empty( $this->payload ) ) $payload = array( );

        // Replace all keys with underscores
        if ( strpos( $key, '-' ) ) $key = str_replace( '-', '_', $key );

        $this->payload[$key] = $data;
    }

    public function get( $key )
    {
        return array_get( $this->payload, $key, FALSE );
    }
}