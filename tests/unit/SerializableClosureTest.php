<?php

class SerializableClosureTest extends \PHPunit_Framework_TestCase
{
    protected $closure;
    protected $serializable;
    protected $serialized;

    public function setUp()
    {
        $hello = 'hello';

        $this->closure = function () use ($hello) {
            return $hello . ' world!';
        };

        $this->serializable = new Acfatah\Container\SerializableClosure($this->closure);
        $this->serialized = serialize($this->serializable);
    }

    public function testUnserialize()
    {
        $unserialized = unserialize($this->serialized);
        $this->assertNotFalse($unserialized);
        $this->assertTrue(is_callable($unserialized));
        $this->assertEquals('hello world!', $unserialized());
    }

    public function testGetClosure()
    {
        $closure = $this->closure;
        $serializable = unserialize($this->serialized);
        $unserialized_closure = $serializable->getClosure();
        $expected = new \ReflectionFunction($closure);
        $actual = new \ReflectionFunction($unserialized_closure);

        $this->assertEquals(
            $expected->getNumberOfParameters(),
            $actual->getNumberOfParameters()
        );
        $this->assertEquals(
            $expected->getNumberOfRequiredParameters(),
            $actual->getNumberOfRequiredParameters()
        );
        $this->assertEquals(
            $expected->getParameters(),
            $actual->getParameters()
        );
        $this->assertEquals(
            $expected->getStaticVariables(),
            $actual->getStaticVariables()
        );
        $this->assertEquals($closure(), $unserialized_closure());
    }
}
