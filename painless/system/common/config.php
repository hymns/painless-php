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

class Config
{
    protected $config = array( );

    public function get( $namespace )
    {
        // check if the config file has been loaded or not
        if ( empty( $this->config ) ) $this->init( );

        // try to load the value first
        $result = array_get( $this->config, $namespace, NULL );

        // see if the last segment of the namespace is an asterisk or not
        $pos = strrpos( trim( $namespace ), '*' );
        if ( $pos !== FALSE && $result === NULL )
        {
            // if so, then we should return everything that matches the particular
            // expression in the config array. First, extract the part of the
            // namespace prior to the asterisk symbol
            $partToMatch = substr( $namespace, 0, $pos );

            $matches = array( );
            foreach ( $this->config as $key => $config )
            {
                if ( strpos( $key, $partToMatch ) !== FALSE ) $matches[$key] = $config;
            }

            return $matches;
        }
        else
        {
            return $result;
        }
    }

    public function getAll( )
    {
        // lazy init
        if ( empty( $this->config ) ) $this->init( );

        return $this->config;
    }

    protected function init( )
    {
        $configPath = '';
        $profile = '';
        $aclPath = '';

        // first, check if a deployment profile is issued
        $profile = Painless::$PROFILE;

        // get the engine's implementor path if possible
        if ( ! empty( Painless::$IMPL_PATH ) )
        {
            $configPath = Painless::$IMPL_PATH . 'config/' . Painless::$IMPL_NAME;
            $aclPath    = Painless::$IMPL_PATH . 'config/' . Painless::$IMPL_NAME;
            $routesPath = Painless::$IMPL_PATH . 'config/' . Painless::$IMPL_NAME;

            if ( $profile ) $configPath .= '.' . $profile;

            $configPath .= EXT;
            $aclPath    .= '.acl' . EXT;
            $routesPath .= '.route' . EXT;
        }

        // check if the config path is correct
        if ( file_exists( $configPath ) )
        {
            require_once( $configPath );

            if ( !isset( $config ) )
                throw new ConfigException( 'Unable to find the config array in [' . $configPath . ']' );

            $this->config = $config;

            // clean up because $config is going to be recycled after this
            unset( $config );
        }
        else
        {
            throw new ConfigException( 'Invalid config file [' . $configPath . ']' );
        }

        // load the acl array too
        if ( file_exists( $aclPath ) )
        {
            require_once( $aclPath );

            if ( isset( $config ) )
            {
                $this->config = array_merge( $this->config, $config );
                unset( $config );
            }
        }

        // load the routes array too
        if ( file_exists( $routesPath ) )
        {
            require_once( $routesPath );

            if ( isset( $config ) )
            {
                $this->config = array_merge( $this->config, $config );
            }
        }
    }
}