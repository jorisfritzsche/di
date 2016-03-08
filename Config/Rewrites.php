<?php

namespace Di\Config;


class Rewrites extends AbstractConfig
{
    const CONFIG_FILE = 'rewrites.json';

    /**
     * @param array $rewrite
     *
     * @return $this
     */
    public function addRewrite(array $rewrite)
    {
        $this->config = array_merge_recursive($this->config, $rewrite);

        return $this;
    }

    /**
     * Process any rewrites found in the DI config.
     *
     * @param \ReflectionType $type
     *
     * @return \ReflectionType|string
     */
    public function processRewrites(\ReflectionType $type)
    {
        /** Get the type's class name and make sure it starts in the root namespace. */
        $stringType = (string) $type;
        if (strpos($stringType, '\\') !== 0) {
            $stringType = '\\' . $stringType;
        }

        /** Check for any rewrites and return the rewritten class name if available. */
        if (isset($this->config[$stringType])) {
            $type = $this->config[$stringType];
        }

        return $type;
    }
}