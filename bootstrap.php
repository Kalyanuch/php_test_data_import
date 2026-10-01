<?php

declare(strict_types=1);

use Kolya\Test\Config\Env;

$root = __DIR__;

require_once $root . '/vendor/autoload.php';
Env::load($root . '/.env');

return require $root . '/config.php';

