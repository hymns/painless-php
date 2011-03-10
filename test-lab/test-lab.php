<?php

// Attempt to load PUPUnit.  If it fails, we are done.
if ( ! @include_once('PHPUnit/Autoload.php'))
{
	die(PHP_EOL.'PHPUnit does not appear to be installed properly.'.PHP_EOL.PHP_EOL.'Please visit http://phpunit.de and re-install.'.PHP_EOL.PHP_EOL);
}

/**
 * Set error reporting and display errors settings.  You will want to change these when in production.
 */
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

// Bootstrap Painless PHP
require_once __DIR__ . "/../painless/painless.php";
Painless::bootstrap( 'core', '/' );