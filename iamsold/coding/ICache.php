<?php


declare(strict_types=1);


namespace Buan;


/**
 * Interface for cache classes.
 *
 * @package Buan
 */
interface ICache
{
    /**
     * Retrieve an item from the cache. This method must ensure the cached item
     * has not yet expired before returning it.
     *
     * If no item exists at the specified key, return FALSE.
     *
     * @param string Storage key
     * @return mixed|FALSE
     */
    public function get(string $key);
    /**
     * Check if a cached object has expired.
     *
     * @param string Key
     * @param mixed Time at which to test against (should default to current time)
     * @return boolean
     */
    public function hasExpired(string $key, int $timestamp = null): boolean;

    /**
     * Store a data object at the given key.
     *
     * If storage fails for some reason, it returns FALSE. Otherwise returns TRUE.
     *
     * @param string Storage key
     * @param mixed Object to store
     * @param string|int
     */
    public function set(
        string $key,
               $value
    )
    : void;

    /**
     * Set the expiry date on a cached object. Return TRUE on successful setting.
     *
     * @param string Key
     * @param mixed Date (specific to implementation)
     */
    public function setExpiry(string $key, int $expireTimestamp): void;

    /**
     * Unsets the cached object specified by the ket.
     *
     * @param string Storage key
     */
    public function expire( $key ): void;


}
