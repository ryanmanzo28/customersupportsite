<?php

declare(strict_types=1);

use App\Application;
use Cake\Http\Server;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/bootstrap.php';

$server = new Server(new Application(dirname(__DIR__) . '/config'));
$server->emit($server->run());
