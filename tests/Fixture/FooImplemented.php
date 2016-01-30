<?php

namespace Fixture;

use Fixture\FooInterface;

class FooImplemented implements FooInterface
{
    public function getString()
    {
        return 'foo';
    }
}
