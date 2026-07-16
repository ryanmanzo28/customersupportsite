<?php

declare(strict_types=1);

use Cake\Core\Configure;
use Cake\Cache\Cache;
use Cake\Datasource\ConnectionManager;
use Cake\Error\ErrorTrap;
use Cake\Routing\Router;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
if (!defined('ROOT')) {
    define('ROOT', $root);
}
if (!defined('APP_DIR')) {
    define('APP_DIR', 'src');
}
if (!defined('APP')) {
    define('APP', ROOT . DIRECTORY_SEPARATOR . APP_DIR . DIRECTORY_SEPARATOR);
}
if (!defined('WWW_ROOT')) {
    define('WWW_ROOT', ROOT . DIRECTORY_SEPARATOR . 'webroot' . DIRECTORY_SEPARATOR);
}
if (!defined('CONFIG')) {
    define('CONFIG', ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR);
}
if (!defined('CORE_PATH')) {
    define('CORE_PATH', ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'cakephp' . DIRECTORY_SEPARATOR . 'cakephp' . DIRECTORY_SEPARATOR);
}
if (!defined('CAKE')) {
    define('CAKE', CORE_PATH . 'src' . DIRECTORY_SEPARATOR);
}

$envPath = dirname(__DIR__) . '/.env';
if (is_file($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        [$name, $value] = array_pad(explode('=', $line, 2), 2, null);
        $name = trim((string)$name);
        $value = trim((string)$value);
        if ($name === '') {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if (getenv($name) === false) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);

        return $value === false ? $default : $value;
    }
}

$debug = filter_var(env('DEBUG', 'true'), FILTER_VALIDATE_BOOL);
Configure::write('debug', $debug);
Configure::write('App.namespace', 'App');
Configure::write('App.defaultCharset', 'UTF-8');
Configure::write('App.encoding', 'UTF-8');
Configure::write('Error', ['debug' => $debug]);
Router::reload();

$tmpPath = (string)env('APP_TMP', sys_get_temp_dir() . '/support');
if (!str_ends_with($tmpPath, '/')) {
    $tmpPath .= '/';
}
if (!defined('TMP')) {
    define('TMP', $tmpPath);
}

$cachePath = rtrim($tmpPath, '/') . '/cache';
$coreCachePath = $cachePath . '/persistent';
$modelCachePath = $cachePath . '/models';
foreach ([$tmpPath, $cachePath, $coreCachePath, $modelCachePath] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

Cache::setConfig('_cake_translations_', [
    'className' => 'File',
    'path' => $coreCachePath . DIRECTORY_SEPARATOR,
    'prefix' => 'support_core_',
    'serialize' => true,
    'duration' => '+1 year',
]);

Cache::setConfig('_cake_model_', [
    'className' => 'File',
    'path' => $modelCachePath . DIRECTORY_SEPARATOR,
    'prefix' => 'support_model_',
    'serialize' => true,
    'duration' => '+1 year',
]);

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

(new ErrorTrap())->register();

date_default_timezone_set('UTC');
