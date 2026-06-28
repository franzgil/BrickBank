<?php
/**
 * Routendefinitionen. $router ist im Front-Controller verfügbar.
 *
 * @var \App\Core\Router $router
 */

use App\Controller\HomeController;
use App\Controller\ContainerController;
use App\Controller\MoveController;
use App\Controller\LabelController;
use App\Controller\StocktakeController;
use App\Controller\InventoryController;

$router->get('/', [HomeController::class, 'index']);
$router->get('/ping', [HomeController::class, 'ping']);   // Diagnose ohne DB
$router->get('/auth-debug', [HomeController::class, 'authDebug']);   // SSO-Diagnose (danach entfernen)

// --- Behälter (Container/Karton/Tüte) ---
$router->get('/container', [ContainerController::class, 'index']);
$router->get('/container/new', [ContainerController::class, 'create']);
$router->post('/container', [ContainerController::class, 'store']);
$router->get('/container/{id}', [ContainerController::class, 'show']);
$router->get('/container/{id}/history', [MoveController::class, 'history']);
$router->get('/container/{id}/add', [InventoryController::class, 'add']);
$router->post('/container/{id}/inventory', [InventoryController::class, 'store']);

// --- Umräumen ---
$router->get('/move', [MoveController::class, 'form']);
$router->post('/move', [MoveController::class, 'perform']);

// --- Etiketten ---
$router->get('/label', [LabelController::class, 'index']);
$router->get('/label/print', [LabelController::class, 'printSheet']);
$router->get('/label/export', [LabelController::class, 'export']);
$router->get('/label/qr', [LabelController::class, 'qr']);
$router->get('/label/{id}', [LabelController::class, 'preview']);

// --- Inventur ---
$router->get('/stocktake', [StocktakeController::class, 'index']);
$router->get('/stocktake/new', [StocktakeController::class, 'create']);
$router->post('/stocktake', [StocktakeController::class, 'store']);
$router->get('/stocktake/{id}', [StocktakeController::class, 'show']);
$router->get('/stocktake/{id}/scan', [StocktakeController::class, 'scanForm']);
$router->post('/stocktake/{id}/scan', [StocktakeController::class, 'scan']);
$router->post('/stocktake/{id}/finish', [StocktakeController::class, 'finish']);
