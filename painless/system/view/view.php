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
