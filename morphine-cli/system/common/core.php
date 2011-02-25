<?php
/**
 * Morphine - the command line toolkit for Painless PHP to take away the pain
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
 * @package     Morphine
 * @author      Tan Long Zheng (soggie) <ruben@rendervault.com>
 * @copyright   2011 Tan Long Zheng (soggie) <ruben@rendervault.com>
 * @license     BSD 3 Clause (New BSD)
 * @link        http://painless-php.com
 */

class MorphineCore extends PainlessCore
{
    public $argv = array( );

    protected $alias = array(
        'con'           => 'config',
        'conf'          => 'config',
        'gen'           => 'generate',
        'ex'            => 'execute',
        'up'            => 'update',
        'in'            => 'install',
        '--help'        => 'help',
        '/?'            => 'help',
    );

    protected $ops = array(
        'config'        => 'GET config/main',       // Get/set morphine's configuration
        'generate'      => 'GET generate/main',     // Generate modules, workflows, configs, etc
        'execute'       => 'GET execute/main',      // Executes packages and workflows
        'update'        => 'GET update/main',       // Updates the system from the package server
        'install'       => 'GET install/main',      // Installs packages
        'test'          => 'GET test/main',         // Runs the test suite
        'help'          => 'GET help/main',         // Shows the help file
    );

    protected function processArgs( )
    {
        // Localize the variable
        $argv = $this->argv;

        // Extract the operation from there
        $operation = strtolower( array_get( $argv, 1, FALSE ) );

        // If there's no operation, show the help file by default
        if ( empty( $operation ) ) $operation = 'help';

        // Check if it's a valid operation
        $legalOps = array_keys( $this->ops );
        $alias = array_keys( $this->alias );
        if ( ! in_array( $operation, $legalOps ) && ! in_array( $operation, $alias ) )
        {
            $operation = 'help';
        }
        elseif ( in_array( $operation, $alias ) )
        {
            $operation = $this->alias[$operation];
        }

        // Now that we have the proper operation, map it to the proper process
        $process = $this->ops[$operation];

        // Append the rest of the arguments into the process string
        if ( count( $argv ) > 2 )
        {
            // Append a backslash at the end of the process
            $process .= '/';

            // Remove the first two elements
            $argv = array_splice( $argv, 2 );

            // Strip any backslashes from the argument array
            foreach( $argv as $i => $v )
            {
                if ( FALSE !== strpos( $v, '/' ) )
                    $argv[$i] = str_replace( '/', '', $v );
            }

            $process .= implode( '/', $argv );
        }

        return $process;
    }

    public function dispatch( )
    {
        // check and load the router
        $router = Painless::get( 'system/common/router' );

        $uri = $this->processArgs( );

        try
        {
            // let the router process the business logic
            $response = $router->process( $uri );
        }
        catch( PainlessWorkflowNotFoundException $e )
        {
            // construct a 404 response
            $response = Painless::get( 'system/workflow/response', LP_LOAD_NEW );
            $response->status = 404;
            $response->message = 'Unable to locate workflow';
        }
        catch( ErrorException $e )
        {
            $response = Painless::get( 'system/workflow/response', LP_LOAD_NEW );
            $response->status = 500;
            $response->message = $e->getMessage( );
        }

        // pass the control to the renderer
        $render = Painless::get( 'system/common/render' );
        $output = $render->process( $response );

        return $output;
    }
}