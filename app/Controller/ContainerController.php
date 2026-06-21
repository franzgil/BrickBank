<?php
namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
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

    public function __construct()
    {
        parent::__construct();
        $this->labels    = new LabelRepository();
        $this->locations = new LocationRepository();
        $this->owners    = new OwnerRepository();
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
        if (!$errors && $parentId !== null) {
            $parent = $this->locations->find($parentId);
            if ($parent === null) {
                $errors[] = 'Gewählter Standort wurde nicht gefunden.';
            } elseif (!ContainerRules::parentAllowed($kind, $parent['kind'])) {
                $errors[] = ContainerRules::ruleMessage($kind);
            }
        }

        if ($errors) {
            $this->render('container/form', $this->formData([
                'errors' => $errors,
                'old'    => compact('kind', 'name', 'parentId', 'ownerId', 'note'),
            ]));
            return;
        }

        $created = $this->labels->createContainer($kind, $name, $parentId, $ownerId, $note);
        $this->flash('success', ContainerRules::label($kind) . ' angelegt: ' . $created['code']);
        $this->redirect(base_url('container/' . $created['id']));
    }

    /** Detailansicht eines Behälters inkl. Inhalt. */
    public function show($id): void
    {
        $container = $this->labels->find((int) $id);
        if ($container === null) {
            http_response_code(404);
            echo 'Behälter nicht gefunden';
            return;
        }
        $this->render('container/detail', [
            'title'     => $container['code'],
            'nav'       => 'container',
            'container' => $container,
            'contents'  => $this->labels->contents((int) $id),
        ]);
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
