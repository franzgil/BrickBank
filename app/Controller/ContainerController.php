<?php
namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Integration\WcfSession;
use App\Model\Repository\CatalogRepository;
use App\Model\Repository\HoldingRepository;
use App\Model\Repository\ItemRepository;
use App\Model\Repository\LabelRepository;
use App\Model\Repository\LocationRepository;
use App\Model\Repository\OwnerRepository;
use App\Service\CodeGenerator;
use App\Service\ContainerRules;
use App\Service\StockService;

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
    /** @var StockService */
    private $stock;
    /** @var ItemRepository */
    private $items;
    /** @var CatalogRepository */
    private $catalog;

    public function __construct()
    {
        parent::__construct();
        $this->labels    = new LabelRepository();
        $this->locations = new LocationRepository();
        $this->owners    = new OwnerRepository();
        $this->holdings  = new HoldingRepository();
        $this->stock     = new StockService();
        $this->items     = new ItemRepository();
        $this->catalog   = new CatalogRepository();
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
            'title'       => $container['code'] ?? $container['name'],
            'nav'         => 'container',
            'container'   => $container,
            'children'    => $this->locations->childrenOf((int) $id),
            'contents'    => $this->holdings->contentsOfLocation((int) $id, $viewer ? $viewer->userId : null),
            'moveTargets' => $this->locations->byKinds(
                ['raum', 'schrank', 'schublade', 'box', 'fach', 'sonstiges',
                 'container', 'karton', 'tuete', 'sortimentsbox', 'einsatzkasten']
            ),
            'colors'      => $this->catalog->colors(),
            'meId'        => $viewer ? $viewer->userId : null,
            'canWrite'    => $viewer ? $viewer->canWrite() : false,
            'csrf'        => Csrf::token(),
        ]);
    }

    /** Menge einer Bestandsposition im Behälter korrigieren (add|remove). */
    public function adjustStock($id): void
    {
        $user = $this->requireLogin();
        Csrf::validate($this->request->post('csrf_token'));
        $locationId = (int) $id;
        $itemId     = (int) $this->request->post('item_id', 0);
        $ownerId    = (int) $this->request->post('owner_id', 0);
        $cond       = (string) $this->request->post('cond', 'gebraucht');
        $qty        = (int) $this->request->post('quantity', 0);
        $action     = (string) $this->request->post('action', '');   // add|remove

        if (!$this->mayEditOwner($ownerId, $user)) {
            $this->flash('error', 'Keine Berechtigung für diese Position.');
            $this->redirect(base_url('container/' . $locationId));
        }
        try {
            if ($action === 'add') {
                $existing   = $this->holdings->findExact($itemId, $locationId, $ownerId, $cond);
                $visibility = $existing ? $existing['visibility'] : 'privat';
                $this->stock->add($itemId, $locationId, $ownerId, $cond, $visibility, $qty, $user->userId, null);
                $this->flash('success', $qty . '× hinzugefügt.');
            } else {
                $this->stock->remove($itemId, $locationId, $ownerId, $cond, $qty, $user->userId, null);
                $this->flash('success', $qty . '× entnommen.');
            }
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
        $this->redirect(base_url('container/' . $locationId));
    }

    /** Bestandsposition aus diesem Behälter an einen anderen Ort umbuchen. */
    public function moveStock($id): void
    {
        $user = $this->requireLogin();
        Csrf::validate($this->request->post('csrf_token'));
        $fromLocationId = (int) $id;
        $toLocationId   = (int) $this->request->post('to_location_id', 0);
        $itemId         = (int) $this->request->post('item_id', 0);
        $ownerId        = (int) $this->request->post('owner_id', 0);
        $cond           = (string) $this->request->post('cond', 'gebraucht');
        $qty            = (int) $this->request->post('quantity', 0);

        if (!$this->mayEditOwner($ownerId, $user)) {
            $this->flash('error', 'Keine Berechtigung für diese Position.');
            $this->redirect(base_url('container/' . $fromLocationId));
        }
        if ($toLocationId <= 0) {
            $this->flash('error', 'Bitte einen Zielort wählen.');
            $this->redirect(base_url('container/' . $fromLocationId));
        }
        try {
            $this->stock->move($itemId, $fromLocationId, $toLocationId, $ownerId, $cond, $qty, $user->userId, null);
            $this->flash('success', $qty . '× umgebucht.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
        $this->redirect(base_url('container/' . $fromLocationId));
    }

    /** Sichtbarkeit einer Bestandsposition im Behälter ändern. */
    public function setStockVisibility($id): void
    {
        $user = $this->requireLogin();
        Csrf::validate($this->request->post('csrf_token'));
        $locationId = (int) $id;
        $itemId     = (int) $this->request->post('item_id', 0);
        $ownerId    = (int) $this->request->post('owner_id', 0);
        $cond       = (string) $this->request->post('cond', 'gebraucht');
        $visibility = (string) $this->request->post('visibility', '');

        if (!in_array($visibility, ['privat', 'intern', 'verein'], true)) {
            $this->flash('error', 'Ungültige Sichtbarkeit.');
            $this->redirect(base_url('container/' . $locationId));
        }
        $owner = $this->owners->find($ownerId);
        if ($owner === null || !$this->mayEditOwner($ownerId, $user)) {
            $this->flash('error', 'Keine Berechtigung für diese Position.');
            $this->redirect(base_url('container/' . $locationId));
        }
        // Vereinsbestand ist mindestens intern sichtbar.
        if ($owner['type'] === 'verein' && $visibility === 'privat') {
            $visibility = 'intern';
        }
        $holding = $this->holdings->findExact($itemId, $locationId, $ownerId, $cond);
        if ($holding !== null) {
            $this->holdings->setVisibility((int) $holding['id'], $visibility);
            $this->flash('success', 'Sichtbarkeit geändert.');
        }
        $this->redirect(base_url('container/' . $locationId));
    }

    /** Menge einer Bestandsposition direkt auf einen absoluten Wert setzen. */
    public function setStockQuantity($id): void
    {
        $user = $this->requireLogin();
        Csrf::validate($this->request->post('csrf_token'));
        $locationId = (int) $id;
        $itemId     = (int) $this->request->post('item_id', 0);
        $ownerId    = (int) $this->request->post('owner_id', 0);
        $cond       = (string) $this->request->post('cond', 'gebraucht');
        $target     = (int) $this->request->post('quantity', -1);
        $back       = base_url('container/' . $locationId);

        if (!$this->mayEditOwner($ownerId, $user)) {
            $this->flash('error', 'Keine Berechtigung für diese Position.');
            $this->redirect($back);
        }
        if ($target < 0) {
            $this->flash('error', 'Menge darf nicht negativ sein.');
            $this->redirect($back);
        }
        $existing = $this->holdings->findExact($itemId, $locationId, $ownerId, $cond);
        $current  = $existing ? (int) $existing['quantity'] : 0;
        $delta    = $target - $current;
        try {
            if ($delta > 0) {
                $visibility = $existing ? $existing['visibility'] : 'privat';
                $this->stock->add($itemId, $locationId, $ownerId, $cond, $visibility, $delta, $user->userId, 'Menge gesetzt');
            } elseif ($delta < 0) {
                $this->stock->remove($itemId, $locationId, $ownerId, $cond, -$delta, $user->userId, 'Menge gesetzt');
            }
            $this->flash('success', 'Menge auf ' . $target . ' gesetzt.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
        $this->redirect($back);
    }

    /**
     * Menge einer Position per Waage zählen: aus Einzel- und Gesamtgewicht
     * die Stückzahl berechnen (qty = gesamt / einzel) und absolut setzen.
     * Das Einzelgewicht wird am Item gespeichert.
     */
    public function setStockByWeight($id): void
    {
        $user = $this->requireLogin();
        Csrf::validate($this->request->post('csrf_token'));
        $locationId = (int) $id;
        $itemId     = (int) $this->request->post('item_id', 0);
        $ownerId    = (int) $this->request->post('owner_id', 0);
        $cond       = (string) $this->request->post('cond', 'gebraucht');
        $unit       = (float) str_replace(',', '.', (string) $this->request->post('unit_weight', ''));
        $total      = (float) str_replace(',', '.', (string) $this->request->post('total_weight', ''));
        $back       = base_url('container/' . $locationId);

        if (!$this->mayEditOwner($ownerId, $user)) {
            $this->flash('error', 'Keine Berechtigung für diese Position.');
            $this->redirect($back);
        }
        if ($unit <= 0) {
            $this->flash('error', 'Einzelgewicht muss größer als 0 sein.');
            $this->redirect($back);
        }
        if ($total < 0) {
            $this->flash('error', 'Gesamtgewicht darf nicht negativ sein.');
            $this->redirect($back);
        }

        // Einzelgewicht am Item merken (für die nächste Zählung).
        $this->items->setUnitWeight($itemId, $unit);

        $targetQty = (int) round($total / $unit);
        $existing  = $this->holdings->findExact($itemId, $locationId, $ownerId, $cond);
        $current   = $existing ? (int) $existing['quantity'] : 0;
        $delta     = $targetQty - $current;
        try {
            if ($delta > 0) {
                $visibility = $existing ? $existing['visibility'] : 'privat';
                $this->stock->add($itemId, $locationId, $ownerId, $cond, $visibility, $delta, $user->userId, 'Zählung per Gewicht');
            } elseif ($delta < 0) {
                $this->stock->remove($itemId, $locationId, $ownerId, $cond, -$delta, $user->userId, 'Zählung per Gewicht');
            }
            $this->flash('success', 'Per Gewicht gezählt: ' . $targetQty . ' Stück (' . $total . ' g ÷ ' . $unit . ' g).');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
        $this->redirect($back);
    }

    /** Eine Bestandsposition vollständig aus dem Behälter entfernen. */
    public function deleteStock($id): void
    {
        $user = $this->requireLogin();
        Csrf::validate($this->request->post('csrf_token'));
        $locationId = (int) $id;
        $itemId     = (int) $this->request->post('item_id', 0);
        $ownerId    = (int) $this->request->post('owner_id', 0);
        $cond       = (string) $this->request->post('cond', 'gebraucht');
        $back       = base_url('container/' . $locationId);

        if (!$this->mayEditOwner($ownerId, $user)) {
            $this->flash('error', 'Keine Berechtigung für diese Position.');
            $this->redirect($back);
        }
        $existing = $this->holdings->findExact($itemId, $locationId, $ownerId, $cond);
        if ($existing !== null && (int) $existing['quantity'] > 0) {
            try {
                $this->stock->remove($itemId, $locationId, $ownerId, $cond, (int) $existing['quantity'], $user->userId, 'Position gelöscht');
            } catch (\Throwable $e) {
                $this->flash('error', $e->getMessage());
                $this->redirect($back);
            }
        }
        $this->flash('success', 'Position entfernt.');
        $this->redirect($back);
    }

    /** Farbe und/oder Zustand einer Bestandsposition im Behälter ändern. */
    public function reclassifyStock($id): void
    {
        $user = $this->requireLogin();
        Csrf::validate($this->request->post('csrf_token'));
        $locationId = (int) $id;
        $itemId     = (int) $this->request->post('item_id', 0);
        $ownerId    = (int) $this->request->post('owner_id', 0);
        $cond       = (string) $this->request->post('cond', 'gebraucht');
        $colorRaw   = $this->request->post('color_id', null);       // Präsenz prüfen (0 = Schwarz!)
        $hasColor   = ($colorRaw !== null && $colorRaw !== '');
        $newColorId = (int) $colorRaw;
        $newCond    = (string) $this->request->post('new_cond', $cond);
        $qty        = (int) $this->request->post('quantity', 0);
        $back       = base_url('container/' . $locationId);

        if (!$this->mayEditOwner($ownerId, $user)) {
            $this->flash('error', 'Keine Berechtigung für diese Position.');
            $this->redirect($back);
        }
        if (!in_array($newCond, ['neu', 'gebraucht'], true)) {
            $this->flash('error', 'Ungültiger Zustand.');
            $this->redirect($back);
        }

        $item = $this->items->find($itemId);
        if ($item === null) {
            $this->flash('error', 'Position nicht gefunden.');
            $this->redirect($back);
        }

        // Zielfarbe bestimmen: nur für Elemente; sonst bleibt das Item gleich.
        $toItemId = $itemId;
        if ($item['type'] === 'element' && $hasColor && $newColorId !== (int) $item['color_id']) {
            if ($this->catalog->color($newColorId) === null) {
                $this->flash('error', 'Ungültige Farbe.');
                $this->redirect($back);
            }
            $toItemId = $this->items->findOrCreateElement($item['part_num'], $newColorId);
        }

        if ($toItemId === $itemId && $newCond === $cond) {
            $this->flash('info', 'Farbe und Zustand sind unverändert.');
            $this->redirect($back);
        }

        try {
            $this->stock->reclassify($itemId, $toItemId, $locationId, $ownerId, $cond, $newCond, $qty, $user->userId, 'Farbe/Zustand geändert');
            $this->flash('success', $qty . '× angepasst (Farbe/Zustand).');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
        $this->redirect($back);
    }

    /** Darf der Benutzer die Position dieses Besitzers bearbeiten? */
    private function mayEditOwner(int $ownerId, \App\Integration\WcfUser $user): bool
    {
        $owner = $this->owners->find($ownerId);
        if ($owner === null) {
            return false;
        }
        if ($owner['type'] === 'verein') {
            return $user->canWrite();
        }
        return ((int) $owner['wcf_user_id'] === $user->userId) || $user->canWrite();
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
