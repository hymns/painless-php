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
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class PainlessMysql extends PainlessDao
{
    // execute( ) $extra['return'] options
    const RET_ROW_COUNT     = 0;
    const RET_ID            = 1;
    const RET_ARRAY         = 2;
    const RET_ASSOC         = 3;
    const RET_OBJ           = 4;
    const RET_STMT          = 5;

    // execute( ) $extra['close'] options
    const STMT_CLOSE        = TRUE;
    const STMT_IGNORE       = FALSE;

    // execute( ) $extra option shorthands for common operations
    protected $_opInsert    = array( 'return' => self::RET_ID,          'close' => self::STMT_CLOSE );
    protected $_opUpdate    = array( 'return' => self::RET_ROW_COUNT,   'close' => self::STMT_CLOSE );
    protected $_opSelect    = array( 'return' => self::RET_ASSOC,       'close' => self::STMT_CLOSE );
    protected $_opDelete    = array( 'return' => self::RET_ROW_COUNT,   'close' => self::STMT_CLOSE );

    protected $_logRetData  = FALSE;

    protected $_conn        = NULL;

    /**
     * @var string	$prep	the list of SQL in the last transaction
     */
    protected $_log         = array( );

    protected $_tranId      = '';

    public function __construct( )
    {
        // log the return data of all queries during development. This is turned
        // off in production to save memory
        if ( 'development' === DEPLOY_PROFILE ) $this->_logRetData = TRUE;
    }

    /**--------------------------------------------------------------------------------------------------------------------------------------------------
     * utility methods
     * --------------------------------------------------------------------------------------------------------------------------------------------------
     */

    /**
     * Builds a WHERE query string from an associative array
     * @param array $criteria   an associative array containing the list of criterias
     * @return string           the WHERE query string
     */
    protected function buildWhere( $criteria )
    {
        // lazy init the connection
        if ( NULL == $this->_conn ) $this->init( );

        $ret = '';

        // $criteria can come in two flavors: a query string (which does not need
        // to be "built" (except sanitized), or an array, which is automatically
        // deconstructed into a string conjoined using the "AND" bitwise operator.
        if ( is_array( $criteria ) )
        {
            // convert $criteria, an associative array whose key is the field name and
            // value is the actual value, into an indexed array that can be imploded
            // into a WHERE query string
            $where = array( );
            foreach ( $criteria as $field => $cond )
            {
                if ( is_string( $field ) )
                {
                    $where[] = '`' . $field . '`=' . $this->_conn->quote( $cond ) . '';
                }
                else
                {
                    $where[] = $cond;
                }
            }

            // create the WHERE query string here
            if ( !empty( $where ) )
            {
                $ret = ' WHERE ' . implode( ' AND ', $where );
            }        
        }
        else
        {
            // don't need to escape anything here as it'll automatically be done
            // in execute( )
            $ret = $criteria;
        }

        return $ret;
    }

    /**
     * Builds a LIMIT clause using an offset and limit
     * @param int $offset   the offset to begin search with. If $limit is 0, this would instead be used as the limit
     * @param int $limit    the limit of records to search for.
     * @return string       the LIMIT clause
     */
    protected function buildLimit( $offset = 0, $limit = 0 )
    {
        if ( FALSE === $offset && FALSE === $limit ) return '';

        // lazy init the connection
        if ( NULL == $this->_conn ) $this->init( );

        // $offset and $limit can be used in 2 ways: $limit only, or $offset +
        // $limit. This expectation is made with the use case where $offset cannot
        // execute without a $limit. This is similar to how Mysql handles the
        // LIMIT clause.
        if ( $limit === 0 && $offset !== 0 )
        {
            $ret = "LIMIT $offset";
        }
        else
        {
            $ret = "LIMIT $offset, $limit";
        }

        return $ret;
    }

    /**
     * Builds a ORDER query string from an associative array
     * @param array $criteria   an associative array containing the list of criterias
     * @return string           the ORDER query string
     */
    protected function buildOrder( $criteria )
    {
        // lazy init the connection
        if ( NULL == $this->_conn ) $this->init( );

        $ret = '';

        // $criteria can come in two flavors: a query string (which does not need
        // to be "built" (except sanitized), or an array, which is automatically
        // deconstructed into a string conjoined using the "AND" bitwise operator.
        if ( is_array( $criteria ) )
        {
            // convert $criteria, an associative array whose key is the field name and
            // value is the actual value, into an indexed array that can be imploded
            // into a WHERE query string
            $order = array( );
            foreach ( $criteria as $field )
            {
                $order[] = $this->_conn->quote( $field );
            }

            // create the WHERE query string here
            if ( !empty( $order ) )
            {
                $ret = ' ORDER BY ' . implode( ', ', $order );
            }
        }
        else
        {
            // don't need to escape anything here as it'll automatically be done
            // in execute( )
            $ret = $criteria;
        }

        return $ret;
    }

    /**--------------------------------------------------------------------------------------------------------------------------------------------------
     * lifecycle methods
     * --------------------------------------------------------------------------------------------------------------------------------------------------
     */

    /**
     * Initializes the MYSQL connection via PDO
     * @return boolean      always return TRUE
     */
    public function init( $profile = '' )
    {
        $config = Painless::get( 'system/common/config' );
        $connParams = array( );
        $prefix = 'mysql.';

        // If profile is not provided, assuming that we're using the profile "default"
        if ( empty( $profile ) )
        {
            $profile    = 'default';
            $connParams = $config->get( 'mysql.*' );
        }
        else
        {
            // Get the list of profiles from the config file
            $profiles   = $config->get( 'mysql.profiles' );
            if ( empty( $profiles ) ) throw new PainlessMysqlException( 'Profiles not properly defined in the config file' );

            // Only get the profile if there's a match
            if ( ! array_values( $profile ) ) throw new PainlessMysqlException( "The specified profile [$profile] is not defined in the config file" );
            $connParams = $config->get( "mysql.$profile.*" );
            $prefix .= $profile . '.';
        }

        // get the parameters
        $host       = array_get( $connParams, $prefix . 'host', FALSE );
        $db         = array_get( $connParams, $prefix . 'database', FALSE );
        $user       = array_get( $connParams, $prefix . 'username', FALSE );
        $pass       = array_get( $connParams, $prefix . 'password', FALSE );

        // try to connect to the database
        $connString = 'mysql:host=' . $host . ';dbname=' . $db;

        // the line below might throw an exception, which should be caught by
        // the exception handler in the engine, so no point catching it here
        $this->addProfile( $profile, new PDO( $connString, $user, $pass ) );
        $this->useProfile( $profile );

        // make sure the PDO connection throws an exception during development
        // mode
        if ( DEPLOY_PROFILE === 'development' )
            $this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return TRUE;
    }

    /**
     * Closes the active connection
     * @return boolean      always return TRUE
     */
    public function close( )
    {
        if ( ! empty( $this->_conn ) ) $this->_conn->close( );

        return TRUE;
    }

    /**--------------------------------------------------------------------------------------------------------------------------------------------------
     * direct query/execution methods
     * --------------------------------------------------------------------------------------------------------------------------------------------------
     */

    /**
     * Executes an SQL query and returns the results.
     *
     * Options supported by $extra:
     *  'return' => specifies the return value
     *      self::RET_ROW_COUNT = returns an integer count of the number of rows affected by the query
     *      self::RET_ID        = returns the last inserted ID
     *      self::RET_ARRAY     = fetches an indexed array
     *      self::RET_ASSOC     = fetches an associative array where the key is the column name
     *      self::RET_OBJ       = fetches an object where the property is the column name
     *      self::RET_STMT      = returns the PDO statement
     *
     *  'close' => specifies whether or not to close the statement
     *      self::STMT_CLOSE    = closes the statement after execution
     *      self::STMT_IGNORE   = don't close the statement after execution (usually used in conjunction with RET_STMT)
     *
     *  'bind' => an array of bound parameters where the key is the field name and the value is the datum value
     *
     * @param string $cmd   the command to execute (usually a plain SQL string)
     * @param array $extra  any extra commands to add to the execution
     * @return mixed        varies depending on the return type specified in $extra['return']
     */
    public function execute( $cmd, $extra = array( ) )
    {
        // lazy init the connection
        if ( NULL == $this->_conn ) $this->init( );
        
        // Trigger the execution notification
        Beholder::notify( 'mysql.execute.pre', array( $cmd, $extra ) );

        // create a PDOStatement object
        $stmt = $this->_conn->query( $cmd );
        if ( FALSE === $stmt )
        {
            return FALSE;
        }

        // $extra['return'] will tell us what stuff to return, so let's parse it
        // now
        $retType = (int) array_get( $extra, 'return', self::RET_ROW_COUNT );
        $ret = NULL;

        switch( $retType )
        {
            case self::RET_ROW_COUNT :
                $ret = (int) $stmt->rowCount( );
                break;

            case self::RET_ID :
                $ret = $this->_conn->lastInsertId( );
                break;

            case self::RET_ARRAY :
                $stmt->setFetchMode( PDO::FETCH_NUM );
                $ret = $stmt->fetchAll( );
                break;

            case self::RET_ASSOC :
                $stmt->setFetchMode( PDO::FETCH_ASSOC );
                $ret = $stmt->fetchAll( );
                break;

            case self::RET_OBJ :
                $stmt->setFetchMode( PDO::FETCH_OBJ );
                $ret = $stmt->fetchAll( );
                break;

            case self::RET_STMT :
                $ret = $stmt;

            default :
                throw new PainlessMysqlException( 'Unsupported return type [' . $retType . ']' );
        }

        // Close the statement if necessary
        $closeStmt = (boolean) array_get( $extra, 'close', self::STMT_CLOSE );
        if ( $closeStmt && ! ( $ret instanceof PDOStatement ) ) $stmt->closeCursor( );

        // Only log stuff if we're in development mode
        if ( DEV === DEPLOY_PROFILE )
        {
            // save the query into the transaction log if this is a transaction
            $log = array( $cmd, $extra );

            // save the return data if required
            $log[] = $ret;

            // if this is a transaction, group all the logged queries together. Otherwise
            // log them as single queries
            if ( '' !== $this->_tranId )
                $this->_log[$this->_tranId][] = $log;
            else
                $this->_log[date( 'Y-m-d H:i:s [u]' )] = $log;
        }
        
        // Trigger the post execution notification
        Beholder::notify( 'mysql.execute.post', $ret );

        return $ret;
    }

    /**
     * A shorthand for executing SELECT statements
     * @param string $sql   the sql query to execute
     * @return array        the results returned from the database
     */
    public function select( $sql )
    {
        return $this->execute( $sql, $this->_opSelect );
    }

    public function insert( $sql )
    {
        return $this->execute( $sql, $this->_opInsert );
    }

    public function update( $sql )
    {
        return $this->execute( $sql, $this->_opUpdate );
    }

    public function delete( $sql )
    {
        return $this->execute( $sql, $this->_opDelete );
    }

    /**--------------------------------------------------------------------------------------------------------------------------------------------------
     * transactional methods
     * --------------------------------------------------------------------------------------------------------------------------------------------------
     */

    /**
     * Starts a transaction and logs the details of the transacion
     */
    public function start( )
    {
        // lazy init the connection
        if ( NULL == $this->_conn ) $this->open( );

        $this->_conn->beginTransaction( );

        // log the current transaction if needed
        if ( DEV === DEPLOY_PROFILE )
        {
            $this->_tranId = date( 'Y-m-d H:i:s [u]' );
            $this->_log[$this->_tranId] = array( );
        }
    }

    /**
     * Ends a transaction, along with the logs of the transaction
     * @param boolean $rollback     if set to TRUE, will perform a rollback instead of commit
     */
    public function end( $rollback = FALSE )
    {
        // lazy init the connection
        if ( NULL == $this->_conn ) $this->open( );

        if ( ! $rollback )
        {
            // commits the data and if failed, roll it back
            if ( ! $this->_conn->commit( ) ) $this->end( TRUE );
        }
        else
        {
            $this->_conn->rollBack( );
        }

        // log the current transaction if needed
        if ( DEV === DEPLOY_PROFILE )
        {
            // always reset the transaction ID to prevent any further changes to the
            // transaction log
            $this->_tranId = '';
        }
    }

    /**--------------------------------------------------------------------------------------------------------------------------------------------------
     * default ORM methods
     * --------------------------------------------------------------------------------------------------------------------------------------------------
     */
    /**
     * Adds a record into the database
     * @param array $opt    an array of options ( NOT SUPPORTED )
     */
    public function add( $opt = array( ) )
    {
        // lazy init the connection
        if ( NULL == $this->_conn ) $this->init( );
        
        if ( FALSE === $this->_tableName )
            throw new PainlessMysqlException( 'When $_tableName is set to FALSE, ActiveRecord functions (add(), find(), save() and remove()) cannot be used' );

        if ( empty( $this->_tableName ) )
            throw new PainlessMysqlException( '$_tableName is not defined. Please set $_tableName to use ActiveRecord functions' );

        // Get the list of public properties of this DAO
        $props = get_object_vars( $this );

        $fields = array( );
        $values = array( );

        // Create the fields and values array
        foreach( $props as $p )
        {
            $v = $this->_conn->quote( $this->$p );
            $p = camel_to_underscore( $p );
            if ( ( ! empty( $p ) && $p[0] !== '_' ) || $p !== $this->_primaryKey )
            {
                $fields[] = $p;
                $values[] = $v;
            }
        }

        // Implode the two arrays into strings
        $fields = implode( ',', $fields );
        $values = implode( ',', $values );

        // Build the insert query
        $sql = "INSERT INTO `$this->_tableName` ( $fields ) VALUES ( $values )";

        return $this->insert( $sql );
    }

    /**
     * Gets a record in the database
     * @param array $opt    an array of options, each an associative array where:
     *                      options:
     *                          where (assoc array)
     *                              - key           = the name of the field to search for
     *                              - value         = the value of the field to search for
     */
    public function get( $opt = array( ) )
    {
        // lazy init the connection
        if ( NULL == $this->_conn ) $this->init( );

        if ( FALSE === $this->_tableName )
            throw new PainlessMysqlException( 'When $_tableName is set to FALSE, ActiveRecord functions (add(), find(), save() and remove()) cannot be used' );

        if ( empty( $this->_tableName ) )
            throw new PainlessMysqlException( '$_tableName is not defined. Please set $_tableName to use ActiveRecord functions' );

        // Build the WHERE, LIMIT and ORDER query
        $where = $this->buildWhere( array_get( $opt, 'where', array( ) ) );

        $fields = get_object_vars( $this );

        // Convert all properties from camel case to underscore convention
        foreach( $fields as $i => $f )
        {
            if ( $i[0] === '_' )
            {
                unset( $fields[$i] );
                continue;
            }

            $fields[$i] = '`' . camel_to_underscore( $i ) . '`';
        }

        $fields = implode( ',', $fields );

        // Build the SELECT query
        $sql = "SELECT $fields FROM `$this->_tableName` $where LIMIT 1";

        $results = $this->select( $sql );
        if ( ! empty( $results ) )
        {
            $results = $results[0];

            foreach( $results as $field => $value )
            {
                $field = underscore_to_camel( $field );
                $this->$field = $value;
            }

            $results = $this;
        }

        return $results;
    }

    /**
     * Searches for a record in the database
     * @param array $opt    an array of options, each an associative array where:
     *                      options:
     *                          where (assoc array)
     *                              - key           = the name of the field to search for
     *                              - value         = the value of the field to search for
     *                          limit (indexed array)
     *                              - 0             = the offset
     *                              - 1             = the max
     *                          order (assoc array)
     *                              - key           = the field to order by
     *                              - value         = either DESC or ASC
     */
    public function find( $opt = array( ) )
    {
        // lazy init the connection
        if ( NULL == $this->_conn ) $this->init( );
        
        if ( FALSE === $this->_tableName )
            throw new PainlessMysqlException( 'When $_tableName is set to FALSE, ActiveRecord functions (add(), find(), save() and remove()) cannot be used' );

        if ( empty( $this->_tableName ) )
            throw new PainlessMysqlException( '$_tableName is not defined. Please set $_tableName to use ActiveRecord functions' );

        // Build the WHERE, LIMIT and ORDER query
        $where = $this->buildWhere( array_get( $opt, 'where', array( ) ) );
        list( $limit, $offset ) = array_get( $opt, 'limit', array( FALSE, FALSE ) );
        $limit = $this->buildLimit( $limit, $offset );
        $order = $this->buildOrder( array_get( $opt, 'order', array( ) ) );

        $fields = get_object_vars( $this );

        // Convert all properties from camel case to underscore convention
        foreach( $fields as $i => $f )
        {   
            if ( $i[0] === '_' )
            {
                unset( $fields[$i] );
                continue;
            }

            $fields[$i] = '`' . camel_to_underscore( $i ) . '`';
        }

        $fields = implode( ',', $fields );

        // Build the SELECT query
        $sql = "SELECT $fields FROM `$this->_tableName` $where $order $limit";

        $results = $this->select( $sql );
        if ( ! empty( $results ) )
        {
            foreach( $results as $i => $row )
            {
                $obj = new $this;
                foreach( $row as $field => $value )
                {
                    $field = underscore_to_camel( $field );
                    $obj->$field = $value;
                }

                $results[$i] = $obj;
                unset( $obj );
            }
        }

        return $results;
    }

    /**
     * Updates the database with a record.
     * @param array $opt    an array of options ( NOT SUPPORTED )
     */
    public function save( $opt = array( ) )
    {
        // lazy init the connection
        if ( NULL == $this->_conn ) $this->init( );
        
        if ( FALSE === $this->_tableName )
            throw new PainlessMysqlException( 'When $_tableName is set to FALSE, ActiveRecord functions (add(), find(), save() and remove()) cannot be used' );

        if ( empty( $this->_tableName ) )
            throw new PainlessMysqlException( '$_tableName is not defined. Please set $_tableName to use ActiveRecord functions' );

        if ( FALSE === $this->_primaryKey )
            throw new PainlessMysqlException( 'When $_primaryKey is set to FALSE, ActiveRecord functions save() and remove() cannot be used' );

        if ( empty( $this->_primaryKey ) )
            throw new PainlessMysqlException( '$_primaryKey is not defined. Please set $_primaryKey to use save() and remove() functions' );

        // Get the list of public properties of this DAO
        $props = get_object_vars( $this );

        $fields = array( );
        $pk = '';

        // Create the fields and values array
        foreach( $props as $p )
        {
            if ( ! empty( $p ) && $p[0] !== '_' && $p !== $this->_primaryKey )
            {
                $fields[] = "`$p` = " . $this->_conn->quote( $this->$p );
            }
            elseif( $p === $this->_primaryKey )
            {
                $pk = $this->$p;
            }
        }

        // Implode the two arrays into strings
        $fields = implode( ',', $fields );

        // Build the update query
        $sql = "UPDATE `$this->_tableName` SET $fields WHERE `$this->_primaryKey` = '$pk'";

        return $this->update( $sql );
    }

    /**
     * Deletes a record from the DB using a primary key
     * @param array $opt    an array of options ( NOT SUPPORTED )
     */
    public function remove( $opt = array( ) )
    {
        if ( FALSE === $this->_tableName )
            throw new PainlessMysqlException( 'When $_tableName is set to FALSE, ActiveRecord functions (add(), find(), save() and remove()) cannot be used' );

        if ( empty( $this->_tableName ) )
            throw new PainlessMysqlException( '$_tableName is not defined. Please set $_tableName to use ActiveRecord functions' );

        if ( FALSE === $this->_primaryKey )
            throw new PainlessMysqlException( 'When $_primaryKey is set to FALSE, ActiveRecord functions save() and remove() cannot be used' );

        if ( empty( $this->_primaryKey ) )
            throw new PainlessMysqlException( '$_primaryKey is not defined. Please set $_primaryKey to use save() and remove() functions' );

        $pk = $this->_primaryKey;
        $pk = $this->$pk;

        // Build the delete query
        $sql = "DELETE FROM `$this->_tableName` WHERE `$this->_primaryKey` = '$pk' LIMIT 1";

        return $this->delete( $sql );
    }
}

class PainlessMysqlException extends ErrorException { }