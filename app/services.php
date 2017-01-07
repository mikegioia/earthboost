<?php

use App\Auth
  , App\Cache
  , App\Email
  , App\Session
  , App\Controller
  , Monolog\Logger
  , Monolog\Handler\StreamHandler
  , Monolog\Formatter\LineFormatter
  , Monolog\Handler\RotatingFileHandler
  , Pixie\Connection as DatabaseConnection
  , Silex\Provider\ServiceControllerServiceProvider;

// Set up the logging service
$app[ 'log' ] = function ( $app ) use ( $WD ) {
    $config = $app[ 'config' ];
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

// Load database connection service.
$app[ 'db' ] = function ( $app ) {
    return new DatabaseConnection(
        'mysql',
        (array) $app[ 'config' ]->database );
};

// Set up an email service
$app[ 'email' ] = function ( $app ) {
    return new Email( $app[ 'config' ]->email->key );
};

// Create a Redis connection for the session
$app[ 'session.redis' ] = function ( $app ) {
    $session = new Redis;
    $config = $app[ 'config' ]->redis->session;
    // Try to connect
    if ( ! $session->pconnect( $config->host, $config->port ) ):
        throw new \Exception(
            sprintf(
                "Couldn't connect to Redis at host: %s, port: %s",
                $config->host,
                $config->port
            ));
    endif;
    // Prefix all keys
    $session->setOption( Redis::OPT_PREFIX, 'eb:session:' );
    return $session;
};

// Create session service
$app[ 'session' ] = function ( $app ) {
    return new Session(
        $app[ 'session.redis' ],
        $app[ 'config' ],
        $app[ 'cache' ] );
};

// Create a Redis connection for the cache
$app[ 'cache.redis' ] = function ( $app ) {
    $cache = new Redis;
    $config = $app[ 'config' ]->redis->cache;
    // Try to connect
    if ( ! $cache->pconnect( $config->host, $config->port ) ):
        throw new \Exception(
            sprintf(
                "Couldn't connect to Redis at host: %s, port: %s",
                $config->host,
                $config->port
            ));
    endif;
    // Prefix all keys
    $cache->setOption( Redis::OPT_PREFIX, 'eb:cache:' );
    return $cache;
};

// This is our singleton service
$app[ 'cache' ] = function ( $app ) {
    return new Cache( $app[ 'cache.redis' ] );
};

// Set up authenticator service
$app[ 'auth' ] = function () {
    return new Auth;
};

$app[ 'controller' ] = function () {
    return new Controller;
};

// Allow Controller to be invoked as services
$app->register( new ServiceControllerServiceProvider );