<?php

/**
 * @copyright 2016 Joris Fritzsche
 * @license MIT
 * @author Joris Fritzsche (joris.fritzsche@outlook.com)
 */

declare(strict_types = 1);

namespace Di\FileLoader;

use Di\Exception;

class Json implements FileLoaderInterface
{
    /** JSON file extension. */
    const JSON_FILE_EXTENSION = 'json';

    /**
     * @var string
     */
    protected $directory;

    /**
     * Json constructor.
     *
     * Sets the default directory.
     */
    public function __construct()
    {
        $this->setDirectory(
            dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR
        );
    }

    /**
     * Set the firectory from where to read the file.
     *
     * @param string $directory
     *
     * @return FileLoaderInterface
     */
    public function setDirectory(string $directory) : FileLoaderInterface
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * Load the specified JSON file. If the file does not contain valid JSON, an exception will be thrown.
     *
     * @param string $file
     *
     * @return array
     *
     * @throws Exception
     */
    public function load(string $file) : array
    {
        $filePath = $this->directory . $file;

        if (!is_readable($filePath)) {
            throw new Exception("File {$file} is not readable.");
        }

        if (pathinfo($filePath, PATHINFO_EXTENSION) != static::JSON_FILE_EXTENSION) {
            throw new Exception("File {$file} is not a JSON file.");
        }

        $data = file_get_contents($filePath);
        if (empty($data)) {
            throw new Exception("File {$file} is empty.");
        }

        $json = json_decode($data, true);
        if ($json === false || $json === null) {
            throw new Exception("File {$file} does not contain valid JSON.");
        }

        return $json;
    }

    /**
     * Save data to the specified file.
     *
     * @param string $file
     * @param mixed  $data
     *
     * @return Json
     *
     * @throws Exception
     */
    public function save(string $file, $data) : self
    {
        if (!is_string($data)) {
            $data = json_encode($data, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);
        }

        $filePath = $this->directory . $file;
        if (!is_writable($filePath)) {
            throw new Exception("File {$file} is not readable.");
        }

        file_put_contents($filePath, $data);

        return $this;
    }
}
