<?php
namespace App\Controller;

use App\Core\Config;
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
        $page    = max(1, (int) $this->request->get('page', 1));
        $offset  = ($page - 1) * $limit;

        // --- Kategorie-Filter -------------------------------------------
        $categories = $this->catalog->categories();
        $allCatIds  = array_map(function ($c) { return (int) $c['id']; }, $categories);
        $excludedCatIds = $this->resolveExcludedCategories($categories, $allCatIds);
        $includePrinted = $this->resolveIncludePrinted();

        $verein = $this->owners->verein();
        $data = [
            'title'          => 'Bestand erfassen · ' . ($container['code'] ?? $container['name']),
            'nav'            => 'container',
            'container'      => $container,
            'q'              => $q,
            'results'        => [],
            'resultTotal'    => 0,
            'resultLimit'    => $limit,
            'page'           => $page,
            'resultFrom'     => 0,
            'resultTo'       => 0,
            'part'           => null,
            'colors'         => [],
            'unitWeight'     => null,
            'categories'     => $categories,
            'excludedCatIds' => $excludedCatIds,
            'includePrinted' => $includePrinted,
            'memberName'     => $user->username,
            'vereinName'     => $verein ? $verein['name'] : null,
            'canVerein'      => $user->canWrite(),
            'csrf'           => Csrf::token(),
        ];

        if ($partNum !== '') {
            $part = $this->catalog->findPart($partNum);
            if ($part !== null) {
                $colors = $this->catalog->colorsForPart($partNum);
                $data['part']       = $part;
                $data['colors']     = !empty($colors) ? $colors : $this->catalog->colors();
                $data['unitWeight'] = $this->items->unitWeightForPart($partNum);
            } else {
                $this->flash('error', 'Teil „' . $partNum . '" nicht im Katalog gefunden.');
            }
        } elseif ($q !== '') {
            $total = $this->catalog->countParts($q, $excludedCatIds, $includePrinted);
            // Falls die gewählte Seite hinter dem Ende liegt, auf die letzte Seite springen.
            if ($total > 0 && $offset >= $total) {
                $page   = (int) ceil($total / $limit);
                $offset = ($page - 1) * $limit;
            }
            $results = $this->catalog->searchParts($q, $limit, $offset, $excludedCatIds, $includePrinted);
            $data['results']     = $results;
            $data['resultTotal'] = $total;
            $data['page']        = $page;
            $data['resultFrom']  = $total > 0 ? $offset + 1 : 0;
            $data['resultTo']    = $offset + count($results);
        }

        $this->render('inventory/add', $data);
    }

    /**
     * Ermittelt die auszublendenden Kategorie-IDs.
     *  - Filter-Formular (Marker `catform`): angehakte Kategorien = sichtbar,
     *    alle übrigen werden ausgeblendet (fehlt `cat` ganz → alles ausblenden).
     *  - Link/Pagination (Marker `cf`): `xcat[]` = ausgeblendete Kategorien.
     *  - sonst: Standard-Ausschluss aus der Konfiguration (z. B. Duplo, Modulex).
     */
    private function resolveExcludedCategories(array $categories, array $allCatIds): array
    {
        if ($this->request->get('catform') !== null) {
            $included = array_map('intval', (array) ($this->request->get('cat') ?? []));
            $excluded = array_diff($allCatIds, $included);
        } elseif ($this->request->get('cf') !== null) {
            $excluded = array_map('intval', (array) ($this->request->get('xcat') ?? []));
        } else {
            $needles = Config::get('search.exclude_categories', [
                'Duplo', 'Modulex', 'Belville', 'Clikits', 'HO Scale',
                'Non-Buildable Figures', 'Znap',
            ]);
            $excluded = [];
            foreach ($categories as $c) {
                foreach ($needles as $n) {
                    if ($n !== '' && stripos($c['name'], (string) $n) !== false) {
                        $excluded[] = (int) $c['id'];
                        break;
                    }
                }
            }
        }
        return array_values(array_intersect($allCatIds, array_map('intval', $excluded)));
    }

    /**
     * Sollen bedruckte Teile mit angezeigt werden?
     * Bei aktivem Filter (catform/cf) entscheidet die Checkbox `printed`,
     * sonst der Default (Config search.hide_printed, standardmäßig ausblenden).
     */
    private function resolveIncludePrinted(): bool
    {
        if ($this->request->get('catform') !== null || $this->request->get('cf') !== null) {
            return $this->request->get('printed') !== null;
        }
        return !((bool) Config::get('search.hide_printed', true));
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
        $colorRaw   = $this->request->post('color_id', null);       // Präsenz prüfen (0 = Schwarz!)
        $colorId    = (int) $colorRaw;
        $qty        = (int) $this->request->post('quantity', 0);
        $cond       = (string) $this->request->post('cond', 'gebraucht');
        $ownerScope = (string) $this->request->post('owner_scope', 'mein');   // mein|verein
        $visibility = (string) $this->request->post('visibility', 'privat');

        $back = base_url('container/' . $container['id'] . '/add?part=' . urlencode($partNum));

        $errors = [];
        if ($partNum === '' || $this->catalog->findPart($partNum) === null) {
            $errors[] = 'Ungültiges Teil.';
        }
        if ($colorRaw === null || $colorRaw === '' || $this->catalog->color($colorId) === null) {
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

        // Einzelgewicht (falls angegeben) am Item merken – für spätere Zählungen.
        $unitWeight = (float) str_replace(',', '.', (string) $this->request->post('unit_weight', ''));
        if ($unitWeight > 0) {
            $this->items->setUnitWeight($itemId, $unitWeight);
        }

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
