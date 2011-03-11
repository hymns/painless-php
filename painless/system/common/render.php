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
    protected $response = NULL;

    public function process( $response )
    {
        // set the response for use later 
        $this->response = $response;

        // render the view
        $output = $this->render( );

        return $output;
    }

    protected function render( )
    {
        // Trigger the pre-render action. Don't proceed if it failed.
        if ( ! Beholder::notifyUntil( 'render.pre' ) ) return '';

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
            $view = \Painless::load( 'system/view/view' );
            $view->response = $response;
        }
        else
        {
            // Load the correct view
            $view = \Painless::load( "view/$module/$workflow" );
            $view->response = $response;

            // Get the output from the view by running the appropriate method. Once
            // the method has been run, it's safe to assume that $view has properly
            // post-processed all necessary data and payload, and that now the
            // compiler should have enough information to render the output
            if ( $view->preProcess( ) ) $view->$method( );
            $view->postProcess( );
        }

        // Load the appropriate view compiler
        $compiler = \Painless::load( "view-compiler/$contentType" );

        // If the content type is not suppoted, $compiler will be NULL. Handle
        // the error here
        if ( NULL === $compiler )
        {
            // Use the default HTML compiler
            $compiler = \Painless::load( "view-compiler/html" );
        }

        // Return the processed output
        $output = $compiler->process( $view );

        // Trigger the post-render action
        Beholder::notify( 'render.post', $output );
        
        return $output;
    }
}

class RenderException extends \ErrorException { }
