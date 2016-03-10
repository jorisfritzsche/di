<?php

/**
 * @copyright 2016 Joris Fritzsche
 * @license MIT
 * @author Joris Fritzsche (joris.fritzsche@outlook.com)
 */

declare(strict_types = 1);

namespace Di\Config;

class Rewrites extends AbstractConfig
{
    const CONFIG_FILE = 'rewrites.json';

    /**
     * Add a new rewrite or replace an existing one.
     *
     * @param array $rewrite
     *
     * @return self
     *
     * @todo validate the new rewrite.
     */
    public function addRewrite(array $rewrite) : self
    {
        $this->data = array_merge_recursive($this->data, $rewrite);

        return $this;
    }

    /**
     * Remove an existing rewrite.
     *
     * @param string $className
     *
     * @return Rewrites
     */
    public function removeRewrite(string $className) : self
    {
        if (isset($this->data[$className])) {
            unset($this->data[$className]);
        }

        return $this;
    }

    /**
     * Process any rewrites found in the DI config.
     *
     * @param string $type
     *
     * @return \ReflectionType|string
     */
    public function processRewrites(string $type)
    {
        /** Get the type's class name and make sure it starts in the root namespace. */
        $stringType = $type;
        if (strpos($stringType, '\\') !== 0) {
            $stringType = '\\' . $stringType;
        }

        /** Check for any rewrites and return the rewritten class name if available. */
        if (isset($this->data[$stringType])) {
            $type = $this->data[$stringType];
        }

        return $type;
    }
}
