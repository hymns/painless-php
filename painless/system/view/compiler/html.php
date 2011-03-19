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
    const TPL_PATH = '_tpl_path';
    
    protected function pre( $view )
    {
        // Localize the data
        $request    =& $view->request;
        $response   =& $view->response;
        $status     = $response->status;
        $message    = $response->message;
        $payload    = $response->payload;

        // Create the headers
        $response->header( "HTTP1.1/ $status $message" );
        $response->header( 'Content-Type: text/html' );
        
        return $view;
    }
    
    public function process( $view )
    {
        $view = $this->pre( $view );
        
        // See if there's an appropriate status handler for this response code
        $view->response = $this->handleStatus( $view->request, $view->response );

        return $this->post( $view );
    }
    
    protected function post( $view )
    {
        // Localize the variables
        $response   =& $view->response;
        $path       = $response->get( self::TPL_PATH );
        
        // Load the file if it exists in the universe
        if ( file_exists( $path ) )
        {
            ob_start( );
            require $path;
            $response->payload = ob_end_flush( );
        }
        
        return $response;
    }
    
    protected function viewPath( $method, $module, $controller )
    {
        $core = \Painless::app( );
        
        // GET foo/bar should resolve to APP_PATH/foo/view/bar.get.tpl
        return $core->env( \Painless::APP_PATH ) . "$module/view/$controller.$method.tpl";
    }

    protected function handleStatus( $request, $response )
    {
        $out = parent::handleStatus( $request, $response );

        // Localize the data
        $module     = $request->module;
        $controller = $request->controller;
        $method     = $request->method;

        if ( TRUE === $out ) 
            $response->set( self::TPL_PATH, $this->viewPath( $method, $module, $controller ) );

        return $response;
    }

    protected function handle301( $request, $response )
    {
        $response->header( 'Location: ' . $response->get( 'target' ) );
        return $response;
    }

    protected function handle302( $request, $response )
    {
        $response->header( 'Location: ' . $response->get( 'target' ) );
        return $response;
    }

    protected function handle404( $request, $response )
    {
        $response->set( self::TPL_PATH, \Painless::app( )->env( \Painless::APP_PATH ) . 'view/error-404.tpl' );
        return $response;
    }
    
    protected function handle500( $request, $response )
    {
        $response->set( self::TPL_PATH, \Painless::app( )->env( \Painless::APP_PATH ) . 'view/error-500.tpl' );
        return $response;
    }
}