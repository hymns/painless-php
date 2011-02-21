<?php

/**
 * PainlessDao is the base class for data adapters. The class can be used in many
 * different ways, but the preferred usage is to use it as a Data Mapper.
 */

abstract class PainlessDao
{
    /**
     * Determines whether or not this adapter will be automatically killed upon
     * system shutdown.
     * @var boolean     if set to TRUE, it'll be registered in the shutdown function
     */
    protected $autoClose = FALSE;

    public function __construct( )
    { 
        // if $autoClose is true, register it in the list of stuff to automatically
        // kill upon the end of the request
        if ( $this->autoClose )
        {
            register_shutdown_function( array( $this, 'close' ) );
        }
    }

    /**
     * lifecycle methods
     */
    abstract public function init( );
    abstract public function close( );

    /**
     * direct query/execution methods
     */
    abstract public function execute( $cmd, $extra = array( ) );

    /**
     * DAO methods
     */
    abstract public function add( $opt = array( ) );                            // adds a new record to the data store
    abstract public function find( $opt = array( ) );                           // finds a record from the data store
    abstract public function save( $opt = array( ) );                           // saves or updates the record into the data store
    abstract public function delete( $opt = array( ) );                         // deletes the record in the data store

    /**
     * transactional methods
     */
    abstract public function start( );
    abstract public function end( $rollback = FALSE );
}