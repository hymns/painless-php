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

class ConfigManagerWorkflow extends PainlessWorkflow
{
    /**
     * Retrieves the value of a config key
     * @return void
     */
    public function get( )
    {
        // Get the Config model
        $model = \Painless::app( )->load( 'model/config/manager' );

        // A correctly formed URI would look like this: GET test/config/key/[config-key]
        $key = $this->request->getParam( 'key' );

        // Try to get the config key
        $response = $model->getConfig( $key );

        // Handle the key status
        if ( $response->status === 200 )
        {
            $this->response( $response );
            return;
        }
        else
        {
            $this->response( 404, 'Config key not found' );
            return;
        }
    }

    /**
     * Adds a new config key into morphine
     * @return void
     */
    public function put( )
    {
        // Get the Config model
        $model = \Painless::app( )->load( 'model/config/manager' );

        // Get the key and value
        $key    = $this->request->getParam( 'key' );
        $value  = $this->request->getParam( 'value' );

        // Try to add a new config key
        $response = $model->addConfig( $key, $value );

        // Handle the return status
        if ( $response->status === 201 )
        {
            $this->response( 201, 'Created' );
            return;
        }
        else
        {
            $this->response( $response );
            return;
        }
    }

    /**
     * Edits a config key in morphine
     * @return void
     */
    public function post( )
    {
        // Get the config model
        $model = \Painless::app( )->load( 'model/config/manager' );

        // Get the key to update and the value
        $key    = $this->request->getParam( 'key' );
        $value  = $this->request->getParam( 'value' );

        // Try to update the config key
        $response = $model->updateConfig( $key, $value );

        // Handle the return status
        if ( $response->status === 200 )
        {
            $this->response( 200, 'OK' );
            return;
        }
        else
        {
            $this->response( $response );
            return;
        }
    }
}