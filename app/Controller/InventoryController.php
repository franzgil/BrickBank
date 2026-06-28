<?php
namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\Repository\CatalogRepository;
use App\Model\Repository\InventoryRepository;
use App\Model\Repository\LabelRepository;
use App\Model\Repository\OwnerRepository;

/**
 * Bestand zu einem Behälter erfassen (Teil aus Rebrickable + Farbe + Menge).
 */
class InventoryController extends Controller
{
    /** @var LabelRepository */
    private $labels;
    /** @var CatalogRepository */
    private $catalog;
    /** @var InventoryRepository */
    private $inventory;
    /** @var OwnerRepository */
    private $owners;

    public function __construct()
    {
        parent::__construct();
        $this->labels    = new LabelRepository();
        $this->catalog   = new CatalogRepository();
        $this->inventory = new InventoryRepository();
        $this->owners    = new OwnerRepository();
    }

    /** Erfassungs-Formular: Teil suchen → Teil gewählt → Farbe/Menge. */
    public function add($id): void
    {
        $this->requireLogin();
        $container = $this->labels->find((int) $id);
        if ($container === null) {
            http_response_code(404);
            echo 'Behälter nicht gefunden';
            return;
        }

        $q       = trim((string) $this->request->get('q', ''));
        $partNum = trim((string) $this->request->get('part', ''));

        $limit = 200;
        $data = [
            'title'       => 'Bestand erfassen · ' . $container['code'],
            'nav'         => 'container',
            'container'   => $container,
            'q'           => $q,
            'results'     => [],
            'resultTotal' => 0,
            'resultLimit' => $limit,
            'part'        => null,
            'colors'      => [],
            'owners'      => $this->owners->all(),
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

    /** Bestand buchen. */
    public function store($id): void
    {
        $this->requireWrite();
        Csrf::validate($this->request->post('csrf_token'));

        $container = $this->labels->find((int) $id);
        if ($container === null) {
            http_response_code(404);
            echo 'Behälter nicht gefunden';
            return;
        }

        $partNum = trim((string) $this->request->post('part_num', ''));
        $colorId = (int) $this->request->post('color_id', 0);
        $qty     = (int) $this->request->post('quantity', 0);
        $ownerId = (int) $this->request->post('owner_id', 0);

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
        if ($ownerId <= 0 || $this->owners->find($ownerId) === null) {
            $errors[] = 'Bitte einen gültigen Besitzer wählen.';
        }

        if ($errors) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect(base_url('container/' . $container['id'] . '/add?part=' . urlencode($partNum)));
        }

        $elementId = $this->inventory->findOrCreateElement($partNum, $colorId);
        $this->inventory->addStock($elementId, (int) $container['id'], $ownerId, $qty);

        $this->flash('success', $qty . '× ' . $partNum . ' erfasst in ' . $container['code'] . '.');
        $this->redirect(base_url('container/' . $container['id']));
    }
}
