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
     * @var int Maximum depth level of automatic resolution.
     */
    protected $maxDepth = 3;

    /**
     *
     * @var array Automatic resolution depth count.
     */
    private static $depthCount;

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
                $m = 'Resolver configuration is not an array!';
                throw new ContainerException($m);
            }
            $this->setFromArray($config, false);
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
        // reset depth count after creation
        self::$depthCount = null;
        // instance is null
        if (!is_object($instance)) {
            $m = 'Resolver for "%s" returns non object of type "%s"!';
            throw new UnexpectedValueException(sprintf(
                $m,
                $name,
                gettype($instance)
            ));
        }
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
        // container exception
        if (is_string($resolver) && !class_exists($resolver)) {
            $m = 'Unable to bind "%s". Class "%s" does not exists!';
            throw new ContainerException(sprintf($m, $name, $resolver));
        } else {
            $m = 'Unable to bind "%s". The resolver is not an object'
                . ' instance or a callback!';
        }
        throw new ContainerException(sprintf($m, $name));
    }

    /**
     * Sets a resolver from a configuration array.
     *
     * @param array $config
     * @param boolean $load Wether to eager load the resolver
     * @return \Acfatah\Container\Container
     * @throws \Acfatah\Container\Exception\ContainerException
     */
    public function setFromArray(array $config, $load = true)
    {
        // check "name" key
        if (!array_key_exists('name', $config)) {
            // invalid configuration structure, has no name
            $m = 'Resolver configuration array has no "name" key!';
            throw new ContainerException($m);
        }
        // set resolver
        if (isset($config['resolver'])) {
            $this->setResolverFromArray($config);
        } else {
            $m = 'Resolver configuration array has no "resolver" key!';
            throw new ContainerException($m);
        }
        // eager loading
        if ($load && isset($config['new']) && true === $config['new']) {
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
     * Sets the maximum depth level of automatic resolution.
     *
     * An object can only create itself multiple time less than the maximum
     * depth within a single call.
     *
     * Default value is 3 levels.
     *
     * @param int $depth
     * @return \Acfatah\Container\Container
     */
    public function setMaxDepth($depth)
    {
        $this->maxDepth = intval($depth);

        return $this;
    }

    /**
     * Sets a resolver from a configuration array.
     *
     * @param array $config
     * @return \Acfatah\Container\Container
     */
    protected function setResolverFromArray($config)
    {
        // check "single" key
        if (isset($config['single']) && true === $config['single']) {
            $this->single($config['name'], $config['resolver']);
        } else {
            $this->set($config['name'], $config['resolver']);
        }

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
            return call_user_func($this->resolver[$name], $this);
        }
        // create instance from bound class name
        if (isset($this->resolver[$name])
            && is_string($this->resolver[$name])
        ) {
            return $this->create($this->resolver[$name]);
        }
        // automatic resolution
        if (class_exists($name)) {
            // memorize depth
            self::$depthCount[$name] = isset(self::$depthCount[$name])
                ? self::$depthCount[$name]+1 : 1;
            // verify depth
            if (self::$depthCount[$name] > $this->maxDepth) {
                // throw exception if exceeds maximum depth
                $m = 'Recursive resolution exceeds maximum depth %s by'
                    . ' "%s" class!';
                throw new ContainerException(sprintf(
                    $m,
                    $this->maxDepth,
                    $name
                ));
            }
            // create the instance using name as a class name
            return $this->create($name);
        }
        // unable to resolve class name or resolver not defined
        $m = 'Resolver for "%s" is not defined!';
        throw new NotFoundException(sprintf($m, $name));
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
                $m = 'Type-hint error "%s" for "%s" class constructor!';
                throw new ContainerException(sprintf(
                    $m,
                    $re->getMessage(),
                    $reflectionParameter->getDeclaringClass()->getName()
                ));
            }
            // argument required but not a type-hinted class name
            if (!$reflectionParameter->getClass() instanceof ReflectionClass) {
                $m = 'Unable to create constructor argument "%s" for'
                    . ' "%s" class!';
                throw new ContainerException(sprintf(
                    $m,
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
