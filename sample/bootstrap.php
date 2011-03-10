<?php

namespace Sample;

// Bootstrap Painless
require_once __DIR__ . "/../painless/painless.php";

// Define the app name into a constant
define( 'SAMPLE', 'sample' );

Painless::app( SAMPLE )->load( 'something' );