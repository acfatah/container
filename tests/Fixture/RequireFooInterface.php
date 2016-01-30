<?php

namespace Fixture;

use Fixture\FooInterface;

class RequireFooInterface
{
    protected $foo;

    public function __construct(FooInterface $foo)
    {
        $this->foo = $foo;
    }

    public function getFoo()
    {
        return $this->foo;
    }
}
