<?php
namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\Repository\LabelRepository;
use App\Model\Repository\LocationRepository;
use App\Model\Repository\OwnerRepository;
use App\Model\Repository\StocktakeRepository;

class StocktakeController extends Controller
{
    /** @var StocktakeRepository */
    private $runs;
    /** @var LocationRepository */
    private $locations;
    /** @var OwnerRepository */
    private $owners;
    /** @var LabelRepository */
    private $labels;

    public function __construct()
    {
        parent::__construct();
        $this->runs      = new StocktakeRepository();
        $this->locations = new LocationRepository();
        $this->owners    = new OwnerRepository();
        $this->labels    = new LabelRepository();
    }

    /** Liste aller Inventurläufe. */
    public function index(): void
    {
        $this->render('stocktake/list', [
            'title' => 'Inventur',
            'nav'   => 'stocktake',
            'runs'  => $this->runs->all(),
        ]);
    }

    /** Formular: neue Inventur. */
    public function create(): void
    {
        $this->requireLogin();
        $this->render('stocktake/form', [
            'title'     => 'Neue Inventur',
            'nav'       => 'stocktake',
            'locations' => $this->locations->all(),
            'owners'    => $this->owners->all(),
            'csrf'      => Csrf::token(),
        ]);
    }

    /** Inventurlauf anlegen. */
    public function store(): void
    {
        $user = $this->requireWrite();
        Csrf::validate($this->request->post('csrf_token'));

        $title = trim((string) $this->request->post('title', ''));
        if ($title === '') {
            $this->flash('error', 'Bitte einen Titel angeben.');
            $this->redirect(base_url('stocktake/new'));
        }
        $rootId  = $this->nullableInt($this->request->post('root_location_id'));
        $ownerId = $this->nullableInt($this->request->post('owner_id'));

        $id = $this->runs->create($title, $rootId, $ownerId, $user->userId);
        $this->flash('success', 'Inventur „' . $title . '" gestartet.');
        $this->redirect(base_url('stocktake/' . $id . '/scan'));
    }

    /** Detail mit Soll-Ist-Auswertung. */
    public function show($id): void
    {
        $run = $this->runs->find((int) $id);
        if ($run === null) {
            http_response_code(404);
            echo 'Inventur nicht gefunden';
            return;
        }
        $this->render('stocktake/detail', [
            'title'  => 'Inventur · ' . $run['title'],
            'nav'    => 'stocktake',
            'run'    => $run,
            'result' => $this->runs->reconcile($run),
            'csrf'   => Csrf::token(),
        ]);
    }

    /** Scan-Seite (manuelle Eingabe + in Step 7 Kamera-Scan). */
    public function scanForm($id): void
    {
        $this->requireLogin();
        $run = $this->runs->find((int) $id);
        if ($run === null) {
            http_response_code(404);
            echo 'Inventur nicht gefunden';
            return;
        }
        $this->render('stocktake/scan', [
            'title'   => 'Scannen · ' . $run['title'],
            'nav'     => 'stocktake',
            'run'     => $run,
            'recent'  => $this->runs->recentScans((int) $id),
            'count'   => $this->runs->scanCount((int) $id),
            'csrf'    => Csrf::token(),
        ]);
    }

    /** Scan erfassen. Antwortet als JSON (für Kamera-Scan) oder via Redirect. */
    public function scan($id): void
    {
        $this->requireWrite();
        Csrf::validate($this->request->post('csrf_token'));

        $runId = (int) $id;
        $run   = $this->runs->find($runId);
        $code  = trim((string) $this->request->post('code', ''));
        $wantsJson = strpos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;

        if ($run === null || $code === '') {
            if ($wantsJson) {
                $this->json(['ok' => false, 'message' => 'Ungültiger Lauf oder leerer Code.'], 400);
                return;
            }
            $this->flash('error', 'Leerer Code.');
            $this->redirect(base_url('stocktake/' . $runId . '/scan'));
        }

        $this->runs->addScan($runId, $code);
        $known = $this->labels->findByCode($code) !== null;

        if ($wantsJson) {
            $this->json([
                'ok'    => true,
                'code'  => $code,
                'known' => $known,
                'count' => $this->runs->scanCount($runId),
            ]);
            return;
        }

        $this->flash($known ? 'success' : 'warning',
            'Erfasst: ' . $code . ($known ? '' : ' (kein bekannter Behälter)'));
        $this->redirect(base_url('stocktake/' . $runId . '/scan'));
    }

    /** Inventur abschließen. */
    public function finish($id): void
    {
        $this->requireWrite();
        Csrf::validate($this->request->post('csrf_token'));
        $this->runs->finish((int) $id);
        $this->flash('info', 'Inventur abgeschlossen.');
        $this->redirect(base_url('stocktake/' . (int) $id));
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    }
}
