<?php

use App\Model
  , App\Entity
  , App\Session
  , App\Controller
  , Monolog\Logger
  , Silex\Application
  , App\Libraries\Email
  , Monolog\Handler\StreamHandler
  , Monolog\Formatter\LineFormatter
  , Monolog\Handler\RotatingFileHandler
  , Silex\Provider\SessionServiceProvider
  , Pixie\Connection as DatabaseConnection
  , Symfony\Component\HttpFoundation\Request
  , Silex\Provider\ServiceControllerServiceProvider;

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
$config = json_decode(
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
$app[ 'config' ] = $config;
$app[ 'debug' ] = $config->debug;

// Set up the logging service
$app[ 'log' ] = function () use ( $config, $WD ) {
    $logger = new Logger( $config->log->name );
    $handler = new RotatingFileHandler(
        "$WD/{$config->log->path}/{$config->environment}.log",
        $maxFiles = 0,
        $config->log->level,
        $bubble = TRUE );
    // Allow line breaks and stack traces, and don't show
    // empty context arrays
    $formatter = new LineFormatter;
    $formatter->allowInlineLineBreaks();
    $formatter->ignoreEmptyContextAndExtra();
    $handler->setFormatter( $formatter );
    $logger->pushHandler( $handler );

    return $logger;
};

// Set up an email service
$app[ 'email' ] = function () use ( $config ) {
    return new Email( $config->email->key );
};

// Set up the session handler and cookie settings
$app[ 'session.storage.options' ] = [
    'name' => $config->cookie->name,
    'cookie_lifetime' => $config->cookie->lifetime
];
$app->register( new SessionServiceProvider );

// Allow Controller to be invoked as services
$app->register( new ServiceControllerServiceProvider );

$app[ 'controller' ] = function () {
    return new Controller;
};

// Load all the routes
$app->get( '/ping', 'controller:ping' );
$app->get( '/login', 'controller:login' );
$app->get( '/logout', 'controller:logout' );
$app->post( '/signup', 'controller:signup' );
$app->get( '/dashboard', 'controller:dashboard' );
$app->get( '/{name}', 'controller:group' )
    ->assert( 'name', REGEXP_ALPHA );
$app->get( '/{name}/{year}', 'controller:group' )
    ->assert( 'name', REGEXP_ALPHA )
    ->assert( 'year', REGEXP_YEAR );

// Load database connection service.
$app[ 'db' ] = function () use ( $config ) {
    return new DatabaseConnection( 'mysql', (array) $config->database );
};

// Load the database reference to the Model statically
Model::setDb( $app[ 'db' ] );

// Load the locales reference to the Entity statically
Entity::setLocales( $app[ 'locales' ] );

// Error handler
// All exceptions are ultimately caught here. If we're in debug mode
// then this will let the Symfony debug exception handler to take it.
// See App\Log for more info.
$app->error( function ( \Exception $e, $code ) use ( $app ) {
    // If we're in debug mode, let error handler get this
    //if ( $app[ 'debug' ] ):
    //    return NULL;
    //endif;

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