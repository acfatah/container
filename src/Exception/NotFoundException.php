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

use Acfatah\Container\Exception\ContainerException;
use Interop\Container\Exception\NotFoundException as NotFoundExceptionInterface;

/**
 * @link https://github.com/container-interop/container-interop/blob/master/src/Interop/Container/Exception/NotFoundException.php \Interop\Container\Exception\NotFoundException
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{

}
