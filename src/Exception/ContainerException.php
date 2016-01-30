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

namespace Acfatah\Container\Exception;

use RuntimeException;
use Interop\Container\Exception\ContainerException as ContainerExceptionInterface;

/**
 * @link https://github.com/container-interop/container-interop/blob/master/src/Interop/Container/Exception/ContainerException.php \Interop\Container\Exception\ContainerException
 */
class ContainerException extends RuntimeException implements ContainerExceptionInterface
{

}