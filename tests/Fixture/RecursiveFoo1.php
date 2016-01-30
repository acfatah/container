<?php

namespace Fixture;

use Fixture\Foo;

class RecursiveFoo1
{
    protected $foo;

    public function __construct(Foo $foo)
    {
        $this->foo = $foo;
    }

    public function getString()
    {
        $this->foo->getString();
    }
}
