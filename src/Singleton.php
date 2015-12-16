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

/**
 * This class wraps a definition and returns a singleton instance when invoked.
 *
 * @author Achmad F. Ibrahim <acfatah@gmail.com>
 */
final class Singleton
{
    /**
     * @var mixed
     */
    protected $definition;

    /**
     * @var string
     */
    protected $uid;

    /**
     * @var mixed
     */
    protected static $singleton;

    /**
     * Constructor.
     *
     * @param mixed $definition
     */
    public function __construct($definition)
    {
        $this->definition = $definition;
        $this->uid = md5(uniqid('', true));
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        unset(self::$singleton[$this->uid]);
    }

    /**
     * Invokes the closure when called as a function and return a singleton
     * instance.
     *
     * @return mixed
     */
    public function __invoke()
    {
        if (!isset(self::$singleton[$this->uid])) {
            if (is_callable($this->definition)) {
                self::$singleton[$this->uid] = call_user_func_array($this->definition, func_get_args());
            } else {
                self::$singleton[$this->uid] = $this->definition;
            }
        }

        return self::$singleton[$this->uid];
    }
}
