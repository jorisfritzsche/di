<?php

/**
 * @copyright 2016 Joris Fritzsche
 * @license MIT
 * @author Joris Fritzsche (joris.fritzsche@outlook.com)
 */

declare(strict_types = 1);


namespace Di\Tests\TestClasses;


class TestG
{
    public $g;

    public function __construct(string $g = 'default_value')
    {
        $this->g = $g;
    }
}
