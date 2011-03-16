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
namespace Painless\System\Data\Adapter;
use Painless\System\Data\Dao as Dao;

class Memcached extends Dao
{

    public function open( $options = array( ) )
    {
        // do not proceed if memcached is not available
        if ( ! extension_loaded( 'memcached' ) )
        {
            throw new \MemcachedException( 'Memcached is not installed in your system.' );
        }

        $this->params = $options;

        // as usual, get the options from the config if not specified
        if ( empty( $this->params ) )
        {
            $config = \Painless::app( )->load( 'system/common/config' );
            $this->params = $config->get( 'memcached.*' );
        }

        // open a connection
        $host       = array_get( $this->params, 'memcached.host', FALSE );
        $port       = (int) array_get( $this->params, 'memcached.port', FALSE );
        $timeout    = (int) array_get( $this->params, 'memcached.timeout', FALSE );

        $this->conn = new Memcache;
        return $this->conn->connect( $host, $port, $timeout );
    }

    public function close( $options = array( ) )
    {
        if ( $this->isOpen( ) )
        {
            $this->conn->close( );
        }
    }

    public function get( $key )
    {
        return $this->execute( 'get', array( 'key' => $key ) );
    }

    public function set( $key, $value )
    {
        return $this->execute( 'set', array( 'key' => $key, 'value' => $value ) );
    }

    public function execute( $operation, $options = array( ) )
    {
        if ( ! $this->isOpen( ) ) { $this->open( ); }

        // make sure the operation string is all upper case as standardized by
        // all data adapters
        $operation = strtoupper( $operation );

        // process the operation
        if ( 'GET' === $operation )
        {
            return $this->conn->get( $options['key'] );
        }
        elseif ( 'SET' === $operation )
        {
            return $this->conn->set( $options['key'], $options['value'] );
        }
    }
}

class MemcachedException extends ErrorException { }