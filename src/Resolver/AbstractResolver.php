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

/**
 * Base class for a resolver class.
 *
 * @author Achmad F. Ibrahim <acfatah@gmail.com>
 */
abstract class AbstractResolver
{
    /**
     * Resolves an object dependencies.
     */
    abstract public function resolve();
}