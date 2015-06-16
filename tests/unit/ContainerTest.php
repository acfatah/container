<?php

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public function createContainer($definitions)
    {
        return new Acfatah\Container\Container($definitions);
    }

    public function getFoo()
    {
        return 'foo';
    }

    public function testHas()
    {
        $definitions = [
            'foo' => function () {
                return 'foo';
            }
        ];
        $container = $this->createContainer($definitions);

        $this->assertTrue($container->has('foo'));
        $this->assertFalse($container->has('bar'));
    }

    public function testGetIdentifiers()
    {
        $definitions = [
            'foo' => function () {
                return 'foo';
            },
            'bar' => new stdClass,
            'baz' => 'baz'
        ];
        $container = $this->createContainer($definitions);

        $expected = array_keys($definitions);
        $this->assertSame($expected, $container->getIndentifiers());
    }

    public function testSetAndRemove()
    {
        $container = $this->createContainer([]);

        $this->assertFalse($container->has('foo'));

        $container->set('foo', 'foo');

        $this->assertTrue($container->has('foo'));

        $container->remove('foo');

        $this->assertFalse($container->has('foo'));
    }

    public function testGetUndefinedDefinition()
    {
        $this->setExpectedException('Acfatah\Container\NotFoundException');

        $container = $this->createContainer([]);
        $container->get('foo');
    }

    public function testGetAClosure()
    {
        $definition = [
            'foo' => function () {
                return 'foo';
            }
        ];
        $container = $this->createContainer($definition);

        $this->assertTrue($container->get('foo', false) instanceof \Closure);
        $this->assertEquals('foo', $container->get('foo'));
        $this->assertSame($container->get('foo'), $container['foo']);
    }

    public function testContainerInstancePassedAsArgument()
    {
        $definitions = [
            'container' => function ($container) {
                return $container;
            }
        ];
        $container = $this->createContainer($definitions);

        $this->assertSame($container, $container->get('container'));
    }

    public function testGetACallable()
    {
        $definitions = [
            'foo' => [$this, 'getFoo']
        ];
        $container = $this->createContainer($definitions);

        $this->assertTrue(is_callable($container->get('foo', false)));
        $this->assertEquals('foo', $container->get('foo'));
        $this->assertSame($container->get('foo'), $container['foo']);
    }

    public function testGetAnObjectInstance()
    {
        $definitions = ['foo' => new \Fixture\Foo];
        $container = $this->createContainer($definitions);

        $this->assertTrue($container->get('foo') instanceof \Fixture\Foo);
        $this->assertSame($container->get('foo'), $container['foo']);
        $this->assertEquals('foo', $container->get('foo')->getString());
    }

    public function testRecursiveGet()
    {
        $definitions = [
            'string' => 'foo',
            'text' => function ($container) {
                return $container->get('string');
            },
            'foo' => function ($container) {
                return $container->get('text');
            }
        ];
        $container = $this->createContainer($definitions);

        $this->assertEquals('foo', $container->get('foo'));
    }

    public function testClone()
    {
        $foo = new stdClass;
        $foo->string = 'foo';

        $definitions = [
            'foo' => $foo,
            'hello' => 'hello world!'
        ];
        $container = $this->createContainer($definitions);

        $clone = clone $container;
        $foo->string = 'bar';

        $result = $clone->get('foo')->string;
        $this->assertNotEquals('bar', $result);
        $this->assertEquals('foo', $result);
    }

    public function testEagerLoad()
    {
        $definitions = [
            'random' => md5(rand())
        ];
        $container = $this->createContainer($definitions);
        $first = $container->get('random');
        $second = $container->get('random');

        $this->assertSame($first, $second);
    }

    public function testLazyLoad()
    {
        $definitions = [
            'random' => function () {
                return md5(rand());
            }
        ];
        $container = $this->createContainer($definitions);
        $first = $container->get('random');
        $second = $container->get('random');

        $this->assertNotSame($first, $second);
    }

    public function testEagerLoadNewInstance()
    {
        $string = null;
        $definitions = [
            'foo' => new Acfatah\Container\NewInstance(
                function (
                    \Interop\Container\ContainerInterface $container
                ) use (&$string) {
                    $string = 'foo';
                }
            )
        ];
        $container = $this->createContainer($definitions);

        $this->assertNotNull($string);
        $this->assertEquals('foo', $string);
    }

    public function testEagerLoadNewInstanceMethod()
    {
        $string = null;
        $container = $this->createContainer([]);
        $container->set(
            'foo',
            function (
                \Interop\Container\ContainerInterface $container
            ) use (&$string) {
                $string = 'foo';
            },
            true
        );

        $this->assertNotNull($string);
        $this->assertEquals('foo', $string);
    }

    public function testMerge()
    {
        $container = $this->createContainer([]);

        $definition1 = ['foo' => null];
        $definition2 = [
            'foo' => false,
            'bar' => null
        ];
        $definition3 = new \ArrayObject(
            [
                'foo' => 'foo',
                'bar' => 'bar',
                'baz' => 'baz'
            ]
        );

        $container->merge($definition1, $definition2, $definition3);

        $this->assertSame(
            [
                'foo' => 'foo',
                'bar' => 'bar',
                'baz' => 'baz'
            ],
            $container->getArrayCopy()
        );
    }

    public function testInvalidMerge()
    {
        $this->setExpectedException('InvalidArgumentException');

        $container = $this->createContainer([]);

        $definition1 = [];
        $definition2 = 'some string';

        $container->merge($definition1, $definition2);
    }

    public function testSerializeUnserialize()
    {
        $definitions = [
            'foo_string' => 'foo',
            'foo_object' => new \Fixture\Foo,
            'foo_closure'   => function () {
                return new \Fixture\Foo;
            }
        ];

        $container = $this->createContainer($definitions);

        $this->assertInstanceOf('Serializable', $container);

        $serialized = serialize($container);
        $unserialized = unserialize($serialized);

        $this->assertEquals($unserialized->get('foo_string'), $definitions['foo_string']);
        $this->assertEquals($unserialized->get('foo_object'), $definitions['foo_object']);
        $this->assertEquals($unserialized->get('foo_closure'), $definitions['foo_closure']());
    }
}
