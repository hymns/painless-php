<?php
namespace Sample;

// Bootstrap Painless
require_once __DIR__ . "/../painless/painless.php";
Painless::initApp( 'sample', __DIR__ );
echo Painless::app( 'sample' )->run( );
?>