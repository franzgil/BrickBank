<?php
/**
 * Routendefinitionen. $router ist im Front-Controller verfügbar.
 *
 * @var \App\Core\Router $router
 */

use App\Controller\HomeController;
use App\Controller\DashboardController;
use App\Controller\LocationController;
use App\Controller\ContainerController;
use App\Controller\MoveController;
use App\Controller\LabelController;
use App\Controller\StocktakeController;
use App\Controller\InventoryController;
use App\Controller\StockController;
use App\Controller\ProjectController;

$router->get('/', [HomeController::class, 'index']);
$router->get('/ping', [HomeController::class, 'ping']);   // Diagnose ohne DB

// --- Dashboard / Konten ---
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/account/{id}', [DashboardController::class, 'account']);
$router->post('/account/{id}/branch', [LocationController::class, 'addBranch']);
$router->post('/account/{id}/branch/move', [LocationController::class, 'moveBranch']);
$router->post('/account/{id}/branch/delete', [LocationController::class, 'deleteBranch']);

// --- Projekte (eigener Lagerbaum je Projekt) ---
$router->get('/projects', [ProjectController::class, 'index']);
$router->post('/projects', [ProjectController::class, 'store']);

// --- Behälter (Container/Karton/Tüte) ---
$router->get('/container', [ContainerController::class, 'index']);
$router->get('/container/new', [ContainerController::class, 'create']);
$router->post('/container', [ContainerController::class, 'store']);
$router->get('/container/{id}', [ContainerController::class, 'show']);
$router->post('/container/{id}/delete', [ContainerController::class, 'delete']);
$router->get('/container/{id}/history', [MoveController::class, 'history']);
$router->post('/container/{id}/custom-code', [ContainerController::class, 'setCustomCode']);
$router->post('/container/{id}/image', [ContainerController::class, 'uploadImage']);
$router->post('/container/{id}/image/remove', [ContainerController::class, 'removeImage']);
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

// --- Bestand (Items, Übersicht, Entnehmen/Umbuchen) ---
$router->get('/stock', [StockController::class, 'index']);
$router->get('/item/{id}', [StockController::class, 'item']);
$router->post('/item/{id}/adjust', [StockController::class, 'adjust']);
$router->post('/item/{id}/move', [StockController::class, 'move']);

// --- Inventur ---
$router->get('/stocktake', [StocktakeController::class, 'index']);
$router->get('/stocktake/new', [StocktakeController::class, 'create']);
$router->post('/stocktake', [StocktakeController::class, 'store']);
$router->get('/stocktake/{id}', [StocktakeController::class, 'show']);
$router->get('/stocktake/{id}/scan', [StocktakeController::class, 'scanForm']);
$router->post('/stocktake/{id}/scan', [StocktakeController::class, 'scan']);
$router->post('/stocktake/{id}/finish', [StocktakeController::class, 'finish']);
