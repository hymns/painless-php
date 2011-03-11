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
 * Usage:
 * 
 *	To get a Core object as a singleton
 *		$core = \Painless::app( )->load( 'core://system/common/core' );
 * 
 *	To get just the Core's class definition
 *		\Painless::app( )->load( 'core//system/common/core', LP_DEF_ONLY );
 * 
 *	To get a fresh copy of the Core object
 *		$core = \Painless::app( )->load( 'core://system/common/core', LP_LOAD_NEW );
 */

namespace Painless\System\Common;

class Loader
{
    protected $appName  = '';
    protected $appPath  = '';
    protected $corePath = '';

    public static function init( $appName, $appPath, $corePath, $useExt = TRUE )
    {
        $loader = '\\Painless\\System\\Common\\Loader';
        if ( $useExt )
        {
            $path = $appPath . 'system/common/loader.php';
            if ( file_exists( $path ) ) include $path;

            $class = '\\' . dash_to_pascal( $appName ) . '\\System\\Common\\Loader';
            if ( class_exists( $class ) )
                $loader = $class;
        }
        $loader = new $loader;

        // Create the core and assign the environment variables. Remember to use
        // LP_LOAD_NEW to prevent loader from caching the Core instance, which
        // would fail because as of now the Core object does not exist yet!
        $core = $loader->load( 'system/common/core', LP_LOAD_NEW );
        $core->env( Core::APP_NAME, $appName );
        $core->env( Core::APP_PATH, $appPath );
        $core->env( Core::CORE_PATH, $corePath );

        // Set the PROFILE to DEV as a default
        $core->env( Core::PROFILE, DEV );

        // Cache the loader in the core
        $core->com( 'system/common/loader', $loader );

        return $core;
    }

    /**
     * Loads a component
     * @param string $ns    the component's namespace
     * @param int $opt      loading parameters
     * @return mixed        returned value depends on the loading parameters
     */
    public function get( $ns, $opt = LP_ALL )
    {
        // Localize the Core instance for multiple access
        $core = Painless::app( );

        // If LP_LOAD_NEW is not defined, try to see if the component has already
        // been cached and return that instead if so
        $com = $core->com( $ns );
        if ( ! empty( $ns ) && ! empty( $com ) && ! ( $opt & LP_SKIP_CACHE_LOAD ) )
            return $com;

        // Explode the namespace string into an array to make it easier to work
        // with
        $nsa = explode( '/', $ns );
        if ( empty( $nsa ) || count( $nsa ) <= 1 ) throw new ErrorException( 'Namespace cannot be NULL or a malformed format [' . $ns . ']' );

        // The component type uses a dash convention, thus the need for this conversion
        $comType = dash_to_camel( $nsa[0] );

        // Grab the load information from the respective component type handler
        $meta = $this->$comType( $core, $nsa, $ns );

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
        if ( FALSE !== $meta['basepath']
                && file_exists( $meta['basepath'] )
                && $isDefCore
                && ! class_exists( $meta['basename'], FALSE ) )
            require_once $meta['basepath'];

        // Instantiate the core class
        if ( class_exists( $meta['basename'], FALSE ) && ( $isRetCore || $isCacheCore ) )
        {
            $comBase = new $meta['basename'];

            // If caching is required (by enabling the LP_CACHE_CORE flag), save
            // the instantiated object into Painless's central cache
            if ( $isCacheCore ) $core->com( $ns, $comBase );
        }

        // Load the definition of the ext class, if possible
        if ( file_exists( $meta['extpath'] )
                && $isDefExt
                && ! class_exists( $meta['extname'], FALSE ) )
            require_once $meta['extpath'];

        // Instantiate the ext class
        if ( class_exists( $meta['extname'], FALSE ) && ( $isRetExt || $isCacheExt ) )
        {
            $comExt = new $meta['extname'];

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
     * @param Core $core    an instance of the Core object
     * @param array $nsa    an array of tokens from the namespace string
     * @param string $ns    the namespace string in full
     * @return array        the meta data on how to load the component
     */
    protected function system( $core, $nsa, $ns )
    {
        // The namespace ( $ns ) looks like this:
        // [system|vendor]/[category]/[component|[sub-category]/[sub-component]]
        $cns = '\\' . dash_to_namespace( $ns );
        return array(            
            'extpath'   => $core->env( Core::APP_PATH ) . $ns . EXT,
            'extname'   => '\\' . $core->env( Core::APP_NAME ) . $cns,
            
            'basepath'  => $core->env( Core::CORE_PATH ) . $ns . EXT,
            'basename'  => '\\Painless' . $cns,
        );
    }

    /**
     * Loads a library
     * @param Core $core    an instance of the Core object
     * @param array $nsa    an array of tokens from the namespace string
     * @param string $ns    the namespace string in full
     * @return array        the meta data on how to load the component
     */
    protected function library( $core, $nsa, $ns )
    {
        $fn = end( $nsa );

        $cn = dash_to_pascal( $fn );

        return array(
            'extpath'   => $core->env( Core::APP_PATH ) . $ns . '/' . $fn . EXT,
            'extname'   => $core->env( Core::APP_NAME ) . $cn,

            'basepath'  => $this->base . $ns . '/' . $fn . EXT,
            'basename'  => 'Painless' . $cn,
        );
    }

    /**
     * Loads a controller
     * @param Core $core    an instance of the Core object
     * @param array $nsa    an array of tokens from the namespace string
     * @param string $ns    the namespace string in full
     * @return array        the meta data on how to load the component
     */
    protected function controller( $core, $nsa, $ns )
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

        return array(
            'extpath' => $this->app . 'module/' . $module . '/workflow/' . $workflow . EXT,
            'extname'  => $cn . 'Workflow',

            'basepath' => FALSE,
            'basename'  => FALSE,
        );
    }

    /**
     * Loads a model component
     * @param Core $core    an instance of the Core object
     * @param array $nsa    an array of tokens from the namespace string
     * @param string $ns    the namespace string in full
     * @return array        the meta data on how to load the component
     */
    protected function model( $core, $nsa, $ns )
    {
        // Throw an exception of $nsa does not meet the correct length req.
        if ( count( $nsa ) !== 3 ) throw new LoaderException( 'Model namespace should follow this format: model/[module]/[model]' );

        // The second key in the $nsa array is always the module name, followed
        // by the model name
        $module = $nsa[1];
        $model = $nsa[2];
        
        $cn = dash_to_pascal( $module . CNTOK . $model );

        return array(
            'extpath' => $this->app . 'module/' . $module . '/model/' . $model . EXT,
            'extname'  => $cn . 'Model',

            'basepath' => FALSE,
            'basename'  => FALSE,
        );
    }

    /**
     * Loads a view component
     * @param Core $core    an instance of the Core object
     * @param array $nsa    an array of tokens from the namespace string
     * @param string $ns    the namespace string in full
     * @return array        the meta data on how to load the component
     */
    protected function view( $core, $nsa, $ns )
    {
        // Throw an exception of $nsa does not meet the correct length req.
        if ( count( $nsa ) !== 3 ) throw new LoaderException( 'View namespace should follow this format: view/[module]/[view]' );
        
        // The second key in the $nsa array is always the module name, followed
        // by the view name
        $module = $nsa[1];
        $view = $nsa[2];

        $cn = dash_to_pascal( $module . CNTOK . $view );

        return array(
            'extpath' => $this->app . 'module/' . $module . '/view/' . $view . EXT,
            'extname'  => $cn . 'View',

            'basepath' => FALSE,
            'basename'  => FALSE,
        );
    }

    /**
     * Loads a dao component
     * @param Core $core    an instance of the Core object
     * @param array $nsa    an array of tokens from the namespace string
     * @param string $ns    the namespace string in full
     * @return array        the meta data on how to load the component
     */
    protected function dao( $core, $nsa, $ns )
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
            \Painless::load( 'adapter/' . $adapter, LP_DEF_ONLY );

            $dao .= CNTOK . $adapter;
            $cn .= CNTOK . $adapter;
        }

        return array(
            'extpath' => $this->app . 'module/' . $module . '/dao/' . $dao . EXT,
            'extname'  => $cn,

            'basepath' => FALSE,
            'basename'  => FALSE,
        );
    }

    protected function adapter( $core, $nsa, $ns )
    {
        
    }
}