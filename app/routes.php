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

$router->get('/', [HomeController::class, 'index']);

// --- Behälter (Container/Karton/Tüte) ---
$router->get('/container', [ContainerController::class, 'index']);
$router->get('/container/new', [ContainerController::class, 'create']);
$router->post('/container', [ContainerController::class, 'store']);
$router->get('/container/{id}', [ContainerController::class, 'show']);
$router->get('/container/{id}/history', [MoveController::class, 'history']);

// --- Umräumen ---
$router->get('/move', [MoveController::class, 'form']);
$router->post('/move', [MoveController::class, 'perform']);

// --- Etiketten ---
$router->get('/label', [LabelController::class, 'index']);
$router->get('/label/print', [LabelController::class, 'printSheet']);
$router->get('/label/export', [LabelController::class, 'export']);
$router->get('/label/qr', [LabelController::class, 'qr']);
$router->get('/label/{id}', [LabelController::class, 'preview']);

// Weitere Module (Inventur) werden hier registriert.
