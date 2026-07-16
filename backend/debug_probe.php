<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/bootstrap.php';

echo 'debug=' . var_export(Cake\Core\Configure::read('debug'), true) . PHP_EOL;
echo 'error=' . var_export(Cake\Core\Configure::read('Error'), true) . PHP_EOL;
echo 'app.encoding=' . var_export(Cake\Core\Configure::read('App.encoding'), true) . PHP_EOL;
echo 'db_host=' . var_export(getenv('DB_HOST'), true) . PHP_EOL;
