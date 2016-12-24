<?php

/**
 * Application-wide constants
 */

// Statuses
define( 'INFO', 'info' );
define( 'ERROR', 'error' );
define( 'SUCCESS', 'success' );
define( 'WARNING', 'warning' );

// Types
define( 'INT', 'int' );
define( 'URL', 'url' );
define( 'DATE', 'date' );
define( 'STRING', 'string' );
define( 'OBJECT', 'object' );

// Regular expressions
define( 'REGEXP_NUMBER', '\d+' );
define( 'REGEXP_YEAR', '^\d{4}$' );
define( 'REGEXP_ALPHA', '[a-zA-Z]+' );
define( 'REGEXP_ALPHANUMERIC', '\w+' );

// Entity types
define( 'USER', 'user' );
define( 'GROUP', 'group' );

// HTTP Verbs
define( 'GET', 'GET' );
define( 'PUT', 'PUT' );
define( 'POST', 'POST' );
define( 'DELETE', 'DELETE' );

// Dates
define( 'DATE_PICKER', 'm/d/Y' );
define( 'DATE_SQLDATE', 'Y-m-d' );
define( 'DATE_SQL', 'Y-m-d H:i:s' );

// Environments
define( 'PRODUCTION', 'production' );
define( 'DEVELOPMENT', 'development' );