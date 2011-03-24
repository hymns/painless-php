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
namespace Painless\System\Common;

class Render
{
    //--------------------------------------------------------------------------
    /**
     * Processes the request and response to compile the right output
     * @param Request $request      a request object
     * @param Response $response    a response object
     * @return mixed                an output compiled by the appropriate compiler 
     */
    public function process( $request, $response )
    {
        // Localize the variables
        $view           = NULL;
        $method         = $request->method;
        $module         = $request->module;
        $controller     = $request->controller;
        $contentType    = $request->contentType;

        // If $module and $controller exists, find the View controller
        if ( ! empty( $module ) && ! empty( $controller ) )
        {
            // Load the correct view
            $view = \Painless::load( "view/$module/$controller" );
            
            // If the view does not exists, return a 404
            if ( ! empty( $view ) )
            {
                $view->request  = $request;
                $view->response = $response;

                // Get the output from the view by running the appropriate method. Once
                // the method has been run, it's safe to assume that $view has properly
                // post-processed all necessary data and payload, and that now the
                // compiler should have enough information to render the output
                if ( $view->preProcess( ) ) $view->$method( );
                $view->postProcess( );
            }
        }
        
        // At this point, if the view is NULL, create a default view just to
        // pacify the angry view compiler.
        if ( empty( $view ) )
        {
            $view = \Painless::load( 'system/view/view', \Painless::LP_LOAD_NEW );
            $view->request = $request;
            $view->response = $response;
        }

        // Load the appropriate view compiler
        $compiler = \Painless::load( "system/view/compiler/$contentType" );

        // If the content type is not suppoted, $compiler will be NULL. Handle
        // the error here
        if ( NULL === $compiler )
        {
            // Use the default HTML compiler
            $compiler = \Painless::load( "system/view/compiler/html" );
        }

        // Return the processed output as a response;
        return $compiler->process( $view );
    }
}
