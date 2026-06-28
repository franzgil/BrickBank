<?php
namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Integration\WcfSession;
use App\Model\Repository\HoldingRepository;
use App\Model\Repository\LabelRepository;
use App\Model\Repository\LocationRepository;
use App\Model\Repository\OwnerRepository;
use App\Service\CodeGenerator;
use App\Service\ContainerRules;

class ContainerController extends Controller
{
    /** @var LabelRepository */
    private $labels;
    /** @var LocationRepository */
    private $locations;
    /** @var OwnerRepository */
    private $owners;
    /** @var HoldingRepository */
    private $holdings;

    public function __construct()
    {
        parent::__construct();
        $this->labels    = new LabelRepository();
        $this->locations = new LocationRepository();
        $this->owners    = new OwnerRepository();
        $this->holdings  = new HoldingRepository();
    }

    /** Liste aller Behälter, optional auf einen Typ gefiltert (?kind=). */
    public function index(): void
    {
        $kind = (string) $this->request->get('kind', '');
        $kind = CodeGenerator::isContainerKind($kind) ? $kind : null;

        $this->render('container/list', [
            'title'      => 'Behälter',
            'nav'        => 'container',
            'containers' => $this->labels->listContainers($kind),
            'kind'       => $kind,
        ]);
    }

    /** Anlegen-Formular. */
    public function create(): void
    {
        $this->requireLogin();
        $this->render('container/form', $this->formData());
    }

    /** Anlegen verarbeiten. */
    public function store(): void
    {
        $this->requireWrite();
        Csrf::validate($this->request->post('csrf_token'));

        $kind     = (string) $this->request->post('kind', '');
        $name     = trim((string) $this->request->post('name', ''));
        $parentId = $this->nullableInt($this->request->post('parent_id'));
        $ownerId  = $this->nullableInt($this->request->post('owner_id'));
        $note     = trim((string) $this->request->post('note', ''));
        $note     = $note === '' ? null : $note;

        $errors = [];
        if (!CodeGenerator::isContainerKind($kind)) {
            $errors[] = 'Bitte einen gültigen Behältertyp wählen.';
        }
        if ($name === '') {
            $errors[] = 'Name darf nicht leer sein.';
        }
        if (!$errors && $parentId !== null && $this->locations->find($parentId) === null) {
            $errors[] = 'Gewählter Standort wurde nicht gefunden.';
        }

        if ($errors) {
            $this->render('container/form', $this->formData([
                'errors' => $errors,
                'old'    => compact('kind', 'name', 'parentId', 'ownerId', 'note'),
            ]));
            return;
        }

        // Jeder Ast gehört zu einem Konto; ohne Auswahl dem Verein zuordnen.
        if ($ownerId === null) {
            $verein  = $this->owners->verein();
            $ownerId = $verein ? (int) $verein['id'] : null;
        }

        $created = $this->labels->createContainer($kind, $name, $parentId, $ownerId, $note);
        $this->flash('success', ContainerRules::label($kind) . ' angelegt: ' . $created['code']);
        $this->redirect(base_url('container/' . $created['id']));
    }

    /** Detailansicht eines Behälters inkl. Inhalt. */
    public function show($id): void
    {
        $container = $this->locations->findDetail((int) $id);
        if ($container === null) {
            http_response_code(404);
            echo 'Behälter nicht gefunden';
            return;
        }
        $viewer = WcfSession::user();
        $this->render('container/detail', [
            'title'     => $container['code'] ?? $container['name'],
            'nav'       => 'container',
            'container' => $container,
            'contents'  => $this->holdings->contentsOfLocation((int) $id, $viewer ? $viewer->userId : null),
            'csrf'      => Csrf::token(),
        ]);
    }

    /** Behälter (Ast) löschen – nur wenn leer. */
    public function delete($id): void
    {
        $this->requireLogin();
        Csrf::validate($this->request->post('csrf_token'));
        $container = $this->locations->findDetail((int) $id);
        if ($container === null) {
            http_response_code(404);
            echo 'Behälter nicht gefunden';
            return;
        }
        $res = $this->locations->deleteBranch((int) $id);
        if ($res['ok'] && !empty($res['image_path'])) {
            \App\Service\ImageUpload::deletePublic($res['image_path']);
        }
        $this->flash($res['ok'] ? 'success' : 'error', $res['message']);
        $this->redirect($res['ok'] ? base_url('container') : base_url('container/' . (int) $id));
    }

    /** Bild eines Behälters/Astes hochladen. */
    public function uploadImage($id): void
    {
        $this->requireLogin();
        Csrf::validate($this->request->post('csrf_token'));
        $container = $this->locations->findDetail((int) $id);
        if ($container === null) {
            http_response_code(404);
            echo 'Behälter nicht gefunden';
            return;
        }
        try {
            $rel = \App\Service\ImageUpload::storeLocationImage($_FILES['image'] ?? [], (int) $id);
            \App\Service\ImageUpload::deletePublic($container['image_path'] ?? null);
            $this->locations->setImagePath((int) $id, $rel);
            $this->flash('success', 'Bild hochgeladen.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
        $this->redirect(base_url('container/' . (int) $id));
    }

    /** Bild eines Behälters/Astes entfernen. */
    public function removeImage($id): void
    {
        $this->requireLogin();
        Csrf::validate($this->request->post('csrf_token'));
        $container = $this->locations->findDetail((int) $id);
        if ($container !== null && !empty($container['image_path'])) {
            \App\Service\ImageUpload::deletePublic($container['image_path']);
            $this->locations->setImagePath((int) $id, null);
        }
        $this->flash('info', 'Bild entfernt.');
        $this->redirect(base_url('container/' . (int) $id));
    }

    /** Eigene (parallele) ID eines Behälters setzen/ändern. */
    public function setCustomCode($id): void
    {
        $this->requireLogin();
        Csrf::validate($this->request->post('csrf_token'));
        $custom = (string) $this->request->post('custom_code', '');
        $ok = $this->labels->setCustomCode((int) $id, $custom);
        $this->flash($ok ? 'success' : 'error',
            $ok ? 'Eigene ID gespeichert.' : 'Diese eigene ID ist bereits vergeben.');
        $this->redirect(base_url('container/' . (int) $id));
    }

    /** Gemeinsame Daten für das Formular (inkl. evtl. Fehler/Alteingaben). */
    private function formData(array $extra = []): array
    {
        return array_merge([
            'title'         => 'Behälter anlegen',
            'nav'           => 'container',
            'owners'        => $this->owners->all(),
            // alle möglichen Zielorte (Lagerorte + Container + Kartons)
            'parentOptions' => $this->locations->byKinds(
                ['raum', 'schrank', 'schublade', 'box', 'fach', 'sonstiges', 'container', 'karton']
            ),
            'csrf'          => Csrf::token(),
            'errors'        => [],
            'old'           => [],
        ], $extra);
    }
}
