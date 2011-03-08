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

class Observer
{
    public $obj         = NULL;
    public $func        = '';
    public $removeMe    = FALSE;

    public function run( & $params = array( ) )
    {
        // Localize the variables
        $obj    = $this->obj;
        $func   = $this->func;

        // If $obj is null, it's a simple function call
        if ( empty( $obj ) && function_exists( $func ) )
        {
            $status = $func( $params );
        }
        // $obj exists and $func is a public method inside $obj
        elseif ( ! empty( $obj ) && method_exists( $obj, $func ) )
        {
            $status = $obj->$func( $params );
        }
        // The object or the function doesn't exist. Mark it for garbage
        // collection.
        else
        {
            $this->removeMe = TRUE;
        }

        // Always return a TRUE/FALSE
        return ( ! empty( $status ) );
    }
}

class Beholder
{
    public static $events = array( );
    
    public static function init( )
    {
        // Check if there's a trigger configuration
        $IMPL_PATH = Painless::$IMPL_PATH;
        $config = Painless::get( 'system/common/config' );
        
        $triggers = $config->get( 'triggers.*' );
        
        if ( ! empty( $triggers ) )
        {
            foreach( $triggers as $name => $callback )
            {
                self::register( $name, $callback );
            }
        }
    }

    public static function register( $name, $callback )
    {
        // Check if there's any observers already registered for that event
        if ( ! isset( self::$events[$name] ) )
        {
            self::$events[$name] = array( );
        }

        // Check if $callback is a function or an object
        if ( is_array( $callback ) )
        {
            list( $obj, $func ) = $callback;
            $callback = new Observer;
            $callback->obj = $obj;
            $callback->func = $func;
        }
        else
        {
            $obj = new Observer;
            $obj->func = $callback;
            $callback = $obj;
        }

        // Add the observer to the event
        self::$events[$name][] = $callback;
    }

    public static function notify( $name, & $params = array( ), $checkStatus = FALSE )
    {
        // Only run this if there're observers set for this event
        if ( isset( self::$events[$name] ) )
        {
            $roboticsBay = self::$events[$name];

            // Run through the observers collection
            foreach( $roboticsBay as $i => $observer )
            {
                $status = $observer->run( $params );
                
                // If $observer->removeMe is TRUE, remove it from the list of observers
                if ( $observer->removeMe )
                    unset( $roboticsBay[$i] );

                // If $checkStatus is enabled, stop the processing on an error
                if ( $checkStatus && ! $status )
                {
                    return FALSE;
                }
            }
        }

        return TRUE;
    }
    
    public static function notifyUntil( $name, & $params = array( ) )
    {
        return self::notify( $name, $params, TRUE );
    }
}