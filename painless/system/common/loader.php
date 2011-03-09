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

defined( 'NSTOK' ) or define( 'NSTOK', '/' );
defined( 'CNTOK' ) or define( 'CNTOK', '-' );

define( 'LP_DEF_CORE', 1 );         // load the definition for the core component
define( 'LP_DEF_EXT', 2 );          // load the definition for the extended component
define( 'LP_CACHE_CORE', 4 );       // instantiate the core component and cache it
define( 'LP_CACHE_EXT', 8 );        // instantiate the extended component and cache it
define( 'LP_RET_CORE', 16 );        // returns the core component. If this cannot be done, it'll return a NULL
define( 'LP_RET_EXT', 32 );         // returns the extended component. If this cannot be done, it'll return the core component instead
define( 'LP_SKIP_CACHE_LOAD', 64 ); // skip the cache lookup inside the loader

define( 'LP_ALL', 63 );             // short for LP_DEF_CORE | LP_DEF_EXT | LP_CACHE_CORE | LP_CACHE_EXT | LP_RET_CORE | LP_RET_EXT
define( 'LP_LOAD_NEW', 127 );       // short for LP_DEF_CORE | LP_DEF_EXT | LP_CACHE_CORE | LP_CACHE_EXT | LP_RET_CORE | LP_RET_EXT | LP_SKIP_CACHE_LOAD
define( 'LP_DEF_ONLY', 3 );         // short for LP_DEF_CORE | LP_DEF_EXT
define( 'LP_EXT_ONLY', 42 );        // short for LP_DEF_EXT | LP_CACHE_EXT | LP_RET_EXT
define( 'LP_CORE_ONLY', 21 );       // short for LP_DEF_CORE | LP_CACHE_CORE | LP_RET_CORE

class Loader
{
    public $name = '';
    public $base = '';
    public $impl = '';

    protected static $cache = array( );

    public function __construct( )
    {
        // Set the system paths here for reference
        $this->base = Painless::$CORE_PATH;
        $this->impl = Painless::$IMPL_PATH;
        $this->name = Painless::$IMPL_NAME;
    }

    /**
     * Loads a component
     * @param string $ns    the component's namespace
     * @param int $opt      loading parameters
     * @return mixed        returned value depends on the loading parameters
     */
    public function get( $ns, $opt = LP_ALL )
    {
        // If LP_LOAD_NEW is not defined, try to see if the component has already
        // been cached and return that instead if so
        if ( ! empty( $ns ) && isset( self::$cache[$ns] ) && ! ( $opt & LP_SKIP_CACHE_LOAD ) )
            return self::$cache[$ns];

        // Explode the namespace string into an array to make it easier to work
        // with
        $nsa = explode( NSTOK, $ns );
        if ( empty( $nsa ) || count( $nsa ) <= 1 ) throw new LoaderException( 'Namespace cannot be NULL or a malformed format [' . $ns . ']' );

        // The component type uses a dash convention, thus the need for this conversion
        $comType = dash_to_camel( $nsa[0] );

        // Grab the load information from the respective component type handler
        $meta = $this->$comType( $nsa, $ns );

        // Declare the variables and constants to use as a good engineering
        // practice :)
        $comBase        = NULL;
        $comExt         = NULL;
        $isDefCore      = (bool) ( $opt & LP_DEF_CORE );
        $isDefExt       = (bool) ( $opt & LP_DEF_EXT );
        $isCacheCore    = (bool) ( $opt & LP_CACHE_CORE );
        $isCacheExt     = (bool) ( $opt & LP_CACHE_EXT );
        $isRetCore      = (bool) ( $opt & LP_RET_CORE );
        $isRetExt       = (bool) ( $opt & LP_RET_EXT );

        // Load the definition of the base class, if possible
        if ( FALSE !== $meta['base_path']
                && file_exists( $meta['base_path'] )
                && $isDefCore
                && ! class_exists( $meta['base_obj'], FALSE ) )
            require_once $meta['base_path'];

        // Instantiate the core class
        if ( class_exists( $meta['base_obj'], FALSE ) && ( $isRetCore || $isCacheCore ) )
        {
            $comBase = new $meta['base_obj'];

            // If caching is required (by enabling the LP_CACHE_CORE flag), save
            // the instantiated object into Painless's central cache
            if ( $isCacheCore ) self::$cache[$ns] = $comBase;
        }

        // Load the definition of the ext class, if possible
        if ( file_exists( $meta['load_path'] ) 
                && $isDefExt
                && ! class_exists( $meta['load_obj'], FALSE ) )
            require_once $meta['load_path'];

        // Instantiate the ext class
        if ( class_exists( $meta['load_obj'], FALSE ) && ( $isRetExt || $isCacheExt ) )
        {
            $comExt = new $meta['load_obj'];

            // If caching is required (by enabling the LP_CACHE_EXT flag), save
            // the instantiated object into Painless's central cache (overwrite
            // LP_CACHE_CORE if possible
            if ( $isCacheExt ) self::$cache[$ns] = $comExt;
        }

        // Now that we have done all the loading bit, figure out what to return.

        // If both LP_RET_CORE and LP_RET_EXT are not set, then no values are
        // expected to be returned at all
        if ( ! ( $isRetCore && $isRetExt ) )
            return NULL;

        // If $comExt exists and LP_RET_EXT is set, immediately return $comExt
        elseif ( $comExt && $isRetExt )
            return $comExt;

        // If $comExt is not set but $comBase and LP_RET_CORE are set, return
        // $comBase instead
        elseif ( empty( $comExt ) && $comBase && $isRetCore )
            return $comBase;

        // Don't know what to return, so we'll be nice and return an exception
        // instead. :)
        //throw new LoaderException( 'Unable to load the component [' . $ns . ']' );
        return NULL;
    }

    /**
     * Loads a system component
     * @param array $nsa    an array of tokens from the namespace string
     * @param string $ns    the namespace string in full
     * @return array        the meta data on how to load the component
     */
    protected function system( $nsa, $ns )
    {
        $cn = dash_to_pascal( end( $nsa ) );
        
        return array(            
            'load_path' => $this->impl . $ns . EXT,
            'load_obj'  => ucwords( $this->name ) . $cn,
            
            'base_path' => $this->base . $ns . EXT,
            'base_obj'  => 'Painless' . $cn,
        );
    }

    /**
     * Loads a library
     * @param array $nsa    an array of tokens from the namespace string
     * @param string $ns    the namespace string in full
     * @return array        the meta data on how to load the component
     */
    protected function library( $nsa, $ns )
    {
        $fn = end( $nsa );

        $cn = dash_to_pascal( $fn );

        return array(
            'load_path' => $this->impl . $ns . '/' . $fn . EXT,
            'load_obj'  => ucwords( $this->name ) . $cn,

            'base_path' => $this->base . $ns . '/' . $fn . EXT,
            'base_obj'  => 'Painless' . $cn,
        );
    }

    /**
     * Loads a module component
     * @param array $nsa    an array of tokens from the namespace string
     * @param string $ns    the namespace string in full
     * @return array        the meta data on how to load the component
     */
    protected function module( $nsa, $ns )
    {
        // Throw an exception of $nsa does not meet the correct length req.
        if ( count( $nsa ) !== 2 ) throw new LoaderException( 'Module namespace should follow this format: module/[module]' );

        // Don't use the $ns string passed in. The module's name is ALWAYS the
        // last token of the $nsa array
        $ns = end( $nsa );
        $cn = dash_to_pascal( $ns );

        // Load the base object manually
        Painless::get( 'system/workflow/module', LP_DEF_ONLY );

        return array(            
            'load_path' => $this->impl . 'module/' . $ns . '/module' . EXT,
            'load_obj'  => $cn . 'Module',

            'base_path' => FALSE,
            'base_obj'  => FALSE,
        );
    }

    /**
     * Loads a workflow component
     * @param array $nsa    an array of tokens from the namespace string
     * @param string $ns    the namespace string in full
     * @return array        the meta data on how to load the component
     */
    protected function workflow( $nsa, $ns )
    {
        // Throw an exception of $nsa does not meet the correct length req.
        if ( count( $nsa ) !== 3 ) throw new LoaderException( 'Workflow namespace should follow this format: workflow/[module]/[workflow]' );

        // The second key in the $nsa array is always the module name, followed
        // by the workflow name
        $module = $nsa[1];
        $workflow = $nsa[2];

        // Implode the rest of the elements into a dash delimited format, and
        // then convert it into pascal form
        $cn = dash_to_pascal( $module . CNTOK . $workflow );

        // Load the base object manually
        Painless::get( 'system/workflow/workflow', LP_DEF_ONLY );

        return array(
            'load_path' => $this->impl . 'module/' . $module . '/workflow/' . $workflow . EXT,
            'load_obj'  => $cn . 'Workflow',

            'base_path' => FALSE,
            'base_obj'  => FALSE,
        );
    }

    /**
     * Loads a model component
     * @param array $nsa    an array of tokens from the namespace string
     * @param string $ns    the namespace string in full
     * @return array        the meta data on how to load the component
     */
    protected function model( $nsa, $ns )
    {
        // Throw an exception of $nsa does not meet the correct length req.
        if ( count( $nsa ) !== 3 ) throw new LoaderException( 'Model namespace should follow this format: model/[module]/[model]' );

        // The second key in the $nsa array is always the module name, followed
        // by the model name
        $module = $nsa[1];
        $model = $nsa[2];
        
        $cn = dash_to_pascal( $module . CNTOK . $model );

        // Load the base object manually
        Painless::get( 'system/workflow/model', LP_DEF_ONLY );

        return array(
            'load_path' => $this->impl . 'module/' . $module . '/model/' . $model . EXT,
            'load_obj'  => $cn . 'Model',

            'base_path' => FALSE,
            'base_obj'  => FALSE,
        );
    }

    /**
     * Loads a view component
     * @param array $nsa    an array of tokens from the namespace string
     * @param string $ns    the namespace string in full
     * @return array        the meta data on how to load the component
     */
    protected function view( $nsa, $ns )
    {
        // Throw an exception of $nsa does not meet the correct length req.
        if ( count( $nsa ) !== 3 ) throw new LoaderException( 'View namespace should follow this format: view/[module]/[view]' );
        
        // The second key in the $nsa array is always the module name, followed
        // by the view name
        $module = $nsa[1];
        $view = $nsa[2];

        $cn = dash_to_pascal( $module . CNTOK . $view );

        // load the base object manually
        Painless::get( 'system/view/view', LP_DEF_ONLY );

        return array(
            'load_path' => $this->impl . 'module/' . $module . '/view/' . $view . EXT,
            'load_obj'  => $cn . 'View',

            'base_path' => FALSE,
            'base_obj'  => FALSE,
        );
    }

    /**
     * Loads a view compiler
     * @param <type> $nsa
     * @param <type> $ns
     */
    protected function viewCompiler( $nsa, $ns )
    {
        // Throw an exception of $nsa does not meet the correct length req.
        if ( count( $nsa ) !== 2 ) throw new LoaderException( 'View Compiler namespace should follow this format: view-compiler/[type]' );

        // The second key in the $nsa array is always the compiler type
        $type = $nsa[1];

        $cn = dash_to_pascal( $type );

        // Load the base object manually
        Painless::get( 'system/view/compiler/base', LP_DEF_ONLY );

        return array(
            'load_path' => $this->impl . 'system/view/compiler/' . $type . EXT,
            'load_obj'  => ucwords( $this->name ) . $cn . 'Compiler',

            'base_path' => $this->base . 'system/view/compiler/' . $type . EXT,
            'base_obj'  => 'Painless' . $cn . 'Compiler',
        );
    }

    /**
     * Loads a dao component
     * @param array $nsa    an array of tokens from the namespace string
     * @param string $ns    the namespace string in full
     * @return array        the meta data on how to load the component
     */
    protected function dao( $nsa, $ns )
    {
        // Throw an exception of $nsa does not meet the correct length req.
        if ( count( $nsa ) < 3 ) throw new LoaderException( 'DAO namespace should follow this format: dao/[module]/[dao]/[adapter] or dao/[module]/[dao]' );

        // The second key in the $nsa array is always the module name, followed
        // by the view name
        $module = $nsa[1];
        $dao = $nsa[2];
        $cn = dash_to_pascal( $module . CNTOK . $dao );

        if ( isset( $nsa[3] ) )
        {
            $adapter = $nsa[3];

            // Load the base object (the adapter) manually
            Painless::get( 'adapter/' . $adapter, LP_DEF_ONLY );

            $dao .= CNTOK . $adapter;
            $cn .= CNTOK . $adapter;
        }

        return array(
            'load_path' => $this->impl . 'module/' . $module . '/dao/' . $dao . EXT,
            'load_obj'  => $cn,

            'base_path' => FALSE,
            'base_obj'  => FALSE,
        );
    }

    /**
     * Loads a data adapter component
     * @param array $nsa    an array of tokens from the namespace string
     * @param string $ns    the namespace string in full
     * @return array        the meta data on how to load the component
     */
    protected function adapter( $nsa, $ns )
    {
        // Throw an exception of $nsa does not meet the correct length req.
        if ( count( $nsa ) !== 2 ) throw new LoaderException( 'DAO namespace should follow this format: adapter/[adapter-type]' );

        $cn = dash_to_pascal( $nsa[1] );

        // If by any chance $nsa[1] is 'dao', then don't proceed
        if ( $nsa[1] == 'dao' )
            return new LoaderException( '"dao" is not an adapter' );

        // Get PainlessDao definition
        Painless::get( 'system/data/dao', LP_DEF_ONLY );

        return array(
            'load_path' => $this->impl . 'system/data/adapter/' . $nsa[1] . EXT,
            'load_obj'  => ucwords( $this->name ) . $cn,

            'base_path' => $this->base . 'system/data/adapter/' . $nsa[1] . EXT,
            'base_obj'  => 'Painless' . $cn,
        );
    }
}

class LoaderException extends \ErrorException { }