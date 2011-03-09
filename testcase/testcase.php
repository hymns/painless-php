<?php

// Attempt to load PUPUnit.  If it fails, we are done.
if ( ! @include_once('PHPUnit/Autoload.php'))
{
	die(PHP_EOL.'PHPUnit does not appear to be installed properly.'.PHP_EOL.PHP_EOL.'Please visit http://phpunit.de and re-install.'.PHP_EOL.PHP_EOL);
}

// Extend from TestCase to allow flexibility in the future
class TestCase extends \PHPUnit_Framework_TestCase { }

// define the deployment profile
define( 'ERROR_REPORTING', E_ALL | E_STRICT ); // Set to 0 for 'production', set to E_ALL for 'development'

$vendor_path	= trim($_SERVER['vendor_path'], '/').'/';
$morphine_path	= trim($_SERVER['morphine_path'], '/').'/';
$core_path		= trim($_SERVER['core_path'], '/').'/';

date_default_timezone_set( 'UTC' );

$CORE = './painless/';
$IMPL = dirname(__FILE__);
include $CORE . 'painless.php';

$app = Painless::bootstrap( 'edgeyo', $IMPL );
Painless::$PROFILE = DEV;

var_dump($app->dispatch( ));

/*

// DO NOT CHANGE THIS LINE!!!!!!!!!!!!!!!!!!!!!
$CORE = dirname( __FILE__ ) . '/painless/';
$IMPL = dirname( __FILE__ ) . '/morphine-cli/';
include $CORE . 'painless.php';
include $IMPL . 'morphine.php';

/**
 * Website docroot
 * /
define('DOCROOT', __DIR__.DIRECTORY_SEPARATOR);

( ! is_dir($app_path) and is_dir(DOCROOT.$app_path)) and $app_path = DOCROOT.$app_path;
( ! is_dir($core_path) and is_dir(DOCROOT.$core_path)) and $core_path = DOCROOT.$core_path;
( ! is_dir($package_path) and is_dir(DOCROOT.$package_path)) and $package_path = DOCROOT.$package_path;

define('APPPATH', realpath($app_path).DIRECTORY_SEPARATOR);
define('PKGPATH', realpath($package_path).DIRECTORY_SEPARATOR);
define('COREPATH', realpath($core_path).DIRECTORY_SEPARATOR);

unset($app_path, $core_path, $package_path, $_SERVER['app_path'], $_SERVER['core_path'], $_SERVER['package_path']);

// Get the start time and memory for use later
defined('FUEL_START_TIME') or define('FUEL_START_TIME', microtime(true));
defined('FUEL_START_MEM') or define('FUEL_START_MEM', memory_get_usage());

// Boot the app
require_once APPPATH.'bootstrap.php';

// Set the environment to TEST
Fuel::$is_test = true;*/