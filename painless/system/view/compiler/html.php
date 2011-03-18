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

namespace Painless\System\View\Compiler;

class Html extends \Painless\System\View\Compiler\Base
{
    public function process( $view )
    {
        // Localize the data
        $request    = $view->request;
        $response   = $view->response;
        $status     = $response->status;
        $message    = $response->message;
        $payload    = $response->payload;

        // Create the headers
        header( "HTTP1.1/ $status $message" );
        header( 'Content-Type: text/html' );

        // See if there's an appropriate status handler for this response code
        $path = $this->handleStatus( $request, $response );

        // Load the file if it exists in the universe
        if ( file_exists( $path ) )
        {
            ob_start( );
            require $path;
            return ob_end_flush( );
        }

        return '';
    }

    protected function handleStatus( $request, $response )
    {
        $out = parent::handleStatus( $request, $response );

        // Localize the data
        $module     = $request->module;
        $controller = $request->controller;
        $method     = $request->method;

        if ( FALSE === $out ) $out = "$module/tpl/$controller.$method.phtml";

        return $out;
    }

    protected function handle301( $request, $response )
    {
        header( 'Location: ' . $response->get( 'target' ) );
        return FALSE;
    }

    protected function handle302( $request, $response )
    {
        return \Painless::app( )->env( \Painless::APP_PATH ) . 'view/error-301.tpl';
    }

    protected function handle404( $request, $response )
    {
        return \Painless::app( )->env( \Painless::APP_PATH ) . 'view/error-404.tpl';
    }
    
    protected function handle500( $request, $response )
    {
        return \Painless::app( )->env( \Painless::APP_PATH ) . 'view/error-500.tpl';
    }
}