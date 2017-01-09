<?php

use App\Model
  , App\Entity
  , Silex\Application
  , Symfony\Component\HttpFoundation\Request;

// Set up maximum error reporting and UTC time
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );
date_default_timezone_set( 'UTC' );

// Load our source files and vendor libraries and constants
$WD = __DIR__ ."/..";
require( "$WD/vendor/autoload.php" );
// Load the constants
require( "$WD/app/constants.php" );
// Load the helper functions
require( "$WD/app/functions.php" );

// Instantiate a new App object
$app = new Application;

// Load the config files
$app[ 'config' ] = json_decode(
    file_get_contents(
        "$WD/app/conf/defaults.json"
    ));
$app[ 'locales' ] = json_decode(
    file_get_contents(
        "$WD/app/conf/locales.json"
    ));
$app[ 'emissions' ] = json_decode(
    file_get_contents(
        "$WD/app/conf/emissions.json"
    ));
$app[ 'questions' ] = json_decode(
    file_get_contents(
        "$WD/app/conf/questions.json"
    ));

// Depends on environment
$app[ 'debug' ] = $app[ 'config' ]->debug;

// Set up all services
require( "$WD/app/services.php" );

// Kick off the session at the start
$auth = $app[ 'auth' ];
$factory = $app[ 'entity.factory' ];
$app->before( 'auth:sessionStart' );

// Public routes
$rtr = $app[ 'controllers_factory' ];
$rtr->get( '/ping', 'controller:ping' );
$rtr->post( '/login', 'controller:login' );
$rtr->get( '/logout', 'controller:logout' );
$rtr->post( '/signup', 'controller:signup' );
$rtr->post( '/authorize', 'controller:authorize' );
// Mount these to the root
$app->mount( '/', $rtr );

// Administrative routes
$rtr = $app[ 'controllers_factory' ];
// Set up a certificate requirement
$rtr->before( 'auth:hasClientCertificate' );
// Now build out the routes
$rtr->get( '/', 'controller:admin' );
// Mount to the admin path
$app->mount( '/admin', $rtr );

// Application routes
$rtr = $app[ 'controllers_factory' ];
// Set up a login requirement on this group
$rtr->before( 'auth:loggedIn' );
// Dashboard
$rtr->get( '/dashboard', 'controller:dashboard' );
// Save a new member
$rtr->post( '/savemember/{group}/{year}', 'controller:saveMember' )
    ->assert( 'group', REGEXP_ALPHA )
    ->assert( 'year', REGEXP_YEAR )
    ->convert( 'group', $factory->make( GROUP ) )
    ->before( $auth->can( WRITE, GROUP ) );
// Remove a member from a group
$rtr->post( '/removemember/{group}/{year}', 'controller:removeMember' )
    ->assert( 'group', REGEXP_ALPHA )
    ->assert( 'year', REGEXP_YEAR )
    ->convert( 'group', $factory->make( GROUP ) )
    ->before( $auth->can( WRITE, GROUP ) );
// Load the questions for a group and year
$rtr->get( '/questions/{group}/{year}', 'controller:questions' )
    ->assert( 'group', REGEXP_ALPHA )
    ->assert( 'year', REGEXP_YEAR )
    ->convert( 'group', $factory->make( GROUP ) )
    ->before( $auth->can( WRITE, GROUP ) );
// Load the questions for a group, user, and year
$rtr->get( '/questions/{group}/{year}/{user}', 'controller:questions' )
    ->assert( 'group', REGEXP_ALPHA )
    ->assert( 'year', REGEXP_YEAR )
    ->assert( 'userId', REGEXP_NUMBER )
    ->convert( 'group', $factory->make( GROUP ) )
    ->convert( 'user', $factory->make( USER ) )
    ->before( $auth->can( READ, GROUP ) )
    ->before( $auth->can( WRITE, USER ) );
// Save an answer for a group and year
$rtr->post( '/saveanswer/{group}/{year}', 'controller:saveAnswer' )
    ->assert( 'group', REGEXP_ALPHA )
    ->assert( 'year', REGEXP_YEAR )
    ->convert( 'group', $factory->make( GROUP ) )
    ->before( $auth->can( WRITE, GROUP ) );
// Save an answer for a group, user, and year
$rtr->post( '/saveanswer/{group}/{year}/{user}', 'controller:saveAnswer' )
    ->assert( 'group', REGEXP_ALPHA )
    ->assert( 'year', REGEXP_YEAR )
    ->assert( 'userId', REGEXP_NUMBER )
    ->convert( 'group', $factory->make( GROUP ) )
    ->convert( 'user', $factory->make( USER ) )
    ->before( $auth->can( READ, GROUP ) )
    ->before( $auth->can( WRITE, USER ) );
// Load the group profile for a specific year
$rtr->get( '/{group}/{year}', 'controller:group' )
    ->assert( 'group', REGEXP_ALPHA )
    ->assert( 'year', REGEXP_YEAR )
    ->convert( 'group', $factory->make( GROUP ) )
    ->before( $auth->can( READ, GROUP ) );
// Load the group profile
$rtr->get( '/{group}', 'controller:group' )
    ->assert( 'group', REGEXP_ALPHA )
    ->convert( 'group', $factory->make( GROUP ) )
    ->before( $auth->can( READ, GROUP ) );
// General error handler
$rtr->get( '/{path}', 'controller:error' )
    ->assert( 'path', REGEXP_ANY );
// Mount these to root as well
$app->mount( '/', $rtr );

// After the controller, add the session data and any cookies to
// the response object.
$app->after( 'auth:sessionEnd' );

// Load the database reference to the Model statically
Model::setDb( $app[ 'db' ] );

// Load the locales reference to the Entity statically
Entity::setLocales( $app[ 'locales' ] );
Entity::setQuestions( $app[ 'questions' ] );

// Error handler
// All exceptions are ultimately caught here. If we're in debug mode
// then this will let the Symfony debug exception handler to take it.
// See App\Log for more info.
$app->error( function ( \Exception $e, Request $request, $code ) use ( $app ) {
    // For certain exceptions, get the code and message
    $code = ( method_exists( $e, 'getHttpCode' ) )
        ? $e->getHttpCode()
        : $code;
    $code = ( intval( $code ) >= 200 )
        ? $code
        : 500;
    // Send back a 200 so that the app can handle them
    $responseCode = ( in_array( $code, [ 400, 401, 403, 404, 500 ] ) )
        ? 200
        : $code;
    $status = ( in_array( $code, [ 401, 403 ] ) )
        ? INFO
        : ERROR;

    return $app->json([
        'code' => $code,
        'data' => [],
        'status' => $status,
        'message' => $e->getMessage(),
        'messages' => [[
            'type' => $status,
            'message' => $e->getMessage()
        ]]
    ], 200 );
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