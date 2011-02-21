<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class PainlessMemcached extends PainlessDao
{
    protected $params = array( );

    public function open( $options = array( ) )
    {
        // do not proceed if memcached is not available
        if ( ! extension_loaded( 'memcached' ) )
        {
            throw new PainlessMemcachedDaoException( 'Memcached is not installed in your system.' );
        }

        $this->params = $options;

        // as usual, get the options from the config if not specified
        if ( empty( $this->params ) )
        {
            $config = Painless::get( 'system/common/config' );
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

class PainlessMemcachedException extends ErrorException { }