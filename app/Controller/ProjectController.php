<?php
namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\Repository\ProjectRepository;

/**
 * Projekte anlegen und auflisten. Standorte/Behälter eines Projekts werden
 * im Lagerbaum des Projekts verwaltet (/account/{id}), da ein Projekt ein
 * Besitzer (bb_owner Typ 'projekt') ist.
 */
class ProjectController extends Controller
{
    /** @var ProjectRepository */
    private $projects;

    public function __construct()
    {
        parent::__construct();
        $this->projects = new ProjectRepository();
    }

    /** Übersicht aller Projekte + Anlege-Formular. */
    public function index(): void
    {
        $user = $this->requireLogin();
        $this->render('project/index', [
            'title'    => 'Projekte',
            'nav'      => 'project',
            'projects' => $this->projects->all(),
            'meId'     => $user->userId,
            'csrf'     => Csrf::token(),
        ]);
    }

    /** Neues Projekt anlegen und direkt in seinen Lagerbaum wechseln. */
    public function store(): void
    {
        $user = $this->requireLogin();
        Csrf::validate($this->request->post('csrf_token'));

        $name = trim((string) $this->request->post('name', ''));
        $note = trim((string) $this->request->post('note', ''));
        $note = $note === '' ? null : $note;

        if ($name === '') {
            $this->flash('error', 'Projektname darf nicht leer sein.');
            $this->redirect(base_url('projects'));
        }

        $id = $this->projects->create($name, $note, $user->userId);
        $this->flash('success', 'Projekt „' . $name . '" angelegt. Lege nun Standorte/Behälter an.');
        $this->redirect(base_url('account/' . $id));
    }
}
