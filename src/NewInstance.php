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

use Closure;
use Interop\Container\ContainerInterface;

/**
 * A wrapper class use to resolve a definition and store the instantiated object
 * in container.
 *
 * @author Achmad F. Ibrahim <acfatah@gmail.com>
 */
class NewInstance
{
    /**
     * @var \Closure
     */
    protected $closure;

    /**
     * Constructor.
     *
     * @param \Closure $closure
     */
    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
    }

    /**
     * Resolve the definition and returns a new instance of an object.
     *
     * @return object
     */
    public function __invoke(ContainerInterface $container)
    {
        return $this->closure->__invoke($container);
    }
}
