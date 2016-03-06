<?php

/*
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE file.
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright (c) 2016, Achmad F. Ibrahim
 * @link https://github.com/acfatah/container
 * @license http://opensource.org/licenses/mit-license.php The MIT License (MIT)
 */

namespace Acfatah\Container\Resolver;

use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use Interop\Container\ContainerInterface;
use Acfatah\Container\Resolver\AbstractResolver;
use Acfatah\Container\Exception\ContainerException;

/**
 * Automatic object resolution class.
 *
 * @author Achmad F. Ibrahim <acfatah@gmail.com>
 */
class ReflectionResolver extends AbstractResolver
{
    /**
     * @var Interop\Container\ContainerInterface The container instance.
     */
    protected $container;

    /**
     * @var string The class name.
     */
    protected $className;

    /**
     * @var int Maximum recursion count of automatic resolution.
     */
    protected $maxRecursion;

    /**
     * @var array Recursion count.
     */
    private static $recursionCount;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param string $className
     * @param int $maxRecursion
     */
    public function __construct(
        ContainerInterface $container,
        $className,
        $maxRecursion
    ) {
        $this->container = $container;
        $this->className = $className;
        $this->maxRecursion = $maxRecursion;
    }

    /**
     * Resolves the class name to an class instance.
     *
     * @param string $className
     * @return mixed
     */
    public function resolve()
    {
        // resolve the instance
        $instance = $this->resolveInstance();
        $this->resetRecursionCount();
        return $instance;
    }

    /**
     * Creates the class instance.
     *
     * @return mixed
     */
    protected function resolveInstance()
    {
        $this->increaseRecursionCount();
        $reflectionClass = new ReflectionClass($this->className);
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
     * Resolves an object parameters.
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
            $arguments[] = $this->container->get(
                $reflectionParameter->getClass()->getName()
            );
        }
        return $arguments;
    }

    /**
     * Increases recursion count.
     *
     * @throws \Acfatah\Container\Exception\ContainerException
     */
    protected function increaseRecursionCount()
    {
        // increment
        self::$recursionCount[$this->className] =
            isset(self::$recursionCount[$this->className])
                ? self::$recursionCount[$this->className] + 1
                : 1;
        if (self::$recursionCount[$this->className] > $this->maxRecursion) {
            // throw exception if exceeds maximum count
            $msg = 'Class "%s" exceeds maximum recursion count of %s times!';
            throw new ContainerException(sprintf(
                $msg,
                $this->className,
                $this->maxRecursion
            ));
        }

    }

    /**
     * Resets recursion count.
     */
    protected function resetRecursionCount()
    {
        self::$recursionCount = null;
    }
}