<?php

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

class PainlessLoader
{
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
        if ( ! empty( $ns ) && isset( Painless::$cache[$ns] ) && ! ( $opt & LP_SKIP_CACHE_LOAD ) )
            return Painless::$cache[$ns];

        // Explode the namespace string into an array to make it easier to work
        // with
        $nsa = explode( NSTOK, $ns );
        if ( empty( $nsa ) || count( $nsa ) <= 1 ) throw new PainlessLoaderException( 'Namespace cannot be NULL or a malformed format [' . $ns . ']' );

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
                && ! class_exists( $meta['base_obj'] ) )
            require_once $meta['base_path'];

        // Instantiate the core class
        if ( class_exists( $meta['base_obj'] ) && ( $isRetCore || $isCacheCore ) )
        {
            $comBase = new $meta['base_obj'];

            // If caching is required (by enabling the LP_CACHE_CORE flag), save
            // the instantiated object into Painless's central cache
            if ( $isCacheCore ) Painless::$cache[$ns] = $comBase;
        }

        // Load the definition of the ext class, if possible
        if ( file_exists( $meta['load_path'] ) 
                && $isDefExt
                && ! class_exists( $meta['load_obj'] ) )
            require_once $meta['load_path'];

        // Instantiate the ext class
        if ( class_exists( $meta['load_obj'] ) && ( $isRetExt || $isCacheExt ) )
        {
            $comExt = new $meta['load_obj'];

            // If caching is required (by enabling the LP_CACHE_EXT flag), save
            // the instantiated object into Painless's central cache (overwrite
            // LP_CACHE_CORE if possible
            if ( $isCacheExt ) Painless::$cache[$ns] = $comExt;
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
        //throw new PainlessLoaderException( 'Unable to load the component [' . $ns . ']' );
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

        // Add a check here for direct instantiation of data adapters and DAOs
        if ( FALSE !== strpos( $ns, 'system/data/adapter/' ) || FALSE !== strpos( $ns, 'system/data/dao' ) )
            throw new PainlessLoaderException( 'Data adapters and DAO base classes (PainlessDao, PainlessMysql, etc) cannot be directly instantiated because they are all abstract classes. Please extend them and use Painless::get( \'dao/[module]/[dao-name]/[adapter-type]\') instead.' );

        return array(            
            'load_path' => IMPL_PATH . $ns . EXT,
            'load_obj'  => ucwords( IMPL_NAME ) . $cn,
            
            'base_path' => PL_PATH . $ns . EXT,
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
            'load_path' => IMPL_PATH . $ns . '/' . $fn . EXT,
            'load_obj'  => ucwords( IMPL_NAME ) . $cn,

            'base_path' => PL_PATH . $ns . '/' . $fn . EXT,
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
        if ( count( $nsa ) < 2 ) throw new PainlessLoaderException( 'Module namespace should follow this format: module/[module]' );

        // Don't use the $ns string passed in. The module's name is ALWAYS the
        // last token of the $nsa array
        $ns = end( $nsa );
        $cn = dash_to_pascal( $ns );

        // Load the base object manually
        Painless::get( 'system/workflow/module', LP_DEF_ONLY );

        return array(            
            'load_path' => IMPL_PATH . 'module/' . $ns . '/module' . EXT,
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
        if ( count( $nsa ) < 3 ) throw new PainlessLoaderException( 'Workflow namespace should follow this format: workflow/[module]/[workflow]' );

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
            'load_path' => IMPL_PATH . 'module/' . $module . '/workflow/' . $workflow . EXT,
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
        if ( count( $nsa ) < 3 ) throw new PainlessLoaderException( 'Model namespace should follow this format: model/[module]/[model]' );

        // The second key in the $nsa array is always the module name, followed
        // by the model name
        $module = $nsa[1];
        $model = $nsa[2];
        
        $cn = dash_to_pascal( $module . CNTOK . $model );

        // Load the base object manually
        Painless::get( 'system/workflow/model', LP_DEF_ONLY );

        return array(
            'load_path' => IMPL_PATH . 'module/' . $module . '/model/' . $model . EXT,
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
        if ( count( $nsa ) < 3 ) throw new PainlessLoaderException( 'View namespace should follow this format: view/[module]/[view]' );
        
        // The second key in the $nsa array is always the module name, followed
        // by the view name
        $module = $nsa[1];
        $view = $nsa[2];

        $cn = dash_to_pascal( $module . CNTOK . $view );

        // load the base object manually
        Painless::get( 'system/view/view', LP_DEF_ONLY );

        return array(
            'load_path' => IMPL_PATH . 'view/' . $module . '/' . $view . EXT,
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
        if ( count( $nsa ) < 2 ) throw new PainlessLoaderException( 'View Compiler namespace should follow this format: view-compiler/[type]' );

        // The second key in the $nsa array is always the compiler type
        $type = $nsa[1];

        $cn = dash_to_pascal( $type );

        // Load the base object manually
        Painless::get( 'system/view/compiler/base', LP_DEF_ONLY );

        return array(
            'load_path' => IMPL_PATH . 'system/view/compiler/' . $type . EXT,
            'load_obj'  => ucwords( IMPL_NAME ) . $cn . 'Compiler',

            'base_path' => PL_PATH . 'system/view/compiler/' . $type . EXT,
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
        if ( count( $nsa ) < 4 ) throw new PainlessLoaderException( 'DAO namespace should follow this format: dao/[module]/[dao]/[adapter]' );

        // The second key in the $nsa array is always the module name, followed
        // by the view name
        $module = $nsa[1];
        $dao = $nsa[2];
        $adapter = $nsa[3];

        // All DAO adapters HAVE to extend PainlessDao, so we load that first
        Painless::get( 'system/data/dao', LP_DEF_ONLY );

        // Load the base object manually
        Painless::get( 'system/data/dao/adapter/' . $adapter, LP_DEF_ONLY );

        $cn = dash_to_pascal( $module . CNTOK . $dao . CNTOK . $adapter );

        return array(
            'load_path' => IMPL_PATH . 'module/' . $module . '/dao/' . $dao . CNTOK . $adapter . EXT,
            'load_obj'  => $cn,

            'base_path' => FALSE,
            'base_obj'  => FALSE,
        );
    }
}

class PainlessLoaderException extends ErrorException { }