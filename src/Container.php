<?php

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE file.
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright (c) 2015, Achmad F. Ibrahim
 * @link https://github.com/acfatah/container
 * @license http://opensource.org/licenses/mit-license.php The MIT License (MIT)
 */

namespace Acfatah\Container;

use ArrayObject;
use Closure;
use Serializable as SerializableInterface;
use InvalidArgumentException;
use Interop\Container\ContainerInterface;
use Acfatah\Container\NewInstance;
use Acfatah\Container\NotFoundException;
use Acfatah\Container\SerializableClosure;

/**
 * Dependency injection container class.
 *
 * This class is used to store definitions on how to create objects and their
 * dependencies using closure.
 *
 * Definition can be any php type. The definition will be invoked if it is a
 * callable and the container will pass its referrence to the definition as
 * an argument.
 */
class Container extends ArrayObject implements ContainerInterface, SerializableInterface
{
    /**
     * Constructor.
     *
     * @param array $definitions An array of definitions.
     */
    public function __construct($definitions = [])
    {
        parent::__construct($definitions);

        foreach ($this as $key => $value) {
            if ($value instanceof NewInstance) {
                $this[$key] = $value($this);
            }
        }
    }

    /**
     * Magic clone method.
     */
    public function __clone()
    {
        $iterator = $this->getIterator();
        while ($iterator->valid()) {
            if (is_object($iterator->current())) {
                $clone[$iterator->key()] = clone $iterator->current();
                $iterator->next();
                continue;
            }
            $clone[$iterator->key()] = $iterator->current();
            $iterator->next();
        }
        $this->exchangeArray($clone);
    }

    /**
     * If the value supplied is callable, the value will be invoked or called.
     *
     * This class instance will be passed as argument to the callback.
     *
     * > Note: Since Container extends from object, accessing it as an array
     * > invokes this method.
     *
     * @param string $identifier
     * @param boolean $invoke Whether to invoke the definition if it is callable
     * @return mixed
     * @throws \Acfatah\Container\NotFoundException
     */
    public function get($identifier, $invoke = true)
    {
        if (!$this->offsetExists($identifier)) {
            throw new NotFoundException(sprintf('Identifier "%s" is not defined!', $identifier));
        }

        $definition = parent::offsetGet($identifier);

        if ($invoke && is_callable($definition)) {
            return call_user_func($definition, $this);
        }

        return $definition;
    }

    /**
     * Gets all the defined identifiers.
     *
     * @return array
     */
    public function getIndentifiers()
    {
        return array_keys($this->getArrayCopy());
    }

    /**
     * {@inheritdoc}
     */
    public function has($identifier)
    {
        return $this->offsetExists($identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($index)
    {
        return $this->get($index);
    }

    /**
     * Merges current values with other ArrayObject(s) or array(s).
     *
     * @return \Acfatah\Container\Container
     * @throws \InvalidArgumentException
     */
    public function merge()
    {
        $arguments = func_get_args();
        $array = $this->getArrayCopy();
        for ($n = 0, $args = func_num_args(); $n < $args; $n++) {
            if ($arguments[$n] instanceof ArrayObject) {
                $arrayN = $arguments[$n]->getArrayCopy();
            } elseif (is_array($arguments[$n])) {
                $arrayN = $arguments[$n];
            } else {
                throw new InvalidArgumentException(
                    'Argument(s) supplied is not an instance of an ArrayObject or array!'
                );
            }
            $array = array_merge($array, $arrayN);
        }
        $this->exchangeArray($array);

        return $this;
    }

    /**
     * Removes a definition.
     *
     * @param string $identifier
     * @return \Acfatah\Container\Container
     */
    public function remove($identifier)
    {
        $this->offsetUnset($identifier);

        return $this;
    }

    /**
     * Serializes the container instance.
     */
    public function serialize()
    {
        $data = $this->getArrayCopy();
        foreach ($data as $key => $value) {
            if ($value instanceof Closure) {
                $data[$key] = new SerializableClosure($value);
            }
        }

        return serialize($data);
    }

    /**
     * Sets a definition to an identifier.
     *
     * @param string $identifier
     * @param mixed $definition
     * @param boolean $invoke Whether to eager load the definition
     * @return \Acfatah\Container\Container
     */
    public function set($identifier, $definition, $invoke = false)
    {
        if ($invoke) {
            $definition = $definition($this);
        }
        $this->offsetSet($identifier, $definition);

        return $this;
    }

    /**
     * Unserializes the container instance.
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        foreach ($data as $key => $value) {
            if ($value instanceof SerializableClosure) {
                $data[$key] = $value->getClosure();
            }
        }

        $this->exchangeArray($data);
    }
}
