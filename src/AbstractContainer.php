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

namespace Acfatah\Container;

use ArrayAccess;
use Interop\Container\ContainerInterface;

/**
 * Describes an inversion of control container.
 *
 * @author Achmad F. Ibrahim <acfatah@gmail.com>
 */
abstract class AbstractContainer implements ArrayAccess, ContainerInterface
{
    /**
    * @link https://github.com/container-interop/container-interop/blob/master/src/Interop/Container/ContainerInterface.php
    */
    abstract public function has($class);

    /**
    * @link https://github.com/container-interop/container-interop/blob/master/src/Interop/Container/ContainerInterface.php
    */
    abstract public function get($class);

    /**
     * Sets a class resolver.
     */
    abstract public function set($class, $resolver);

    /**
     * Removes a class resolver.
     */
    abstract public function remove($class);

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
}