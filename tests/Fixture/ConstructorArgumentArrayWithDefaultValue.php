<?php

namespace Fixture;

class ConstructorArgumentArrayWithDefaultValue
{
    protected $array;

    public function __construct(array $array = ['foo', 'bar'])
    {
        $this->array = $array;
    }

    public function getArray()
    {
        return $this->array;
    }
}
