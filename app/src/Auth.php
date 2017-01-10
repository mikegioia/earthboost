<?php

/**
 * Authenticator class for verifying access to request
 * parameters. These functions provide hooks into the
 * access checks for various controller endpoints. See
 * ControllerProvider classes for usage.
 */
namespace App;

use App\EntityFactory
  , Silex\Application
  , App\Exceptions\Auth as AuthException
  , Symfony\Component\HttpFoundation\Request
  , Symfony\Component\HttpFoundation\Response
  , App\Exceptions\NotFound as NotFoundException
  , Symfony\Component\HttpFoundation\JsonResponse
  , App\Exceptions\AccessDenied as AccessDeniedException
  , App\Exceptions\AdminRequired as AdminRequiredException;

class Auth
{
    /**
     * Storage for certificate authorization. Set during start.
     */
    private $certificateAccess;

    /**
     * Header constants for SSL verification.
     */
    const SSL_SERIAL = 'x-ssl-serial';
    const SSL_VERIFY = 'x-ssl-verify';
    const SSL_VERIFY_SUCCESS = 'SUCCESS';

    /**
     * First middleware called in the route chain. This starts the
     * session and creates one if there isn't one.
     * @param Request $request
     * @param Application $app
     */
    public function sessionStart( Request $request, Application $app )
    {
        $env = $app[ 'config' ]->environment;

        if ( defined( 'CLI' ) ) {
            return;
        }

        $app[ 'session' ]->setRequest( $request );
        $app[ 'session' ]->start();

        if ( defined( 'CLI' ) || $env === DEVELOPMENT ) {
            $this->certificateAccess = TRUE;
            return;
        }

        $this->certificateAccess = $this->checkCertificate( $request, FALSE );
    }

    /**
     * Check if the user is logged in and if they're not, return
     * and access error. There are certain endpoints that we want
     * to allow, so we check that in the configuration file first.
     * @param Request $request
     * @param Application $app
     */
    public function loggedIn( Request $request, Application $app )
    {
        if ( defined( 'CLI' ) ) {
            return;
        }

        // Check if the user is logged in
        if ( ! $app[ 'session' ]->isLoggedIn() ) {
            throw new AuthException;
        }
    }

    /**
     * Checks if the user has site admin capability. This is set
     * through a valid client certificate.
     */
    public function isAdmin()
    {
        return $this->certificateAccess === TRUE;
    }

    /**
     * Route hook for checking client certificate access.
     * @param Request $request
     * @throws AdminRequiredException
     */
    public function hasClientCertificate( Request $request )
    {
        if ( is_null( $this->certificateAccess ) ) {
            $this->certificateAccess = $this->checkCertificate( $request );
        }

        if ( ! $this->certificateAccess ) {
            throw new AdminRequiredException;
        }
    }

    /**
     * Writes out the session.
     * @param Request $request
     * @param Response $response
     * @param Application $app
     */
    public function sessionEnd( Request $request, Response $response, Application $app )
    {
        if ( defined( 'CLI' ) ) {
            return;
        }

        // End the session
        $app[ 'session' ]->setResponse( $response );
        $app[ 'session' ]->write();
    }

    /**
     * This method checks access to the specific object/entity.
     * Usage: $auth->can( READ, $form )
     * It's used in Routers to be passed to the before() middle-
     * ware. Before the Controller action is hit, we can check
     * access and throw an Exception if not. This drastically
     * reduces the amount of boilerplate in our Controller actions
     * since they no longer need to check for existence and access
     * to each object.
     *
     * @param $access Constant (READ|WRITE)
     * @param $objectType String
     * @param $queryParam String -- (optional) specifies the query
     *   param key to use. By default this will check TYPE and
     *   TYPE_id.
     * @return function(Request, Application)
     */
    public function can( $access, $objectType, $queryParam = NULL )
    {
        // The object type is in camel case. We want to convert this
        // to underscore case for checking the request attibutes for
        // the key containing the ID.
        $key = ( ! is_null( $queryParam ) )
            ? $queryParam
            : strtolower(
                preg_replace(
                    '/([a-z])([A-Z])/',
                    '$1_$2',
                    $objectType
                ));
        // Helper to get the type of access. READ or WRITE.
        $accessCall = ( $access === READ )
            ? "canRead"
            : "canWrite";

        return function ( Request $request, Application $app )
                    use ( $key, $accessCall, $objectType )
        {
            $params = $request->attributes->all();
            $params = get( $params, '_route_params', [] );

            // We want to look for request attributes with the name of
            // this object type or of the form TYPE_id. Then, instantiate
            // a new object of the type and call the access method on it.
            if ( $request->attributes->get( $key ) ) {
                $objectId = $request->attributes->get( $key );
            }
            elseif ( $request->attributes->get( "{$key}_id" ) ) {
                $objectId = $request->attributes->get( "{$key}_id" );
            }
            else {
                throw new NotFoundException( $objectType );
            }

            // Get the object from the factory and check if it exists
            $make = "make{$objectType}";
            $entity = $app[ 'entity.factory' ]->make( $objectType, $objectId );

            if ( ! $entity->exists() ) {
                throw new NotFoundException( $objectType, $objectId );
            }

            if ( ! $app[ 'session' ]->$accessCall( $entity, $params ) ) {
                throw new AccessDeniedException( $objectType, $objectId );
            }
        };
    }

    /**
     * Checks if there's a valid client certificate in the request.
     * @param Request $request
     * @return bool
     */
    private function checkCertificate( Request $request )
    {
        $headers = $request->headers->all();
        $verify = ( isset( $headers[ self::SSL_VERIFY ] ) )
            ? array_shift( $headers[ self::SSL_VERIFY ] )
            : NULL;
        $serial = ( isset( $headers[ self::SSL_SERIAL ] ) )
            ? array_shift( $headers[ self::SSL_SERIAL ] )
            : NULL;

        return $serial && $verify === self::SSL_VERIFY_SUCCESS;
    }
}