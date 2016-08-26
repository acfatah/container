<?php

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE file.
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright (c) 2016, Achmad F. Ibrahim
 * @link https://github.com/acfatah/container
 * @license http://opensource.org/licenses/mit-license.php The MIT License (MIT)
 */

namespace Acfatah\Container;
use Acfatah\Container\AbstractContainer;
use Acfatah\Container\Resolver\Config;
use Acfatah\Container\Resolver\AbstractResolver;
use Acfatah\Container\Resolver\CallableResolver;
use Acfatah\Container\Resolver\ReflectionResolver;
use Acfatah\Container\Exception\ContainerException;
use Acfatah\Container\Exception\NotFoundException;
use Acfatah\Container\Exception\InvalidArgumentException;

/**
 * Dependency injection container class.
 *
 * This class is an inversion of control container. It is used to bind a class
 * to a resolver and create its dependencies.
 *
 * Simply, resolver is a callback used to create an object instance and inject
 * its dependencies either by constructor or method injection. The container
 * reference is passed as an argument to the callback and can be used to
 * resolve other dependant objects.
 *
 * It can also be a class name string. The container will automagically create
 * the class dependencies that can be type-hinted and inject them by
 * constructor injection.
 */
class Container extends AbstractContainer
{
    /**
     * @var \Acfatah\Container\ResolverInterface[] An array of resolvers.
     */
    protected $resolver;

    /**
     * @var array An array of singleton resolver names.
     */
    protected $singles = [];

    /**
     * @var int Maximum recursion count of automatic resolution.
     */
    protected $maxRecursion = 3;

    /**
     * Constructor.
     *
     * @param array $configurations An array of resolvers
     * @throws \Acfatah\Container\Exception\ContainerException
     */
    public function __construct($configurations = [])
    {
        // initialize configurations
        $newInstances = [];
        foreach ($configurations as $config) {
            // configuration is not an array
            if (!is_array($config)) {
                $msg = 'Resolver configuration is not an array!';
                throw new ContainerException($msg);
            }

            $config = new Config($config);

            // check single
            $config->isSingle()
                ? $this->single($config->getClass(), $config->getResolver())
                : $this->set($config->getClass(), $config->getResolver());

            // eager loading flag
            if ($config->isNew()) {
                $newInstances[] = $config->getClass();
            }
        }

        // eager load resolvers after initialization
        foreach ($newInstances as $class) {
            $this->resolver[$class] = $this->get($class);
        }
    }

    /**
     * Creates an object and resolve its dependencies.
     *
     * If a class is bound, the container will use the resolver to resolve the
     * object. If not then the class name itself will be used to resolved the
     * object.
     *
     * The container instance will be passed as an argument to the callback
     * if the resolver is a callable.
     *
     * > Note: Since Container implements ArrayAccess, accessing it as an array
     * > invokes this method.
     *
     * @param string $class
     * @return mixed
     * @throws \Acfatah\Container\Exception\UnexpectedValueException
     * @link https://github.com/container-interop/container-interop/blob/master/src/Interop/Container/ContainerInterface.php
     */
    public function get($class)
    {
        // resolver is not set
        if (!isset($this->resolver[$class])) {
            // but class exists
            if (class_exists($class)) {
                // create a new class instance
                return $this->classnameResolver($class)->resolve();
            }
            $msg = 'Resolver for "%s" is not defined!';
            throw new NotFoundException(sprintf($msg, $class));
        }

        // resolver is a class instance
        if (!$this->resolver[$class] instanceof AbstractResolver) {
            return $this->resolver[$class];
        }

        // resolver is a single instance
        if (in_array($class, $this->singles)) {
                $this->resolver[$class] = $this->resolver[$class]->resolve();
                return $this->resolver[$class];
            }

        // resolve
        return $this->resolver[$class]->resolve();
    }

    /**
     * Gets class names of all defined resolvers.
     *
     * @return string[]
     */
    public function getResolvers()
    {
        return array_keys($this->resolver);
    }

    /**
     * @link https://github.com/container-interop/container-interop/blob/master/src/Interop/Container/ContainerInterface.php
     */
    public function has($class)
    {
        return isset($this->resolver[$class]);
    }

    /**
     * Removes a class resolver.
     *
     * @param string $class
     * @return \Acfatah\Container\Container
     */
    public function remove($class)
    {
        // remove single flag
        $pos = array_search($class, $this->singles);
        if (false !== $pos) {
            unset($this->singles[$pos]);
        }

        // remove the resolver
        unset($this->resolver[$class]);

        return $this;
    }

    /**
     * Sets a class resolver.
     *
     * @param string $class
     * @param mixed $resolver An object instance, a string class name
     *  or a callback
     * @return \Acfatah\Container\Container
     * @throws \Acfatah\Container\Exception\ContainerException
     */
    public function set($class, $resolver)
    {
        // clear previous state if already being set
        $this->remove($class);

        // an object instance
        if (is_object($resolver) && !is_callable($resolver)) {
            $this->resolver[$class] = $resolver;

            return $this;
        }

        // a callable
        if (is_callable($resolver)) {
            $this->resolver[$class] = $this->callbackResolver(
                $class,
                $resolver
            );

            return $this;
        }

        // a string class name
        if (is_string($resolver) && class_exists($resolver)) {
            $this->resolver[$class] = $this->classnameResolver($resolver);

            return $this;
        }

        // invalid string resolver
        if (is_string($resolver) && !class_exists($resolver)) {
            $msg = 'Unable to bind "%s". Class "%s" does not exists!';
            throw new ContainerException(sprintf($msg, $class, $resolver));
        }

        // invalid resolver
        $msg = 'Unable to bind "%s". The resolver is not an object'
            . ' instance or a callback!';
        throw new ContainerException(sprintf($msg, $class));

    }

    /**
     * Sets a resolver from a configuration array.
     *
     * @param array $config
     * @return \Acfatah\Container\Container
     * @throws \Acfatah\Container\Exception\ContainerException
     */
    public function setFromArray(array $config)
    {
        $resolver = new Config($config);

        // new instance
        if ($resolver->isNew()) {
            $this->setNew($resolver->getClass(), $resolver->getResolver());

            return $this;
        }

        // single instance
        $resolver->isSingle()
            ? $this->single($resolver->getClass(), $resolver->getResolver())
            : $this->set($resolver->getClass(), $resolver->getResolver());

        return $this;
    }

    /**
     * Sets a resolver to return singleton instance.
     *
     * @param string $class
     * @param callable|string $resolver
     * @return \Acfatah\Container\Container
     */
    public function single($class, $resolver)
    {
        $this->set($class, $resolver);
        $this->singles[] = $class;

        return $this;
    }

    /**
     * Sets a resolver and eager loads it.
     *
     * >> Note: New instance is always a single instance.
     *
     * @param string $class
     * @param callable|string $resolver
     * @return \Acfatah\Container\Container
     */
    public function setNew($class, $resolver)
    {
        $this->set($class, $resolver);
        if ($this->resolver[$class] instanceof AbstractResolver) {
            $this->resolver[$class] = $this->resolver[$class]->resolve();
        }

        return $this;
    }

    /**
     * Sets the maximum recursion count of automatic resolution.
     *
     * An object can only create itself multiple time less than the maximum
     * recursion within a single call.
     *
     * The default value is 3 times.
     *
     * @param int $count
     * @return \Acfatah\Container\Container
     */
    public function setMaxRecursion($count)
    {
        $count = intval($count);
        if ($count < 1) {
            throw new InvalidArgumentException(
                'Invalid maximum recursion count!'
            );
        }
        $this->maxRecursion = $count;

        return $this;
    }

    /**
     * Creates a resolver class instance from a class name.
     *
     * @param string $class
     * @return \Acfatah\Container\Resolver\ReflectionResolver
     */
    protected function classnameResolver($class)
    {
        return new ReflectionResolver($this, $class, $this->maxRecursion);
    }

    /**
     * Creates a resolver class instance from a callback.
     *
     * @param string $class
     * @param callable $callback
     * @return \Acfatah\Container\Resolver\CallableResolver
     */
    protected function callbackResolver($class, $callback)
    {
        return new CallableResolver($this, $class, $callback, $this->maxRecursion);
    }
}
