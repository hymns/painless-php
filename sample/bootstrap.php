<?php

// Bootstrap Painless
require_once __DIR__ . "/../painless/painless.php";

class Painless extends \Painless
{
    public static function get( $ns, $opt = 0 )
    {
        $app = static::app( 'sample' );
        return $app->get( $ns, $opt );
    }
}