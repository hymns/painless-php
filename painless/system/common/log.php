<?php 

class PainlessLog
{
    protected $file = NULL;

    public function __destruct( )
    {
        if ( $this->file )
        {
            fclose( $this->file );
        }
    }

    public function open( )
    {
        if ( empty( $this->file ) )
        {
            $path = IMPL_PATH . 'log.txt';
            $this->file = fopen( $path, 'a' );
        }
    }

    public function info( $message )
    {
        if ( DEPLOY_PROFILE === 'development' )
        {
            $this->open( );
            fwrite( $this->file, "$message\n" );
        }
    }
}