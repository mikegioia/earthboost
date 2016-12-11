<?php

use App\Session
  , App\Controller
  , Silex\Application
  , Silex\Provider\SessionServiceProvider
  , Symfony\Component\HttpFoundation\Request
  , Silex\Provider\ServiceControllerServiceProvider;

// Set up maximum error reporting and UTC time
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );
date_default_timezone_set( 'UTC' );

// Constants
define( 'ERROR', 'error' );
define( 'SUCCESS', 'success' );

// Load our source files and vendor libraries and constants
$WD = __DIR__ ."/..";
//require( "$WD/src/constants.php" );
require( "$WD/vendor/autoload.php" );

// Instantiate a new App object
$app = new Application;

// Depends on environment
$app[ 'debug' ] = FALSE; //$app[ 'config' ]->app->debug;

// Set up the session handler and cookie settings
// @TODO store this in config file
$app[ 'session.storage.options' ] = [
    'name' => 'user',
    'cookie_lifetime' => 7776000 // 90 days
];
$app->register( new SessionServiceProvider );

// Allow Controller to be invoked as services
$app->register( new ServiceControllerServiceProvider );

$app[ 'controller' ] = function () use ( $app ) {
    return new Controller;
};

// Load all the routes
$app->get( '/login', 'controller:login' );
$app->get( '/logout', 'controller:logout' );
$app->get( '/dashboard', 'controller:dashboard' );

// Error handler
// All exceptions are ultimately caught here. If we're in debug mode
// then this will let the Symfony debug exception handler to take it.
// See App\Log for more info.
$app->error( function ( \Exception $e, $code ) use ( $app ) {
    // If we're in debug mode, let error handler get this
    if ( $app[ 'debug' ] ):
        return NULL;
    endif;

    // For certain exceptions, get the code and message
    $code = ( method_exists( $e, 'getHttpCode' ) )
        ? $e->getHttpCode()
        : 400;

    return $app->json([
        'code' => $code,
        'data' => [],
        'status' => ERROR,
        'message' => $e->getMessage(),
        'messages' => []
    ], $code );
});

// If a CLI command was run, execute it manually
if ( isset( $argv ) && ! is_null( $argv ) ):
    define( 'CLI', TRUE );

    if ( count( $argv ) < 3 ):
        exit( "Usage: php {$argv[0]} [GET|POST] <path>" );
    endif;

    list( $script, $method, $path ) = $argv;
    $request = Request::create(
        ( $path ) ?: '/',
        ( in_array( $method, [ 'POST', 'GET' ] ) )
            ? $method
            : 'GET' );
    $app->run( $request );
// Otherwise kick off the application
else:
    $app->run();
endif;