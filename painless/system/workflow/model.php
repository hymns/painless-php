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

class PainlessModel
{
    const SUCCESS = 200;
    const CREATED = 201;
    const FAILURE = 400;

    protected $response = NULL;
    
    /**
     * A shorthand to return a properly formed response object to the calling
     * workflow.
     * @param int $status       the HTTP status of the response object
     * @param string $message   a message to return to the caller
     * @param mixed $data       usually, either an array or a string
     * @return array            an array where the key 'status' is TRUE or FALSE, and 'data' is the returned data
     */
    protected function response( $status, $message = '', $data = array( ) )
    {
        // Double check $status first. If it's not an INT, assume it's a response
        // object
        if ( ! is_int( $status ) )
        {
            Painless::get( 'system/workflow/response', LP_DEF_ONLY );
            if ( ! ( $status instanceof PainlessResponse ) )
                throw new PainlessWorkflowException( '$status must only be an int or an instance of PainlessResponse' );

            $this->response = $status;
        }
        else
        {
            // remember to get a new instance of the response
            $response = Painless::get( 'system/workflow/response', LP_LOAD_NEW );
            $response->status = (int) $status;
            $response->message = $message;
            $response->payload = $data;

            $this->response = $response;
        }
        return $this->response;
    }

    protected function validateNull( $v )                  { return empty( $v ); }
    protected function validateEmail( $v )                 { return ( filter_var( $v, FILTER_VALIDATE_EMAIL ) !== FALSE ); }
    protected function validateEquals( $v1, $v2 )          { return ( $v1 == $v2 ); }
    protected function validateIdentical( $v1, $v2 )       { return ( $v1 === $v2 ); }
}
