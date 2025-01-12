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

/**
 * Phpunit bootstrap file
 *
 * @author Achmad F. Ibrahim <acfatah@gmail.com>
 */

$autoload = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoload)) {
    die(<<<MSG
 Please run "composer install" to install dependencies and create autoload file.

MSG
    );
}

$loader = require $autoload;
$loader->addPsr4('Fixture\\', 'tests/Fixture');
