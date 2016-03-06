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

use Acfatah\Container\Exception\ContainerException;

/**
 * Sets a resolver from a configuration array.
 *
 * The array keys are
 *
 * - **class**    : The class name string
 * - **resolver** : The resolver. Can be an object, a callable, or a class name
 *   string
 * - **single**   : Whether to resolver is a single instance
 * - **new**      : Whether the resolver is a new instance
 *
 * @author Achmad F. Ibrahim <acfatah@gmail.com>
 */
class Config
{
    /**
     * @var string Class name.
     */
    protected $class;

    /**
     * @var mixed Resolver.
     */
    protected $resolver;

    /**
     * @var boolean Single instance.
     */
    protected $single;

    /**
     * @var boolean New instance.
     */
    protected $new;

    /**
     * Constructor.
     *
     * @param array $config
     * @throws \Acfatah\Container\Exception\ContainerException
     */
    public function __construct(array $config)
    {
        // check "class" key
        if (!array_key_exists('class', $config)) {
            // invalid configuration structure, has no class key
            $msg = 'Resolver configuration array has no "class" key!';
            throw new ContainerException($msg);
        }
        $this->class = $config['class'];

        // check "resolver" key
        if (!isset($config['resolver'])) {
            $msg = 'Resolver configuration array has no "resolver" key!';
            throw new ContainerException($msg);
        }
        $this->resolver = $config['resolver'];

        // check "single" key
        $this->single = isset($config['single']) && true === $config['single']
            ? true : false;

        // check "new" key
        $this->new = isset($config['new']) && true === $config['new']
            ? true : false;
    }

    /**
     * Gets the class name.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Gets the resolver.
     *
     * @return mixed
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * Checks whether the resolver is a single instance.
     *
     * @return boolean
     */
    public function isSingle()
    {
        return $this->single;
    }

    /**
     * Checks whether the resolver is a new instance.
     *
     * @return boolean
     */
    public function isNew()
    {
        return $this->new;
    }
}