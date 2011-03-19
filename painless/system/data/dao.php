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

/**
 * PainlessDao is the base class for data adapters. The class can be used in many
 * different ways, but the preferred usage is to use it as a Data Mapper.
 *
 * Note that the DAO class is the ONLY class in the framework that uses an
 * underscore prefix for protected and private members. This is not a break in
 * the convention but rather, an intended exception. Specifically, since DAOs
 * are meant to be used like an object, where each property maps to a cell in the
 * data store. These public properties will automatically be sanitized, which is
 * a function provided by the DAO base class (since it applies to all DAOs), and
 * thus adding an underscore prefix to properties that are never meant to be
 * sanitized would provide a good mechanism to filter out unwanted operations.
 */

namespace Painless\System\Data;

abstract class Dao
{
    /**
     * Determines whether or not this adapter will be automatically killed upon
     * system shutdown.
     * @var boolean     
     */
    protected $_autoClose   = FALSE;

    /**
     * Holds the list of connection profiles for this DAO
     * @var array       
     */
    protected $_profiles    = array( );

    /**
     * Hold the current connection profile. Can be changed by calling useProfile( )
     * @var string      
     */
    protected $_currProfile = '';

    //--------------------------------------------------------------------------
    /**
     * Dao constructor to automatically register Dao::close( ) to run once this
     * object is destroyed.
     */
    public function __construct( )
    { 
        // if $autoClose is true, register it in the list of stuff to automatically
        // kill upon the end of the request
        if ( $this->_autoClose )
        {
            register_shutdown_function( array( $this, 'close' ) );
        }
    }
    
    //--------------------------------------------------------------------------
    /**
     * Gets or sets the profile with a connection object
     * @param string $name  the name of the profile to use
     * @param object $conn  a connection object
     * @return object       a connection to the database driver associated with this profile 
     */
    public function profile( $name, $conn = '' )
    {
        // Set the profile
        if ( ! empty( $conn ) )
        {            
            // Check if there's already a profile that exists
            if ( isset( $this->_profiles[$name] ) )
            {
                // If the profile is the current one, close it first and then
                // replace it with the connection object
                if ( $name === $this->_currProfile )
                    $this->close( );
                else
                    $this->_currProfile = $name;
                
                $this->_profiles[$name] = $conn;
                $this->_conn            = $conn;
            }
            // If the profile does not exists, add the connection there and set
            // it as the current profile
            else
            {
                // Check if there's a connection currently opened. Close it if so
                if ( ! empty( $this->_conn ) )
                    $this->close( );
                
                $this->_profiles[$name] = $conn;
                $this->_conn            = $conn;
                $this->_currProfile     = $name;
            }
            
            return $this->_conn;
        }
        // Switch to the profile
        else
        {
            // If it's already the current profile, and a connection exists there,
            // do nothing
            if ( $this->_currProfile === $name && ! is_null( $this->_conn ) )
            {
                return $this->_conn;
            }
            // Otherwise, if the profile exists but is not the current one, switch
            // to it
            elseif( isset( $this->_profiles[$name] ) )
            {
                $this->_currProfile = $name;
                $this->_conn = $this->_profiles[$name];
                return $this->_conn;
            }
            // Finally, if the profile does not exists at all, initialize it
            else
            {
                $this->init( $name );
                return $this->_conn;
            }
        }
        
        return FALSE;
    }

    /**-------------------------------------------------------------------------
     * lifecycle methods
     * -------------------------------------------------------------------------
     */
    abstract public function init( $profile = '' );
    abstract public function close( );

    /**-------------------------------------------------------------------------
     * direct query/execution methods
     * -------------------------------------------------------------------------
     */
    abstract public function execute( $cmd, $extra = array( ) );

    /**-------------------------------------------------------------------------
     * transactional methods
     * -------------------------------------------------------------------------
     */
    abstract public function start( );
    abstract public function end( $rollback = FALSE );
}