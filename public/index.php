<?php
/**
 * BrickBank – Front-Controller. DocumentRoot zeigt auf dieses public/-Verzeichnis.
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Request;
use App\Core\Router;

$router = new Router();
require BASE_PATH . '/app/routes.php';

$router->dispatch(new Request());
