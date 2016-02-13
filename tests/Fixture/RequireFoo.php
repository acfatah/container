<?php

namespace Fixture;

use RuntimeException;
use Fixture\Foo;

class RequireFoo
{
    protected $foo;

    public function __construct(Foo $foo)
    {
        if (!$foo instanceof Foo) {
            throw new RuntimeException('Constructor argument is not an instance of \Fixture\Foo');
        }

        $this->foo = $foo;
    }

    public function getFoo()
    {
        return $this->foo;
    }
}
