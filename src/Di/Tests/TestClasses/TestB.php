<?php

/**
 * @copyright 2016 Joris Fritzsche
 * @license MIT
 * @author Joris Fritzsche (joris.fritzsche@outlook.com)
 */
declare(strict_types=1);

namespace Di\Tests\TestClasses;

class TestB
{
    public $a;

    public function __construct(TestA $a)
    {
        $this->a = $a;
    }
}
