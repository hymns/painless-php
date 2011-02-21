<?php

// @PAINFUL

class PainlessCookie
{
    protected $salt = 'This is workfloo';
    protected $expiration = 0;
    protected $path = '/';
    protected $domain = NULL;
    protected $secure = FALSE;
    protected $httpOnly = FALSE;

    /**
     * READ-ONLY! Used to determine if the user has cookies enabled
     * @var boolean TRUE if cookies are enabled for the user, FALSE if otherwise
     */
    protected $hasCookies = FALSE;

    public function __construct( )
    {
        if ( ! isset( $_COOKIE["PHPSESSID"] ) && ! isset( $_GET["PHPSESSID"] ) )
        {
            // handle cookie not found here
        }
        elseif ( ! isset( $_COOKIE["PHPSESSID"] ) && isset( $_GET["PHPSESSID"] ) )
        {
            $this->hasCookies = TRUE;
        }
        else
        {
            $this->hasCookies = FALSE;
        }

    }

    public function hasCookies( )
    {
        return $this->hasCookies;
    }

    public function get( $key, $default = NULL )
    {
        
    }

    public function set( $key, $value, $expire = NULL )
    {
        
    }

    public function delete( $name )
    {
        
    }

    protected function generateSalt( )
    {
        
    }
}