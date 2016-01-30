<?php

namespace Fixture;

use Fixture\RecursiveFoo1;

class RecursiveFoo2
{
    protected $foo;

    public function __construct(RecursiveFoo1 $foo)
    {
        $this->foo = $foo;
    }

    public function getString()
    {
        $this->foo->getString();
    }
}
