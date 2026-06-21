<?php
/**
 * Routendefinitionen. $router ist im Front-Controller verfügbar.
 *
 * @var \App\Core\Router $router
 */

use App\Controller\HomeController;
use App\Controller\ContainerController;

$router->get('/', [HomeController::class, 'index']);

// --- Behälter (Container/Karton/Tüte) ---
$router->get('/container', [ContainerController::class, 'index']);
$router->get('/container/new', [ContainerController::class, 'create']);
$router->post('/container', [ContainerController::class, 'store']);
$router->get('/container/{id}', [ContainerController::class, 'show']);

// Weitere Module (Etiketten, Inventur) werden hier registriert.
