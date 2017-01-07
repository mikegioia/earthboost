<?php

/**
 * Session service library for working with user state.
 * This library takes in a session adapter service to read
 * and write to. It manages logged in state for the user,
 * temporary flash data, and reading/writing session data
 * to our adapter.
 */
namespace App;

use Redis
  , stdClass
  , App\Cache
  , App\Entities\User
  , App\Exceptions\Login as LoginException
  , Symfony\Component\HttpFoundation\Cookie
  , Symfony\Component\HttpFoundation\Request
  , Symfony\Component\HttpFoundation\Response;

class Session
{
    // Cache service
    private $cache;
    // Storage for the session (Redis)
    private $session;
    // Dependencies loaded in before/after
    public $request;
    private $response;
    // Internal session data
    private $ttl;
    private $user;
    private $userData;
    private $sessionId;
    private $flashData;
    private $flashQueue;
    // Session cookie data
    private $cookieJar;
    private $loginCookie;
    private $sessionCookie;
    // Redis constants
    const USER_TOKEN = 'user_token:';
    const USER_SESS_ID = 'user_sess_id:';
    const USER_SESS_IP = 'user_sess_ip:';
    // User field constants
    const USER_ID = 'id';

    public function __construct( Redis $session, stdClass $config, Cache $cache )
    {
        $this->userData = [];
        $this->flashData = [];
        $this->cookieJar = [];
        $this->cache = $cache;
        $this->flashQueue = [];
        $this->session = $session;

        // Set up cookie config
        $this->ttl = $config->session->ttl;
        $this->loginCookie = $config->login->cookie;
        $this->sessionCookie = $config->session->cookie;
    }

    public function setRequest( Request $request )
    {
        $this->request = $request;
    }

    public function setResponse( Response $response )
    {
        $this->response = $response;
    }

    /**
     * Public entry-point to initializing the session.
     */
    public function start()
    {
        if ( ! $this->read() ) {
            $this->create();
        }
    }

    /**
     * Read in the session cookie and check if there's a corresponding
     * key in Redis. If there is, read the data from redis and populate
     * our internal class variables.
     */
    private function read( $userId = NULL )
    {
        // If a user ID came in, get the session ID for that user and
        // load it.
        if ( ! is_null( $userId ) && valid( $userId, INT ) ) {
            $sessionId = $this->session->get( self::USER_SESS_ID . $user_id );
        }
        else {
            $sessionId = get(
                $this->request->cookies->all(),
                $this->sessionCookie->name );
        }

        if ( ! valid( $sessionId, STRING ) ) {
            return FALSE;
        }

        // Check if the token exists in redis
        $this->sessionId = $sessionId;
        $redisSession = $this->session->get( $this->sessionId );
        $sessionData = @unserialize( $redisSession );

        if ( ! $sessionData ) {
            return FALSE;
        }

        // Move data from the queue (what was read from redis) to the data
        // the data will expire at the end of the request.
        $this->userData = $sessionData[ 'user_data' ];
        $this->flashData = $sessionData[ 'flash_data' ];
        $this->flashQueue = [];

        return TRUE;
    }

    /**
     * Create a new session key and overwrite the session cookie with this
     * key. This is the only method that actually writes out a cookie. Make
     * sure to use a secure PRNG for session key generation.
     * @throws LoginException
     */
    private function create()
    {
        // Generate the cryto strong ID
        $randomBytes = openssl_random_pseudo_bytes( 32, $cstrong );
        $this->sessionId = base64_encode( $randomBytes );

        // We want to rate-limit this by IP address. 20 times in 10 mins
        // is too many. Adjust this if it's causing problems but it shouldn't.
        $key = self::USER_SESS_IP . $this->request->getClientIp();

        if ( ! $this->cache->rateLimit( $key, 20, 60 ) ) {
            throw new LoginException(
                "Rate limit exceeded for session creation." );
        }

        // Set the session cookie for a week. This is written to the response
        // object in write().
        $this->cookieJar[] = new Cookie(
            $this->sessionCookie->name,
            $this->sessionId,
            ( time() + $this->sessionCookie->ttl ),
            $this->sessionCookie->path,
            $this->sessionCookie->domain,
            $this->sessionCookie->secure,
            FALSE ); // http only
    }

    /**
     * Create a new session key by authenticating the user's login cookie
     * token. This is a persistant browser cookie that we can authenticate
     * the user with.
     * @param string $token Optional, uses cookie otherwise
     * @throws LoginException
     */
    public function createFromToken( $token = NULL )
    {
        // Check if the cookie token exists
        if ( ! $token ) {
            $token = get(
                $this->request->cookies->all(),
                $this->loginCookie->name );
        }

        if ( ! $token ) {
            throw new LoginException(
                "No valid token found for login." );
        }

        // Same rate-limiting as in create
        $key = self::USER_SESS_IP . $this->request->getClientIp();

        if ( ! $this->cache->rateLimit( $key, 20, 60 ) ) {
            throw new LoginException(
                "Rate limit exceeded for session creation." );
        }

        // Look-up token from database and check if there's a valid
        // user account tied to it (if the token exists).
        $user = new User;
        $userId = $this->session->get( self::USER_TOKEN . $token );

        if ( valid( $userId, INT ) ) {
            $user = new User( $userId );
        }

        if ( ! $user->exists() ) {
            throw new LoginException(
                "No user found from login token. Sign in again please." );
        }

        $this->user = $user;
        $this->set( $user->toArray() );
    }

    /**
     * Creates a new login token for the specified user.
     * @param User $user
     * @param int $ttl Default is 3 days
     * @return string
     */
    public function createLoginToken( User $user, $ttl = 259200 )
    {
        $randomBytes = openssl_random_pseudo_bytes( 32, $cstrong );
        $token = base64_encode( $randomBytes );

        // Set this in redis
        $this->session->setex(
            self::USER_TOKEN . $token,
            $ttl,
            $user->id );

        return $token;
    }

    /**
     * Write the session info to Redis
     */
    public function write()
    {
        $sessionData = [
            'user_data' => $this->userData,
            'session_id' => $this->sessionId,
            'flash_data' => $this->flashQueue
        ];

        // If there's no session counter or if the session counter hits
        // one of our conditions, then execute the task(s).
        if ( ! isset( $sessionData[ 'user_data' ][ 'session_timer' ] )
            || intval( $sessionData[ 'user_data' ][ 'session_timer' ] ) + 600 < time() )
        {
            // 10 minutes have passed
            $session_data[ 'user_data' ][ 'session_timer' ] = time();
        }

        if ( $this->cookieJar ) {
            foreach ( $this->cookieJar as $cookie ) {
                $this->response->headers->setCookie( $cookie );
            }
        }

        // If there's a user in the session, write out the session
        // ID for this user ID.
        if ( valid( $this->get( 'id' ) ) ) {
            return $this->session
                ->multi()
                ->set(
                    self::USER_SESS_ID . $this->get( 'id' ),
                    $this->sessionId )
                ->setex(
                    $this->sessionId,
                    $this->ttl,
                    serialize( $sessionData ))
                ->exec();
        }
        else {
            return $this->session->setex(
                $this->sessionId,
                $this->ttl,
                serialize( $sessionData ));
        }
    }

    /**
     * Destroys the session, kills the cookies.
     * @return bool
     */
    public function destroy()
    {
        $this->session->delete( $this->sessionId );

        // Kill class variables
        $this->userData = [];
        $this->sessionId  = NULL;

        // Kill the cookies
        $this->cookieJar[] = new Cookie(
            $this->sessionCookie->name,
            "",
            ( time() - 31500000 ),
            $this->sessionCookie->path,
            $this->sessionCookie->domain,
            $this->sessionCookie->secure,
            FALSE ); // http only
        $this->cookieJar[] = new Cookie(
            $this->loginCookie->name,
            "",
            ( time() - 31500000 ),
            $this->loginCookie->path,
            $this->loginCookie->domain,
            $this->loginCookie->secure,
            FALSE ); // http only

        return TRUE;
    }

    /**
     * Get the session ID.
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Check if the user is logged in.
     * @return bool
     */
    public function isLoggedIn()
    {
        return valid( $this->getUserId(), INT );
    }

    /**
     * Check if the logged in user can read the requested entity.
     */
    public function canRead( $entity )
    {
        return TRUE;
        // @TODO
        return $entity->isReadBy( $this->getUser() );
    }

    public function canWrite( $entity )
    {
        return TRUE;
        // @TODO
        return $entity->isWrittenBy( $this->getUser() );
    }


    /**
     * Save new session data by key and value.
     * @param array|string $newData
     * @param array|string $newVal
     */
    public function set( $newData = [], $newVal = '' )
    {
        if ( is_string( $newData ) ) {
            $newData = [ $newData => $newVal ];
        }

        if ( count( $newData ) > 0 ) {
            foreach ( $newData as $key => $val ) {
                $this->userData[ $key ] = $val;
            }
        }
    }

    /**
     * Clears session data by key or keys.
     * @param string|array $newData
     */
    public function clear( $newData = [] )
    {
        if ( is_string( $newData ) ) {
            $newData = [ $newData => '' ];
        }

        if ( count( $newData ) > 0 ) {
            foreach ( $newData as $key => $val ) {
                unset( $this->userData[ $key ] );
            }
        }
    }

    /**
     * Get a value from the session by key.
     * @param string $key
     * @return mixed|bool
     */
    public function get( $key )
    {
        return ( isset( $this->userData[ $key ] ) )
            ? $this->userData[ $key ]
            : FALSE;
    }

    /**
     * Get all user data.
     * @return array
     */
    public function getAll()
    {
        return $this->userData;
    }

    /**
     * Return the logged-in User object.
     * @return User
     */
    public function getUser()
    {
        if ( $this->user ) {
            return $this->user;
        }

        $this->user = ( $this->isLoggedIn() )
            ? new User( $this->getUserId() )
            : new User;

        return $this->user;
    }

    /**
     * Get the logged in user's ID.
     * @return int|bool
     */
    public function getUserId()
    {
        return $this->get( self::USER_ID );
    }

    /**
     * Flash queue gets all data set to it. at the end of the request
     * the flash queue and user data get written to redis. at the
     * beginning of the session the flash queue gets moved to the flash
     * data for application access.
     * @param string|array $newData
     * @param string|array $newVal
     */
    public function setFlash( $newData = [], $newVal = '' )
    {
        if ( is_string( $newData ) ) {
            $newData = array( $newData => $newVal );
        }

        if ( count( $newData ) > 0 ) {
            foreach ( $newData as $key => $val ) {
                $this->flashQueue[ $key ] = $val;
            }
        }
    }

    /**
     * Persist the flash data across a request.
     */
    public function keepFlash()
    {
        $this->flashQueue = $this->flashData;
    }

    /**
     * Get a value from the flash data by key.
     * @param string $key
     * @return mixed
     */
    public function flash( $key )
    {
        return ( isset( $this->flashData[ $key ] ) )
            ? $this->flashData[ $key ]
            : FALSE;
    }
}