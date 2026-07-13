<?php
namespace App\Controller;

use App\Core\Controller;

/** Grafische Anleitung / Hilfe für BrickBank (ohne Login lesbar). */
class HelpController extends Controller
{
    public function index(): void
    {
        $this->render('help/index', [
            'title' => 'Anleitung',
            'nav'   => 'help',
        ]);
    }
}
