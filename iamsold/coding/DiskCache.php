<?php
declare(strict_types=1);
namespace Buan;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
/**
 * Container for all Cache classes.
 *
 * @package Buan
 */
class DiskCache implements ICache
{
    /**
     * Directory to which cache files will be written.
     *
     * @var string
     */
    private $dir;

    /**
     * Throws an Exception if the directory is not valid.
     *
     * @param string Directory to which cache files will be written
     * @throws FileException
     */
    public function __construct(
        $dir
    )
    {

        // If the directory doesn't exists, try to create it. Otherwise throw an
        // exception
        if (!is_dir($dir)) {
            if (!mkdir($dir) && !is_dir($dir)) {
                throw new Exception("Cache target directory is missing: {$dir}");
            }
        } elseif (!is_writable($dir)) {
            throw new Exception("Cache target directory is not writable: {$dir}");
        }
        $this->dir = $dir;

    }

    /**
     * @param $key
     */
    public function expire(string $key): void
    {
        $hash = MD5($key);
        if (file_exists("{$this->dir}/{$hash}.cache")) {
            unlink( "{$this->dir}/{$hash}.cache" );
        }
    }

    /**
     * Load data from the cache.
     *
     * @param string Storage key
     * @return mixed|FALSE
     */
    public function get(string $key)
    {
        $hash = md5($key);
        return file_exists("{$this->dir}/{$hash}.cache")
            ? unserialize(
                file_get_contents(
                    "{$this->dir}/{$hash}.cache"
                ),
                [
                    "allowed_classes" => array(
                        \StdClass::class,
                    ),
                ]
            )
            : false;
    }

    /**
     * Test if an object has expired.
     *
     * @param string $key Key
     * @return boolean
     */
    public function objectExists(string $key): bool
    {
        $t = $this->readExpiryTable();
        return empty($t[$key]) ? false : true;
    }

    /**
     * Test if an object has expired.
     *
     * @param string $key Key
     * @param integer $timestamp
     * @return boolean true if has expired or no entry found for $key (so both will trigger a re-read),
     *              false if not yet expired.
     */
    public function hasExpired(string $key, int $ts = null): bool
    {
        $t = $this->readExpiryTable();
        return empty($t[$key]) ? true : $t[$key] <= $ts;
    }

    /**
     * Find results in the expiry table that are like the findKey passed through.
     * Returns the resulting found actual keys, in an array for the user.
     * @param string $findKey       A partial string that we're looking for in a key.
     */
    public function findKeyLike(string $findKey)
    {
        $foundResults = array();

        $t = $this->readExpiryTable();
        foreach ($t AS $key => $expiryTime) {
            if (strpos($key, $findKey) !== FALSE) {
                $foundResults[] = $key;
            }
        }

        return $foundResults;
    }

    /**
     * Reads the contents of the expiry table and returns.
     * @return array
     */
    private function readExpiryTable(): array
    {
        $et = "{$this->dir}/expirytable";
        $t = file_exists( $et ) ? file_get_contents( $et ) : '';
        return empty($t) ? array() : (array) json_decode( $t );
    }

    /**
     * Store an object in the cache against the given key.
     *
     * @param string Key
     * @param mixed Data to store
     */
    public function set(string $key, $data): void
    {
        $hash = md5($key);
        file_put_contents("{$this->dir}/{$hash}.cache", serialize($data));
    }

    /**
     * Set the expire time on an object.
     *
     * @param string $key Object key
     * @param integer $expireTimestamp Object will expire on this timestamp date. Use 0 for no expiry
     * @throws Exception
     */
    public function setExpiry(string $key, int $expiry = 0): void
    {
        $table = $this->readExpiryTable();
        $table[$key] = $expire;
        $this->writeExpiryTable($table);
    }

    /**
     * Writes expiry data to the table.
     *
     *
     * @param array Expiry data
     * @throws Exception
     */
    private function writeExpiryTable(array $data): void
    {
        $et = "{$this->dir}/expirytable";
        if (!file_exists($et)) {
            if (!touch($et) || !chmod($et, '0777')) {
                $e = new Exception("Unable to create cache expiry table.");
                throw $e;
            }
        }
        file_put_contents($et, json_encode($data));
    }
}
