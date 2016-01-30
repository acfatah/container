<?php

namespace Fixture;

use Fixture\RecursiveFoo2;

class RecursiveFoo3
{
    protected $foo;

    public function __construct(RecursiveFoo2 $foo)
    {
        $this->foo = $foo;
    }

    public function getString()
    {
        return $this->foo->getString();
    }
}
