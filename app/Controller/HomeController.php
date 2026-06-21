<?php
namespace App\Controller;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->render('home/index', ['title' => 'BrickBank – Start']);
    }

    /** Diagnose ohne Datenbank: prüft Routing/Pfade im Deployment. */
    public function ping(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "pong\n";
        echo 'path        = ' . $this->request->path() . "\n";
        echo 'script_name = ' . ($_SERVER['SCRIPT_NAME'] ?? '') . "\n";
        echo 'path_info   = ' . ($_SERVER['PATH_INFO'] ?? '') . "\n";
        echo 'request_uri = ' . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
        echo 'asset_base  = ' . app_base_dir() . "\n";
        echo 'route(home) = ' . base_url('/') . "\n";
        echo 'route(cont) = ' . base_url('container') . "\n";
        echo 'asset(css)  = ' . asset_url('assets/app.css') . "\n";
    }
}
