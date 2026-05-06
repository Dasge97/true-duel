<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (class_exists(Dotenv::class) && is_file(dirname(__DIR__) . '/.env')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

if (($_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? false)) {
    umask(0000);
}
