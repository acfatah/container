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

use Interop\Container\ContainerInterface;
use Acfatah\Container\Resolver\AbstractResolver;
use Acfatah\Container\Exception\ContainerException;
use Acfatah\Container\Exception\UnexpectedValueException;

/**
 * This class encapsulates a callable resolver.
 *
 * @author Achmad F. Ibrahim <acfatah@gmail.com>
 */
class CallableResolver extends AbstractResolver
{
    /**
     * @var Interop\Container\ContainerInterface The container instance.
     */
    protected $container;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var callable
     */
    protected $callback;

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
     * @param \Acfatah\Container\Resolver\ContainerInterface $container
     * @param string $className
     * @param callable $callback
     */
    public function __construct(
        ContainerInterface $container,
        $className,
        $callback,
        $maxRecursion
    ) {
        $this->container = $container;
        $this->className = $className;
        $this->callback = $callback;
        $this->maxRecursion = $maxRecursion;
    }

    /**
     * Resolves the class name to an object instance.
     *
     * @return mixed
     */
    public function resolve()
    {
        $this->increaseRecursionCount();
        // resolve the instance
        $instance = call_user_func($this->callback, $this->container);
        // instance is not an object
        if (!is_object($instance)) {
            $msg = 'Resolver for "%s" returns non object of type "%s"!';
            throw new UnexpectedValueException(sprintf(
                $msg,
                $this->className,
                gettype($instance)
            ));
        }
        $this->resetRecursionCount();
        return $instance;
    }

    /**
     * Increases recursion count.
     *
     * @throws \Acfatah\Container\Exception\ContainerException
     */
    protected function increaseRecursionCount()
    {
        $hash = spl_object_hash($this);
        // increment
        self::$recursionCount[$this->className][$hash] =
            isset(self::$recursionCount[$this->className][$hash])
                ? self::$recursionCount[$this->className][$hash] + 1
                : 1;
        if (self::$recursionCount[$this->className][$hash] > $this->maxRecursion) {
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
        //self::$recursionCount = null;
        unset(self::$recursionCount[$this->className]);
    }
}