<?php
/**
 * Routendefinitionen. $router ist im Front-Controller verfügbar.
 *
 * @var \App\Core\Router $router
 */

use App\Controller\HomeController;
use App\Controller\ContainerController;
use App\Controller\MoveController;

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

// Weitere Module (Etiketten, Inventur) werden hier registriert.
