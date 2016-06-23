<?php

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public function createContainer($resolvers)
    {
        return new Acfatah\Container\Container($resolvers);
    }

    public function testGetResolversMethod()
    {
        $resolvers = [
            [
                'class'      => 'Foo',
                'resolver'  => function () {
                    return new stdClass;
                },
            ],
            [
                'class'      => 'Bar',
                'resolver'  => 'stdClass'
            ],
            [
                'class'      => 'Baz',
                'resolver'  => new stdClass
            ]
        ];
        $container = $this->createContainer($resolvers);

        $this->assertSame(['Foo', 'Bar', 'Baz'], $container->getResolvers());
    }

    public function resolverProvider()
    {
        return [
            [
                // closure
                function () {
                    return new stdClass;
                }
            ],
            [
                // string class name
                'Fixture\Foo'
            ]
        ];
    }

    /**
     * @group set
     * @group get
     * @dataProvider resolverProvider
     */
    public function testHasSetGetAndRemoveMethods($resolver)
    {
        $container = $this->createContainer([]);

        $this->assertFalse($container->has('Foo'));

        $container->set('Foo', $resolver);

        $this->assertTrue($container->has('Foo'));

        $first = $container->get('Foo');
        $second = $container->get('Foo');

        $this->assertNotSame($second, $first);

        $container->remove('Foo');

        $this->assertFalse($container->has('Foo'));
    }

    /**
     * @group set
     * @group get
     * @dataProvider resolverProvider
     */
    public function testIssetSetGetAndUnsetAsArray($resolver)
    {
        $container = $this->createContainer([]);

        $this->assertFalse(isset($container['Foo']));

        $container['Foo'] = $resolver;

        $this->assertTrue(isset($container['Foo']));

        $first = $container['Foo'];
        $second = $container['Foo'];

        $this->assertNotSame($second, $first);

        unset($container['Foo']);

        $this->assertFalse(isset($container['Foo']));
    }

    /**
     * @group set
     */
    public function testSetMethodException()
    {
        $this->setExpectedException(
            '\Acfatah\Container\Exception\ContainerException'
        );

        $container = $this->createContainer([]);
        $container->set('Foo', ['foo', 'bar']);
    }

    /**
     * @group set
     * @group get
     */
    public function testHasSetGetAndRemoveNewInstance()
    {
        $container = $this->createContainer([]);

        $this->assertFalse($container->has('Foo'));

        $instance = new stdClass;
        $instance->id = md5(mt_rand());
        $container->set('Foo', $instance);

        $this->assertTrue($container->has('Foo'));

        $first = $container->get('Foo');
        $second = $container->get('Foo');

        $this->assertSame($second, $first);

        $container->remove('Foo');

        $this->assertFalse($container->has('Foo'));
    }

    /**
     * @group set
     * @group get
     */
    public function testSingleMethod()
    {
        $container = $this->createContainer([]);
        $container->single('Single', function () {
            return new stdClass;
        });

        $first = $container->get('Single');
        $second = $container->get('Single');

        $this->assertSame($first, $second);

        $container->remove('Single');

        $this->assertFalse($container->has('Single'));
    }

    /**
     * @group set
     */
    public function testSetNewMethod()
    {
        $container = $this->createContainer([]);
        $result = false;
        $container->setNew('New', function () use (&$result) {
            $result = true;
            return new stdClass;
        });

        $this->assertTrue($result);
    }

    public function invalidArrayConfigurationProvider()
    {
        return [
            [
                // primitive data
                'stdClass'
            ],
            [
                // an object
                new stdClass
            ],
            [
                // a key value array
                ['Foo' => function () {return new stdClass;}]
            ],
            [
                [
                    // no class key
                    'resolver' => function () {return new stdClass;}
                ]
            ],
            [
                [
                    // no resolver key
                    'class' => 'Foo'
                ]
            ],
            [
                [
                    // resolver class not exists
                    'class' => 'Foo',
                    'resolver' => 'UndefinedClass'
                ]
            ],
        ];
    }

    /**
     * @dataProvider invalidArrayConfigurationProvider
     */
    public function testSetFromArrayMethodException($config)
    {
        $this->setExpectedException(
            '\Acfatah\Container\Exception\ContainerException'
        );

        $container = $this->createContainer([$config]);
    }

    public function testEagerLoadSetFromArrayMethod()
    {
        $container = $this->createContainer([]);
        $result = false;
        $container->setFromArray([
            'class' => 'EagerLoad',
            'resolver' => function () use (&$result) {
                $result = true;
                return new stdClass;
            },
            'new' => true
        ]);

        $this->assertTrue($result);
    }

    public function testEagerLoadSetFromArrayConfiguration()
    {
        $result = false;
        $container = $this->createContainer([
            [
                'class' => 'EagerLoaded',
                'resolver' => function () use (&$result) {
                    $result = true;
                    return new stdClass;
                },
                'new' => true
            ],
        ]);

        $this->assertTrue($result);
    }

    public function testSetFromArrayMethodSingleInstance()
    {
        $container = $this->createContainer([]);
        $container->setFromArray([
            'class' => 'Single',
            'resolver' => function () {
                return new stdClass;
            },
            'single' => true
        ]);
        $first = $container->get('Single');
        $second = $container->get('Single');

        $this->assertSame($first, $second);
    }

    /**
     * @group get
     */
    public function testGetUndefinedResolver()
    {
        $this->setExpectedException(
            '\Acfatah\Container\Exception\NotFoundException'
        );

        $container = $this->createContainer([]);
        $container->get('foo');
    }

    public function nonObjectResolversProvider()
    {
        return [
            [
                function () {
                    // returns null
                }
            ],
            [
                function () {
                    // returns string
                    return 'foo';
                }
            ],
            [
                function () {
                    // returns integer
                    return 12345;
                }
            ],
            [
                function () {
                    // returns double
                    return 1.2345;
                }
            ],
            [
                function () {
                    // returns array
                    return [];
                }
            ],
            [
                function () {
                    // returns boolean
                    return true;
                }
            ],
            [
                function () {
                    // returns resource
                    return opendir(__DIR__);
                }
            ]
        ];
    }

    /**
     * @group get
     * @dataProvider nonObjectResolversProvider
     */
    public function testResolverReturnsNonObject($resolver)
    {
        $this->setExpectedException(
            '\Acfatah\Container\Exception\UnexpectedValueException'
        );

        $container = $this->createContainer([]);
        $container->set('Foo', $resolver);
        $container->get('Foo');
    }

    public function testContainerInstancePassedAsArgument()
    {
        $resolvers = [
            [
                'class' => 'Container',
                'resolver' => function (\Interop\Container\ContainerInterface $container) {
                    return $container;
                }
            ]
        ];
        $container = $this->createContainer($resolvers);

        $this->assertSame($container, $container->get('Container'));
    }

    /**
     * @group binding
     */
    public function testInterfaceBinding()
    {
        $container = $this->createContainer([]);
        $container->set('Fixture\FooInterface', function () {
            return new \Fixture\FooImplemented;
        });
        $requireFoo = $container->get('Fixture\RequireFooInterface');

        $this->assertInstanceOf('Fixture\FooInterface', $requireFoo->getFoo());
    }

    /**
     * @group binding
     */
    public function testInterfaceBindingNotFoundException()
    {
        $this->setExpectedException(
            '\Acfatah\Container\Exception\NotFoundException'
        );

        $container = $this->createContainer([]);
        $requireFoo = $container->get('Fixture\RequireFooInterface');
    }

    /**
     * @group binding
     */
    public function testInterfaceBindingException()
    {
        if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
            // introduced by php 7
            $this->setExpectedException('\TypeError');
        } else {
            $this->setExpectedException('\PHPUnit_Framework_Error');
        }

        $container = $this->createContainer([]);
        // bind non implemented class
        $container->set('Fixture\FooInterface', new stdClass);
        $requireFoo = $container->get('Fixture\RequireFooInterface');

    }

    public function objectBindingProvider()
    {
        return [
            [
                // callback form
                function () {
                    return new Fixture\Bar;
                }
            ],
            [
                // class name form
                'Fixture\Bar'
            ],
            [
                // new instance
                new Fixture\Bar
            ]
        ];
    }

    /**
     * @group binding
     * @dataProvider objectBindingProvider
     */
    public function testObjectBinding($resolver)
    {
        $container = $this->createContainer([]);
        $container->set('Fixture\Foo', $resolver);
        // inject 'Fixture\Bar'
        $foo = $container->get('Fixture\RequireFoo');

        $this->assertInstanceOf('Fixture\Foo', $foo->getFoo());
        $this->assertInstanceOf('Fixture\Bar', $foo->getFoo());
        $this->assertEquals('bar', $foo->getFoo()->getString());
    }

    public function objectBindingExceptionProvider()
    {
        return [
            [
                // callback form
                function () {
                    return new stdClass;
                }
            ],
            [
                // class name form
                'stdClass'
            ],
            [
                // new instance
                new stdClass
            ]
        ];
    }

    /**
     * @group binding
     * @dataProvider objectBindingExceptionProvider
     */
    public function testObjectBindingException($resolver)
    {
        if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
            // introduced by php 7
            $this->setExpectedException('\TypeError');
        } else {
            $this->setExpectedException('\PHPUnit_Framework_Error');
        }

        $container = $this->createContainer([]);
        $container->set('Fixture\Foo', $resolver);
        // inject non 'Fixture\Foo' instance
        $foo = $container->get('Fixture\RequireFoo');
    }

    public function testGetObjectConstructorArgumentArrayWithDefaultValue()
    {
        $container = $this->createContainer([]);
        $instance = $container->get(
            'Fixture\ConstructorArgumentArrayWithDefaultValue'
        );

        $this->assertInstanceOf(
            'Fixture\ConstructorArgumentArrayWithDefaultValue',
            $instance
        );
        $this->assertSame(['foo', 'bar'], $instance->getArray());
    }

    public function testGetObjectConstructorNotTypeHinted()
    {
        $this->setExpectedException(
            '\Acfatah\Container\Exception\ContainerException'
        );

        $container = $this->createContainer([]);
        $container->get('Fixture\ConstructorArgumentArray');
    }

    public function testGetObjectConstructorTypeHintClassNotExists()
    {
        $this->setExpectedException(
            '\Acfatah\Container\Exception\ContainerException'
        );

        $container = $this->createContainer([]);
        $container->get('Fixture\TypeHintClassNotExists');
    }

    /**
     * @group recursion
     */
    public function testRecursiveAutomaticResolution()
    {
        // \RuntimeException thrown if fail to inject \Fixture\Foo
        $container = $this->createContainer([]);
        $container->get('Fixture\RequireFoo');
    }

    /**
     * @group recursion
     */
    public function testRecursiveAutomaticResolutionRecursionCount()
    {
        // \RuntimeException thrown if fail to inject \Fixture\Foo
        $container = $this->createContainer([]);

        for ($i=0; $i<100; $i++) {
            $container->get('Fixture\RequireFoo');
        }
    }

    /**
     * @group recursion
     */
    public function testInfiniteRecursion()
    {
        $this->setExpectedException(
            '\Acfatah\Container\Exception\ContainerException'
        );

        $container = $this->createContainer([]);
        $container->get('Fixture\InfiniteRecursion');
    }

    /**
     * @group recursion
     */
    public function testSetMaxRecursion()
    {
        $this->setExpectedException(
            '\Acfatah\Container\Exception\ContainerException',
            'Class "Fixture\InfiniteRecursion" exceeds maximum recursion count of 5 times!'
        );

        $container = $this->createContainer([]);
        $container->setMaxRecursion(5);

        $container->get('Fixture\InfiniteRecursion');
    }

    public function testSetMaxRecursionInvalidArgument()
    {
        $this->setExpectedException(
            '\Acfatah\Container\Exception\InvalidArgumentException'
        );

        $container = $this->createContainer([]);
        $container->setMaxRecursion(0);
    }

    /**
     * @group recursion
     */
    public function testInfiniteRecursionCallbackResolver()
    {
        $this->setExpectedException(
            '\Acfatah\Container\Exception\ContainerException'
        );

        $container = $this->createContainer([
            [
                'class' => 'Foo',
                'resolver' => function ($c) {
                    return $c->get('Bar');
                }
            ],
            [
                'class' => 'Bar',
                'resolver' => function ($c) {
                    return $c->get('Foo');
                }
            ]
        ]);

        $container->get('Foo');
    }
}
