<?php
/**
 * Routendefinitionen. $router ist im Front-Controller verfügbar.
 *
 * @var \App\Core\Router $router
 */

use App\Controller\HomeController;

$router->get('/', [HomeController::class, 'index']);

// Weitere Module (Behälter, Etiketten, Inventur) werden hier registriert.
