<?php
namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Integration\WcfSession;
use App\Model\Repository\CatalogRepository;
use App\Model\Repository\ItemRepository;
use App\Model\Repository\LocationRepository;
use App\Model\Repository\OwnerRepository;
use App\Service\StockService;

/**
 * Bestand zu einem Behälter erfassen: Teil (Rebrickable) + Farbe + Zustand
 * + Menge, mit Besitzer (Mein Bestand / Verein) und Sichtbarkeit.
 */
class InventoryController extends Controller
{
    /** @var LocationRepository */
    private $locations;
    /** @var CatalogRepository */
    private $catalog;
    /** @var ItemRepository */
    private $items;
    /** @var OwnerRepository */
    private $owners;
    /** @var StockService */
    private $stock;

    public function __construct()
    {
        parent::__construct();
        $this->locations = new LocationRepository();
        $this->catalog = new CatalogRepository();
        $this->items   = new ItemRepository();
        $this->owners  = new OwnerRepository();
        $this->stock   = new StockService();
    }

    public function add($id): void
    {
        $user = $this->requireLogin();
        $container = $this->locations->findDetail((int) $id);
        if ($container === null) {
            http_response_code(404);
            echo 'Behälter nicht gefunden';
            return;
        }

        $q       = trim((string) $this->request->get('q', ''));
        $partNum = trim((string) $this->request->get('part', ''));
        $limit   = 200;

        $verein = $this->owners->verein();
        $data = [
            'title'       => 'Bestand erfassen · ' . ($container['code'] ?? $container['name']),
            'nav'         => 'container',
            'container'   => $container,
            'q'           => $q,
            'results'     => [],
            'resultTotal' => 0,
            'resultLimit' => $limit,
            'part'        => null,
            'colors'      => [],
            'memberName'  => $user->username,
            'vereinName'  => $verein ? $verein['name'] : null,
            'canVerein'   => $user->canWrite(),
            'csrf'        => Csrf::token(),
        ];

        if ($partNum !== '') {
            $part = $this->catalog->findPart($partNum);
            if ($part !== null) {
                $colors = $this->catalog->colorsForPart($partNum);
                $data['part']   = $part;
                $data['colors'] = !empty($colors) ? $colors : $this->catalog->colors();
            } else {
                $this->flash('error', 'Teil „' . $partNum . '" nicht im Katalog gefunden.');
            }
        } elseif ($q !== '') {
            $data['results']     = $this->catalog->searchParts($q, $limit);
            $data['resultTotal'] = $this->catalog->countParts($q);
        }

        $this->render('inventory/add', $data);
    }

    public function store($id): void
    {
        $user = $this->requireLogin();
        Csrf::validate($this->request->post('csrf_token'));

        $container = $this->locations->findDetail((int) $id);
        if ($container === null) {
            http_response_code(404);
            echo 'Behälter nicht gefunden';
            return;
        }

        $partNum    = trim((string) $this->request->post('part_num', ''));
        $colorId    = (int) $this->request->post('color_id', 0);
        $qty        = (int) $this->request->post('quantity', 0);
        $cond       = (string) $this->request->post('cond', 'gebraucht');
        $ownerScope = (string) $this->request->post('owner_scope', 'mein');   // mein|verein
        $visibility = (string) $this->request->post('visibility', 'privat');

        $back = base_url('container/' . $container['id'] . '/add?part=' . urlencode($partNum));

        $errors = [];
        if ($partNum === '' || $this->catalog->findPart($partNum) === null) {
            $errors[] = 'Ungültiges Teil.';
        }
        if ($colorId <= 0 || $this->catalog->color($colorId) === null) {
            $errors[] = 'Ungültige Farbe.';
        }
        if ($qty < 1) {
            $errors[] = 'Menge muss mindestens 1 sein.';
        }
        if (!in_array($cond, ['neu', 'gebraucht'], true)) {
            $errors[] = 'Ungültiger Zustand.';
        }
        if ($errors) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect($back);
        }

        // Besitzer + Sichtbarkeit bestimmen.
        if ($ownerScope === 'verein') {
            if (!$user->canWrite()) {
                $this->flash('error', 'Keine Berechtigung für Vereinsbestand.');
                $this->redirect($back);
            }
            $verein = $this->owners->verein();
            if ($verein === null) {
                $this->flash('error', 'Kein Vereins-Besitzer angelegt (seed.sql).');
                $this->redirect($back);
            }
            $ownerId    = (int) $verein['id'];
            $visibility = in_array($visibility, ['intern', 'verein'], true) ? $visibility : 'intern';
        } else {
            $ownerId    = $this->owners->findOrCreateForWcfUser($user->userId, $user->username, $user->email);
            $visibility = in_array($visibility, ['privat', 'intern', 'verein'], true) ? $visibility : 'privat';
        }

        $itemId = $this->items->findOrCreateElement($partNum, $colorId);
        try {
            $this->stock->add($itemId, (int) $container['id'], $ownerId, $cond, $visibility, $qty, $user->userId, null);
        } catch (\Throwable $e) {
            $this->flash('error', 'Buchung fehlgeschlagen: ' . $e->getMessage());
            $this->redirect($back);
        }

        $this->flash('success', $qty . '× ' . $partNum . ' (' . $cond . ') erfasst in ' . ($container['code'] ?? $container['name']) . '.');
        $this->redirect(base_url('container/' . $container['id']));
    }
}
