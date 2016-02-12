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

use ArrayAccess;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use Interop\Container\ContainerInterface;
use Acfatah\Container\Exception\ContainerException;
use Acfatah\Container\Exception\NotFoundException;
use Acfatah\Container\Exception\UnexpectedValueException;
use Acfatah\Container\Exception\InvalidArgumentException;

/**
 * Dependency injection container class.
 *
 * This class is an inversion of control container. It is used to bind a class
 * to a resolver and create its dependencies.
 *
 * Simply, resolver is a callback used to create an object instance and inject
 * its dependencies either by constructor or method injection. The container
 * reference is passed as an argument to the callback.
 *
 * It can also be a class name. The container will  automagically create
 * the class dependencies that can be type-hinted and inject them by
 * constructor injection.
 */
class Container implements ArrayAccess, ContainerInterface
{
    /**
     * @var array An array of callbacks or bound class name.
     */
    protected $resolver;

    /**
     * @var array Loaded instances.
     */
    protected $instance;

    /**
     * @var array An array of singleton resolver names.
     */
    protected $singles = [];

    /**
     * @var int Maximum recursion count of automatic resolution.
     */
    protected $maxRecursion = 3;

    /**
     * @var array Automatic resolution recursion count.
     */
    private static $recursionCount;

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
            if (!is_array($config)) {
                // invalid configuration structure
                $msg = 'Resolver configuration is not an array!';
                throw new ContainerException($msg);
            }
            $this->setFromArrayUnresolved($config);
            // eager loading
            if (isset($config['new']) && true === $config['new']) {
                $newInstances[] = $config['name'];
            }
        }
        // eager load resolvers after initialization
        foreach ($newInstances as $name) {
            $this->instance[$name] = $this->get($name);
        }
    }

    /**
     * Resolve the object instance.
     *
     * If a name is bound, the resolver will be invoked as a callback.
     * If not then the name itself will be resolved as a class name.
     *
     * The container referrence will be passed as an argument to the callback
     * if the resolver is callable.
     *
     * > Note: Since Container implements ArrayAccess, accessing it as an array
     * > invokes this method.
     *
     * @param string $name
     * @return mixed
     * @throws \Acfatah\Container\Exception\UnexpectedValueException
     * @link https://github.com/container-interop/container-interop/blob/master/src/Interop/Container/ContainerInterface.php
     */
    public function get($name)
    {
        // return new or single instance if already created
        if (isset($this->instance[$name])) {
            return $this->instance[$name];
        }
        // resolve the instance
        $instance = $this->resolveInstance($name);
        // reset recursion count after creation
        self::$recursionCount = null;
        // store single instance
        if (in_array($name, $this->singles)) {
            $this->instance[$name] = $instance;
        }
        return $instance;
    }

    /**
     * Gets names of all registered resolvers.
     *
     * @return array
     */
    public function getNames()
    {
        return array_merge(
            array_keys($this->resolver),
            array_keys($this->instance)
        );
    }

    /**
     * @link https://github.com/container-interop/container-interop/blob/master/src/Interop/Container/ContainerInterface.php
     */
    public function has($name)
    {
        return isset($this->instance[$name]) || isset($this->resolver[$name]);
    }

    /**
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * Removes a resolver.
     *
     * @param string $name
     * @return \Acfatah\Container\Container
     */
    public function remove($name)
    {
        // remove instance if any
        unset($this->instance[$name]);
        // remove resolver
        unset($this->resolver[$name]);
        // remove single
        $pos = array_search($name, $this->singles);
        if (false !== $pos) {
            unset($this->singles[$pos]);
        }

        return $this;
    }

    /**
     * Sets a resolver to a name.
     *
     * @param string $name
     * @param mixed $resolver An object instance, a string class name
     *  or a callback
     * @return \Acfatah\Container\Container
     * @throws \Acfatah\Container\Exception\ContainerException
     */
    public function set($name, $resolver)
    {
        // clear previous state if already being set
        $this->remove($name);
        // an object instance
        if (is_object($resolver) && !is_callable($resolver)) {
            $this->instance[$name] = $resolver;

            return $this;
        }
        // a callable
        if (is_callable($resolver)) {
            $this->resolver[$name] = $resolver;

            return $this;
        }
        // a string class name
        if (is_string($resolver) && class_exists($resolver)
        ) {
            $this->resolver[$name] = $resolver;

            return $this;
        }
        $msg = 'Unable to bind "%s". The resolver is not an object'
            . ' instance or a callback!';
        // container exception
        if (is_string($resolver) && !class_exists($resolver)) {
            $msg = 'Unable to bind "%s". Class "%s" does not exists!';
            throw new ContainerException(sprintf($msg, $name, $resolver));
        }
        throw new ContainerException(sprintf($msg, $name));
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
        $this->setFromArrayUnresolved($config);
        // eager loading
        if (isset($config['new']) && true === $config['new']) {
            $this->instance[$config['name']] = $this->get($config['name']);
        }

        return $this;
    }

    /**
     * Sets a resolver to return singleton instance.
     *
     * @param string $name
     * @param callable|string $resolver
     * @return \Acfatah\Container\Container
     */
    public function single($name, $resolver)
    {
        $this->set($name, $resolver);
        $this->singles[] = $name;

        return $this;
    }

    /**
     * Sets a resolver and eager loads it.
     *
     * >> Note: New instance is always a single instance.
     *
     * @param string $name
     * @param callable|string $resolver
     * @return \Acfatah\Container\Container
     */
    public function setNew($name, $resolver)
    {
        $this->set($name, $resolver);
        $this->instance[$name] = $this->get($name);

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
        if (intval($count) < 1) {
            throw new InvalidArgumentException(
                'Invalid maximum recursion count!'
            );
        }
        $this->maxRecursion = intval($count);

        return $this;
    }

    protected function setFromArrayUnresolved(array $config)
    {
        // check "name" key
        if (!array_key_exists('name', $config)) {
            // invalid configuration structure, has no name
            $msg = 'Resolver configuration array has no "name" key!';
            throw new ContainerException($msg);
        }
        // check "resolver" key
        if (!isset($config['resolver'])) {
            $msg = 'Resolver configuration array has no "resolver" key!';
            throw new ContainerException($msg);

        }
        // set resolver
        $this->setResolverFromArray($config);

        return $this;
    }

    /**
     * Sets a resolver from a configuration array.
     *
     * @param array $config
     * @return \Acfatah\Container\Container
     */
    protected function setResolverFromArray(array $config)
    {
        // check "single" key
        isset($config['single']) && true === $config['single']
            ? $this->single($config['name'], $config['resolver'])
            : $this->set($config['name'], $config['resolver']);

        return $this;
    }

    /**
     * Resolve a name as a class instance.
     *
     * @param string $name
     * @return mixed
     * @throws \Acfatah\Container\Exception\ContainerException
     * @throws \Acfatah\Container\Exception\NotFoundException
     */
    protected function resolveInstance($name)
    {
        // invoke callback if callable
        if (isset($this->resolver[$name])
            && is_callable($this->resolver[$name])
        ) {
            $instance = call_user_func($this->resolver[$name], $this);
            // instance is not an object
            if (!is_object($instance)) {
                $msg = 'Resolver for "%s" returns non object of type "%s"!';
                throw new UnexpectedValueException(sprintf(
                    $msg, $name, gettype($instance)
                ));
            }
            $this->countRecursion(get_class($instance));
            return $instance;
        }
        // create instance from bound class name
        if (isset($this->resolver[$name])
            && is_string($this->resolver[$name])
        ) {
            $this->countRecursion($this->resolver[$name]);
            // create the instance using resolver as a class name
            return $this->create($this->resolver[$name]);
        }
        // automatic resolution
        if (class_exists($name)) {
            $this->countRecursion($name);
            // create the instance using name as a class name
            return $this->create($name);
        }
        // unable to resolve class name or resolver not defined
        $msg = 'Resolver for "%s" is not defined!';
        throw new NotFoundException(sprintf($msg, $name));
    }

    /**
     * Count recursion of a class creation.
     *
     * @param string $className
     * @throws ContainerException If exceeds class::maxRecursion
     */
    protected function countRecursion($className)
    {
        // count increment
        self::$recursionCount[$className] =
            isset(self::$recursionCount[$className])
                ? self::$recursionCount[$className] + 1
                : 1;
        // verify count
        if (self::$recursionCount[$className] > $this->maxRecursion) {
            // throw exception if exceeds maximum count
            $msg = 'Class "%s" exceeds maximum recursion count of %s times!';
            throw new ContainerException(sprintf(
                $msg,
                $className,
                $this->maxRecursion
            ));
        }
    }

    /**
     * Create an object instance based on class name.
     *
     * @param string $class
     * @return mixed
     */
    protected function create($class)
    {
        $reflectionClass = new ReflectionClass($class);
        $constructor = $reflectionClass->getConstructor();
        // resolve constructor parameter if any
        if (isset($constructor)) {
            return $reflectionClass->newInstanceArgs(
                $this->resolveParameters($constructor)
            );
        }
        return $reflectionClass->newInstance();
    }

    /**
     * Resolve an object parameters.
     *
     * @param ReflectionMethod $constructor
     * @return array
     * @throws \Acfatah\Container\Exception\ContainerException
     */
    protected function resolveParameters(ReflectionMethod $constructor)
    {
        $arguments = [];
        /* @var $reflectionParameter \ReflectionParameter */
        foreach ($constructor->getParameters() as $reflectionParameter) {
            // use default value if available
            if ($reflectionParameter->isDefaultValueAvailable()) {
                $arguments[] = $reflectionParameter->getDefaultValue();
                continue;
            }
            // check if type-hint class exists
            try {
                $reflectionParameter->getClass();
            } catch (ReflectionException $re) {
                // rethrow as \Acfatah\Container\Exception\ContainerException
                $msg = 'Type-hint error "%s" for "%s" class constructor!';
                throw new ContainerException(sprintf(
                    $msg,
                    $re->getMessage(),
                    $reflectionParameter->getDeclaringClass()->getName()
                ));
            }
            // argument required but not a type-hinted class name
            if (!$reflectionParameter->getClass() instanceof ReflectionClass) {
                $msg = 'Unable to create constructor argument "%s" for'
                    . ' "%s" class!';
                throw new ContainerException(sprintf(
                    $msg,
                    $reflectionParameter->getPosition(),
                    $reflectionParameter->getDeclaringClass()->getName()
                ));
            }
            // resolve type-hint argument from container
            $arguments[] = $this->get(
                $reflectionParameter->getClass()->getName()
            );
        }
        return $arguments;
    }
}
