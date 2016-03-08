<?php

namespace Di\Cache;

abstract class AbstractCache
{
    const CACHE_DIR = 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
    const CACHE_FILE = 'caches.json';

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var string
     */
    protected $cacheFile;

    /**
     * @var array
     */
    protected $data;

    /**
     * AbstractCache constructor.
     */
    public function __construct()
    {
        $this->cacheDir = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . static::CACHE_DIR;
        $this->cacheFile = static::CACHE_FILE;

        $this->init();
    }

    /**
     * Init the cache.
     */
    protected function init()
    {
        /** Get the path to the cache file. */
        $path = $this->cacheDir . $this->cacheFile;
        if (file_exists($path) && is_readable($path)) {
            /** Read and decode the cache file. */
            $data = file_get_contents($this->cacheDir . $this->cacheFile);
            $data = json_decode($data, true);

            /** Make sure the cache data is at minimum an empty array. */
            if (empty($data)) {
                $data = [];
            }
        } else {
            /** If the cache file does not exist, create it. */
            fopen($path, 'w+');

            $data = [];
        }

        /** Store the cache data in a class variable. */
        $this->data = $data;
    }

    /**
     * Store new cache data, or overwrite existing data.
     *
     * @param string $key
     * @param $data
     * @return $this
     */
    public function store(string $key, $data)
    {
        if (is_array($data)) {
            array_walk_recursive(
                $data,
                function (&$value) {
                    $value = (string) $value;
                }
            );
        }

        $this->data[$key] = $data;

        return $this;
    }

    /**
     * Retrieve previously stored cache data.
     *
     * @param string $key
     * @return mixed|null
     */
    public function retrieve(string $key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return null;
    }

    /**
     * Save the cache. This method is automatically called when this instance is destroyed.
     *
     * @return $this
     */
    public function save()
    {
        $jsonData = json_encode($this->data);
        file_put_contents($this->cacheDir . $this->cacheFile, $jsonData);

        return $this;
    }

    /**
     * Clear the cache.
     *
     * @param bool $immediate
     * @return $this
     */
    public function clear($immediate = false)
    {
        $this->data = new \StdClass;

        /**
         * If the 'immediate' flag is set, save the now-empty cache immediately and do not wait for the destructor to
         * be called.
         */
        if ($immediate) {
            $this->save();
        }

        return $this;
    }

    /**
     * Make sure the cache is saved before this instance is destroyed.
     */
    public function __destruct()
    {
        $this->save();
    }
}
