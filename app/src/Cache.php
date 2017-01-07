<?php

/**
 * Singleton cache library for working with the application
 * cache. This library takes in a caching adapter service to
 * read and write to.
 */
namespace App;

use Redis;

class Cache
{
    /**
     * Expects a Redis driver object or equivalent mock
     *    get( $keyName )
     *    setex( $keyName, $ttl, $content )
     *    delete( $keyName )
     *    incr( $keyName )
     *    ttl( $keyName )
     *    expire( $keyName, $maxTtl )
     * @param Redis
     */
    private $cache;

    /**
     * Local key-value store.
     * @param array
     */
    private $store;

    public function __construct( Redis $cache, $store = [] )
    {
        $this->store = $store;
        $this->cache = $cache;
    }

    public function get( $keyName, $checkStorage = TRUE )
    {
        $content = ( $checkStorage )
            ? $this->getStorage( $keyName )
            : NULL;

        // If it wasn't in the storage, get it from Redis
        if ( is_null( $content ) ) {
            $content = $this->cache->get( $keyName );
        }
        // Otherwise send them the local copy
        else {
            return $content;
        }

        return ( $content )
            ? @unserialize( $content )
            : NULL;
    }

    public function set( $keyName, $content, $ttl = 900, $store = TRUE )
    {
        if ( $store ) {
            $this->setStorage( $keyName, $content );
        }

        return $this->cache->setex( $keyName, $ttl, @serialize( $content ) );
    }

    /**
     * Return the data if the key exists. if not, run the callback and
     * set the key to the callback's response.
     *
     * @param string $keyName
     * @param function $callback
     * @param int $ttl Expiration in seconds (default 15 minutes)
     * @param boolean $store Whether to store locally
     * @return mixed
     */
    public function getSet( $keyName, $callback = NULL, $ttl = 900, $store = TRUE )
    {
        $cache = $this->get( $keyName, $store );

        if ( ! is_null( $cache ) ) {
            return $cache;
        }

        if ( ! is_callable( $callback ) ) {
            return NULL;
        }

        $content = $callback();
        $this->set( $keyName, $content, $ttl, $store );

        return $content;
    }

    public function delete( $keyName )
    {
        return $this->cache->delete( $keyName );
    }

    public function getStorage( $keyName )
    {
        return ( isset( $this->store[ $keyName ] ) )
            ? $this->store[ $keyName ]
            : NULL;
    }

    public function setStorage( $keyName, $content )
    {
        $this->store[ $keyName ] = $content;
    }

    public function getSetStorage( $keyName, $callback )
    {
        if ( isset( $this->store[ $keyName ] ) ) {
            return $this->store[ $keyName ];
        }

        $content = $callback();
        $this->setStorage( $keyName, $content );

        return $content;
    }

    public function deleteStorage( $keyName )
    {
        unset( $this->store[ $keyName ] );
    }

    public function getObject( $id, $type )
    {
        return $this->getStore( $id . $type );
    }

    public function setObject( $id, $type, $object )
    {
        $this->setStorage[ $id . $type ] = $object;
    }

    public function deleteObject( $id, $type )
    {
        $this->deleteStorage[ $id . $type ];
    }

    /**
     * Only allow a certain action, identified by a key, to be executed
     * at most $max_attempts times in $max_ttl seconds.
     */
    public function rateLimit( $key, $maxAttempts = 10, $maxTTL = 60 )
    {
        $attempts = $this->cache->incr( $key );
        $ttl = $this->cache->ttl( $key );

        if ( $attempts >= $maxAttempts ) {
            return FALSE;
        }

        // Aet the expiration and increment
        if ( $attempts <= 1 || $ttl <= 0 ) {
            $this->cache->expire( $key, $maxTTL );
        }

        return TRUE;
    }
}