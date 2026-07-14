<?php

declare(strict_types=1);

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Error\ErrorTrap;

require dirname(__DIR__) . '/vendor/autoload.php';

$debug = filter_var(env('DEBUG', 'true'), FILTER_VALIDATE_BOOL);
Configure::write('debug', $debug);

ConnectionManager::setConfig('default', [
    'className' => 'Cake\\Database\\Connection',
    'driver' => 'Cake\\Database\\Driver\\Mysql',
    'host' => env('DB_HOST', 'db'),
    'port' => (int)env('DB_PORT', '3306'),
    'username' => env('DB_USERNAME', 'app'),
    'password' => env('DB_PASSWORD', 'app'),
    'database' => env('DB_DATABASE', 'support'),
    'encoding' => 'utf8mb4',
    'timezone' => 'UTC',
    'cacheMetadata' => true,
]);

(new ErrorTrap($debug))->register();

date_default_timezone_set('UTC');
