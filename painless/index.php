<?php



function dash_to_pascal( $string )
{
    return preg_replace( '/(^|-)(.)/e', "strtoupper('\\2')", $string );
}

function dash_to_namespace( $string )
{
    // TODO: Use preg_replace for this
    $sp = explode( '/', $string );
    foreach( $sp as $i => $s )
    {
        $sp[$i] = dash_to_pascal( $s );
    }
    return implode( '\\', $sp );
}

echo dash_to_namespace( 'system/com-mon/cli-console' );