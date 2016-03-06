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
     * Constructor.
     *
     * @param \Acfatah\Container\Resolver\ContainerInterface $container
     * @param string $className
     * @param callable $callback
     */
    public function __construct(
        ContainerInterface $container,
        $className,
        $callback
    ) {
        $this->container = $container;
        $this->className = $className;
        $this->callback = $callback;
    }

    /**
     * Resolves the class name to an object instance.
     *
     * @return mixed
     */
    public function resolve()
    {
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
        return $instance;
    }
}