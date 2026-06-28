<?php
namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Integration\WcfSession;
use App\Model\Repository\HoldingRepository;
use App\Model\Repository\ItemRepository;
use App\Model\Repository\LocationRepository;
use App\Model\Repository\OwnerRepository;
use App\Model\Repository\StockMovementRepository;
use App\Service\StockService;

/**
 * Bestandsübersicht: globale Suche, Item-Detail (wo liegt es, wie viele,
 * verfügbar), Entnehmen und Umbuchen mit Bewegungs-Audit.
 */
class StockController extends Controller
{
    /** @var HoldingRepository */
    private $holdings;
    /** @var ItemRepository */
    private $items;
    /** @var OwnerRepository */
    private $owners;
    /** @var LocationRepository */
    private $locations;
    /** @var StockMovementRepository */
    private $movements;
    /** @var StockService */
    private $stock;

    public function __construct()
    {
        parent::__construct();
        $this->holdings  = new HoldingRepository();
        $this->items     = new ItemRepository();
        $this->owners    = new OwnerRepository();
        $this->locations = new LocationRepository();
        $this->movements = new StockMovementRepository();
        $this->stock     = new StockService();
    }

    /** Globale Bestandssuche. */
    public function index(): void
    {
        $q       = trim((string) $this->request->get('q', ''));
        $viewer  = WcfSession::user();
        $vid     = $viewer ? $viewer->userId : null;

        $this->render('stock/index', [
            'title'   => 'Bestand',
            'nav'     => 'stock',
            'q'       => $q,
            'results' => $q !== '' ? $this->holdings->searchItemsWithStock($q, $vid) : [],
        ]);
    }

    /** Item-Detail: Aggregat, Orte, Historie. */
    public function item($id): void
    {
        $item = $this->items->detail((int) $id);
        if ($item === null) {
            http_response_code(404);
            echo 'Item nicht gefunden';
            return;
        }
        $viewer = WcfSession::user();
        $vid    = $viewer ? $viewer->userId : null;

        $this->render('stock/item', [
            'title'     => 'Bestand · ' . ($item['part_name'] ?? $item['item_key'] ?? $item['id']),
            'nav'       => 'stock',
            'item'      => $item,
            'onHand'    => $this->holdings->onHandByItem((int) $id, $vid),
            'available' => $this->holdings->availableByItem((int) $id, $vid),
            'places'    => $this->holdings->locationsForItem((int) $id, $vid),
            'history'   => $this->movements->historyByItem((int) $id),
            'targets'   => $this->locations->byKinds(
                ['raum', 'schrank', 'schublade', 'box', 'fach', 'sonstiges', 'container', 'karton', 'tuete']
            ),
            'csrf'      => Csrf::token(),
        ]);
    }

    /** Entnahme aus einer Position. */
    public function remove($id): void
    {
        $user = $this->requireLogin();
        Csrf::validate($this->request->post('csrf_token'));
        $itemId = (int) $id;

        $locationId = (int) $this->request->post('location_id', 0);
        $ownerId    = (int) $this->request->post('owner_id', 0);
        $cond       = (string) $this->request->post('cond', 'gebraucht');
        $qty        = (int) $this->request->post('quantity', 0);

        if (!$this->mayEdit($ownerId, $user)) {
            $this->flash('error', 'Keine Berechtigung für diese Position.');
            $this->redirect(base_url('item/' . $itemId));
        }
        try {
            $this->stock->remove($itemId, $locationId, $ownerId, $cond, $qty, $user->userId, null);
            $this->flash('success', $qty . '× entnommen.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
        $this->redirect(base_url('item/' . $itemId));
    }

    /** Umbuchung von einem Ort an einen anderen. */
    public function move($id): void
    {
        $user = $this->requireLogin();
        Csrf::validate($this->request->post('csrf_token'));
        $itemId = (int) $id;

        $fromLocationId = (int) $this->request->post('location_id', 0);
        $toLocationId   = (int) $this->request->post('to_location_id', 0);
        $ownerId        = (int) $this->request->post('owner_id', 0);
        $cond           = (string) $this->request->post('cond', 'gebraucht');
        $qty            = (int) $this->request->post('quantity', 0);

        if (!$this->mayEdit($ownerId, $user)) {
            $this->flash('error', 'Keine Berechtigung für diese Position.');
            $this->redirect(base_url('item/' . $itemId));
        }
        if ($toLocationId <= 0) {
            $this->flash('error', 'Bitte einen Zielort wählen.');
            $this->redirect(base_url('item/' . $itemId));
        }
        try {
            $this->stock->move($itemId, $fromLocationId, $toLocationId, $ownerId, $cond, $qty, $user->userId, null);
            $this->flash('success', $qty . '× umgebucht.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
        $this->redirect(base_url('item/' . $itemId));
    }

    /** Darf der Benutzer diese (Besitzer-)Position bearbeiten? */
    private function mayEdit(int $ownerId, \App\Integration\WcfUser $user): bool
    {
        $owner = $this->owners->find($ownerId);
        if ($owner === null) {
            return false;
        }
        if ($owner['type'] === 'verein') {
            return $user->canWrite();   // Lagerwart/Vorstand-Gruppe
        }
        return ((int) $owner['wcf_user_id'] === $user->userId) || $user->canWrite();
    }
}
