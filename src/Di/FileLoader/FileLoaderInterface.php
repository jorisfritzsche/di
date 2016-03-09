<?php

/**
 * @copyright 2016 Joris Fritzsche
 * @license MIT
 * @author Joris Fritzsche (joris.fritzsche@outlook.com)
 */

declare(strict_types = 1);

namespace Di\FileLoader;

interface FileLoaderInterface
{
    /**
     * Set the directory from where the files will be read.
     *
     * @param string $directory
     * @return FileLoaderInterface
     */
    public function setDirectory(string $directory) : self;

    /**
     * Read a file and return it's contents.
     *
     * @param string $file
     * @return mixed
     */
    public function load(string $file);
}
