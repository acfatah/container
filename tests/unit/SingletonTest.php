<?php

class SingletonTest extends \PHPUnit_Framework_TestCase
{
    public function testConstantDefinition()
    {
        $singleton = new Acfatah\Container\Singleton('Some string...');

        $this->assertTrue(is_callable($singleton));
        $this->assertEquals('Some string...', $singleton());
    }

    public function testClosureReturnsConstant()
    {
        $singleton = new Acfatah\Container\Singleton(function () {
            return 'Some string...';
        });

        $this->assertTrue(is_callable($singleton));
        $this->assertEquals('Some string...', $singleton());
    }

    public function testReturnsSingletonInstance()
    {
        $singleton = new Acfatah\Container\Singleton(function () {
            return md5(uniqid());
        });

        $random = $singleton();
        $this->assertSame($random, $singleton());
    }
}
