<?php

class PainlessConfig
{
    protected $config = array( );

    public function get( $namespace )
    {
        // check if the config file has been loaded or not
        if ( empty( $this->config ) ) $this->init( );

        // try to load the value first
        $result = array_get( $this->config, $namespace, NULL );

        // see if the last segment of the namespace is an asterisk or not
        $pos = strrpos( trim( $namespace ), '*' );
        if ( $pos !== FALSE && $result === NULL )
        {
            // if so, then we should return everything that matches the particular
            // expression in the config array. First, extract the part of the
            // namespace prior to the asterisk symbol
            // COMMENTED OUT BECAUSE WE'RE USING 5.2
            //$partToMatch = strstr( $namespace, '*', TRUE );
            $partToMatch = substr( $namespace, 0, $pos );

            $matches = array( );
            foreach ( $this->config as $key => $config )
            {
                if ( strpos( $key, $partToMatch ) !== FALSE ) $matches[$key] = $config;
            }

            return $matches;
        }
        else
        {
            return $result;
        }
    }

    public function getAll( )
    {
        // lazy init
        if ( empty( $this->config ) ) $this->init( );

        return $this->config;
    }

    protected function init( )
    {
        $configPath = '';
        $profile = '';
        $aclPath = '';

        // first, check if a deployment profile is issued
        if ( defined( 'DEPLOY_PROFILE' ) )
            $profile = DEPLOY_PROFILE;

        // get the engine's implementor path if possible
        if ( defined( 'IMPL_PATH' ) )
        {
            $configPath = IMPL_PATH . 'config/' . strtolower( IMPL_NAME );
            $aclPath    = IMPL_PATH . 'config/' . strtolower( IMPL_NAME );
            $routesPath = IMPL_PATH . 'config/' . strtolower( IMPL_NAME );

            if ( $profile ) $configPath .= '.' . $profile;

            $configPath .= EXT;
            $aclPath    .= '.acl' . EXT;
            $routesPath .= '.routes' . EXT;
        }

        // check if the config path is correct
        if ( file_exists( $configPath ) )
        {
            require_once( $configPath );

            if ( !isset( $config ) )
                throw new PainlessConfigException( 'Unable to find the config array in [' . $configPath . ']' );

            $this->config = $config;

            // clean up because $config is going to be recycled after this
            unset( $config );
        }
        else
        {
            throw new PainlessConfigException( 'Invalid config file [' . $configPath . ']' );
        }

        // load the acl array too
        if ( file_exists( $aclPath ) )
        {
            require_once( $aclPath );

            if ( ! isset( $config ) )
                throw new PainlessConfigException( 'Unable to find the ACL array in [' . $aclPath . ']' );

            $this->config = array_merge( $this->config, $config );
        }

        // load the routes array too
        if ( file_exists( $routesPath ) )
        {
            require_once( $routesPath );

            if ( ! isset( $config ) )
                throw new PainlessConfigException( 'Unable to find the routes array in [' . $routesPath . ']' );

            $this->config = array_merge( $this->config, $config );
        }
    }

}

class PainlessConfigException extends ErrorException { }