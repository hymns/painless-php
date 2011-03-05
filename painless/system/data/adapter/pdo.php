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

class PainlessPdo extends PainlessDao
{
    // execute( ) $extra['return'] options
    const RET_ROW_COUNT             = 0;
    const RET_ID                    = 1;
    const RET_ARRAY                 = 2;
    const RET_ASSOC                 = 3;
    const RET_OBJ                   = 4;
    const RET_STMT                  = 5;

    // execute( ) $extra['close'] options
    const STMT_CLOSE                = TRUE;
    const STMT_IGNORE               = FALSE;

    protected $_conn                = NULL;

    protected static $queryBuilder  = NULL;
    protected static $queryLog      = array( );
    protected static $currTranId    = '';

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
        if ( Painless::isProfile( DEV ) )
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

        // close the statement if necessary
        $closeStmt = (boolean) array_get( $extra, 'close', self::STMT_CLOSE );
        if ( $closeStmt && ! ( $ret instanceof PDOStatement ) ) $stmt->closeCursor( );

        // save the query into the transaction log if this is a transaction
        $log = array( $cmd, $extra );

        // save the return data if required
        $logData = Painless::isProfile( DEV );
        if ( $logData )
            $log[] = $ret;

        // if this is a transaction, group all the logged queries together. Otherwise
        // log them as single queries
        if ( '' !== self::$currTranId )
            self::$queryLog[self::$currTranId][] = $log;
        else
            self::$queryLog[date( 'Y-m-d H:i:s [u]' )] = $log;

        return $ret;
    }

    /**
     * A shorthand for executing SELECT statements
     * @param string $sql   the sql query to execute
     * @return array        the results returned from the database
     */
    public function executeSelect( $sql, $return = self::RET_ASSOC, $close = self::STMT_CLOSE )
    {
        return $this->execute( $sql, array( 'return' => $return, 'close' => $close ) );
    }

    public function executeInsert( $sql, $return = self::RET_ID, $close = self::STMT_CLOSE )
    {
        return $this->execute( $sql, array( 'return' => $return, 'close' => $close ) );
    }

    public function executeUpdate( $sql, $return = self::RET_ROW_COUNT, $close = self::STMT_CLOSE )
    {
        return $this->execute( $sql, array( 'return' => $return, 'close' => $close ) );
    }

    public function executeDelete( $sql, $return = self::RET_ROW_COUNT, $close = self::STMT_CLOSE )
    {
        return $this->execute( $sql, array( 'return' => $return, 'close' => $close ) );
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

        // log the current transaction
        self::$currTranId = date( 'Y-m-d H:i:s [u]' );
        self::$queryLog[self::$currTranId] = array( );
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

        // always reset the transaction ID to prevent any further changes to the
        // transaction log
        self::$currTranId = '';
    }

    /**--------------------------------------------------------------------------------------------------------------------------------------------------
     * default ORM methods
     * --------------------------------------------------------------------------------------------------------------------------------------------------
     */
    /**
     * Adds a record into the database
     * @param array $opt    an array of options ( NOT SUPPORTED )
     */
    public function create( $opt = array( ) )
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
        foreach( $props as $p => $v )
        {
            // Skip over any unset fields
            if ( NULL === $v || $p[0] === '_' || $p === $this->_primaryKey ) continue;

            $v = $this->_conn->quote( $v );
            $p = camel_to_underscore( $p );
            
            $fields[] = '`' . $p . '`';
            $values[] = $v;
        }

        // Implode the two arrays into strings
        $fields = implode( ',', $fields );
        $values = implode( ',', $values );

        // Build the insert query
        $sql = "INSERT INTO `$this->_tableName` ( $fields ) VALUES ( $values )";

        $this->id = $this->executeInsert( $sql );
        if ( empty( $this->id ) )
            return FALSE;

        return TRUE;
    }

    /**
     * Gets a record in the database
     * @param string $where the WHERE clause in string
     * @return boolean      returns TRUE if it successfully finds the record, FALSE if otherwise
     */
    public function get( $where = '' )
    {
        // lazy init the connection
        if ( NULL == $this->_conn ) $this->init( );

        if ( FALSE === $this->_tableName )
            throw new PainlessMysqlException( 'When $_tableName is set to FALSE, ActiveRecord functions' );

        if ( empty( $this->_tableName ) )
            throw new PainlessMysqlException( '$_tableName is not defined. Please set $_tableName to use ActiveRecord functions' );

        // Grab all properties in this object
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

        // Append the WHERE clause to $where if none exists
        if ( ! empty( $where ) && FALSE === stripos( $where, 'WHERE' ) )
            $where = "WHERE $where";

        // Build the SELECT query
        $sql = "SELECT $fields FROM `$this->_tableName` $where LIMIT 1";
        $results = $this->executeSelect( $sql );
        if ( ! empty( $results ) )
        {
            $results = $results[0];

            foreach( $results as $field => $value )
            {
                $field = underscore_to_camel( $field );
                $this->$field = $value;
            }

            $results = $this;

            return TRUE;
        }

        return FALSE;
    }

    /**
     * Searches for a record in the database
     * @param string $where   WHERE conditions
     * @param string $order   ORDER BY conditions
     * @param string $group   GROUP BY conditions
     * @param string $limit   LIMIT conditions
     */
    public function find( $where = '', $order = '', $group = '', $limit = '' )
    {
        // lazy init the connection
        if ( NULL == $this->_conn ) $this->init( );

        if ( FALSE === $this->_tableName )
            throw new PainlessMysqlException( 'When $_tableName is set to FALSE, ActiveRecord functions cannot be used' );

        if ( empty( $this->_tableName ) )
            throw new PainlessMysqlException( '$_tableName is not defined. Please set $_tableName to use ActiveRecord functions' );

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

        // Prepend a WHERE to $where if none available
        if ( ! empty( $where ) && FALSE === stripos( $where, 'WHERE' ) )
            $where = "WHERE $where";

        // Prepend an ORDER BY to $order if none available
        if ( ! empty( $order ) && FALSE === stripos( $order, 'ORDER BY' ) )
            $order = "ORDER BY $order";

        // Prepend a GROUP BY to $group if none available
        if ( ! empty( $group ) && FALSE === stripos( $group, 'GROUP BY' ) )
            $group = "GROUP BY $group";

        // Prepend a LIMIT to $limit if none available
        if ( ! empty( $limit ) && FALSE === stripos( $limit, 'LIMIT' ) )
            $limit = "LIMIT $limit";

        // Build the SELECT query
        $sql = "SELECT $fields FROM `$this->_tableName` $where $order $group $limit";

        $results = $this->executeSelect( $sql );
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
     * @param string $where     the WHERE clause
     */
    public function update( $where = '' )
    {
        // lazy init the connection
        if ( NULL == $this->_conn ) $this->init( );

        if ( FALSE === $this->_tableName )
            throw new PainlessMysqlException( 'When $_tableName is set to FALSE, ActiveRecord functions cannot be used' );

        if ( empty( $this->_tableName ) )
            throw new PainlessMysqlException( '$_tableName is not defined. Please set $_tableName to use ActiveRecord functions' );

        if ( FALSE === $this->_primaryKey && empty( $where ) )
            throw new PainlessMysqlException( 'When $_primaryKey is set to FALSE (and no WHERE clause is passed in), ActiveRecord functions save() and remove() cannot be used' );

        if ( empty( $this->_primaryKey ) || ( empty( $where ) && NULL === $this->{$this->_primaryKey} ) )
            throw new PainlessMysqlException( '$_primaryKey is not defined (and no WHERE clause is passed in). Please set $_primaryKey to use save() and remove() functions' );

        // Get the list of public properties of this DAO
        $props = get_object_vars( $this );

        $fields = array( );

        // Create the fields and values array
        foreach( $props as $f => $v )
        {
            // Don't proceed if the value of the field is NULL, to enable selective
            // field updates
            if ( $f[0] === '_' || $f === $this->_primaryKey || NULL === $v )
                continue;

            $fields[] = "`$f` = " . $this->_conn->quote( $v );
        }

        // Implode the two arrays into strings
        $fields = implode( ',', $fields );

        // If no $where is provided as a parameter, use the primary key instead
        if ( empty( $where ) )
        {
            $pk = $this->_primaryKey;
            $where = "WHERE `$this->_primaryKey` = " . $this->{$pk};
        }

        // Prepend the WHERE in $where if needed
        if ( FALSE === stripos( $where, 'WHERE' ) )
            $where = "WHERE $where";

        // Build the update query
        $sql = "UPDATE `$this->_tableName` SET $fields $where";

        return $this->executeUpdate( $sql );
    }

    /**
     * Deletes a record from the DB using a primary key
     * @param array $opt    an array of options ( NOT SUPPORTED )
     */
    public function delete( $where = '' )
    {
        if ( FALSE === $this->_tableName )
            throw new PainlessMysqlException( 'When $_tableName is set to FALSE, ActiveRecord functions cannot be used' );

        if ( empty( $this->_tableName ) )
            throw new PainlessMysqlException( '$_tableName is not defined. Please set $_tableName to use ActiveRecord functions' );

        if ( FALSE === $this->_primaryKey && empty( $where ) )
            throw new PainlessMysqlException( 'When $_primaryKey is set to FALSE (and no WHERE clause is passed in), ActiveRecord functions save() and remove() cannot be used' );

        if ( empty( $this->_primaryKey ) || ( empty( $where ) && NULL === $this->{$this->_primaryKey} ) )
            throw new PainlessMysqlException( '$_primaryKey is not defined (and no WHERE clause is passed in). Please set $_primaryKey to use save() and remove() functions' );

        // If no $where is provided as a parameter, use the primary key instead
        if ( empty( $where ) )
        {
            $pk = $this->_primaryKey;
            $where = "WHERE `$this->_primaryKey` = " . $this->{$pk};
        }

        // Prepend the WHERE in $where if needed
        if ( FALSE === stripos( $where, 'WHERE' ) )
            $where = "WHERE $where";

        // Build the delete query
        $sql = "DELETE FROM `$this->_tableName` $where";

        return $this->executeDelete( $sql );
    }

    /**--------------------------------------------------------------------------------------------------------------------------------------------------
     * SQL query factory
     * --------------------------------------------------------------------------------------------------------------------------------------------------
     */
    public function sql( )
    {
        if ( empty( self::$queryBuilder ) )
            self::$queryBuilder = Painless::get( 'system/data/sql/sql-factory' );

        return self::$queryBuilder;
    }

    /**--------------------------------------------------------------------------------------------------------------------------------------------------
     * self-sanitization
     * --------------------------------------------------------------------------------------------------------------------------------------------------
     */
    protected function sanitizeForDb( )
    {
        // lazy init the connection
        if ( NULL == $this->_conn ) $this->init( );

        foreach( $this as $field => $value )
        {
            $value = $this->_conn->quote( $value );
        }
    }
}

class PainlessMysqlException extends ErrorException { }