<?php
namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Model\Repository\LabelRepository;
use App\Model\Repository\LocationRepository;
use App\Model\Repository\MovementRepository;

class MoveController extends Controller
{
    /** @var LabelRepository */
    private $labels;
    /** @var LocationRepository */
    private $locations;
    /** @var MovementRepository */
    private $movements;

    public function __construct()
    {
        parent::__construct();
        $this->labels    = new LabelRepository();
        $this->locations = new LocationRepository();
        $this->movements = new MovementRepository();
    }

    /** Umräum-Formular (optional vorbelegt über ?code=). */
    public function form(): void
    {
        $this->requireLogin();
        $code      = trim((string) $this->request->get('code', ''));
        $container = $code !== '' ? $this->labels->findByCode($code) : null;

        $this->render('move/form', [
            'title'         => 'Umräumen',
            'nav'           => 'container',
            'code'          => $code,
            'container'     => $container,
            'parentOptions' => $this->locations->byKinds(
                ['raum', 'schrank', 'schublade', 'box', 'fach', 'sonstiges', 'container', 'karton']
            ),
            'csrf'          => Csrf::token(),
        ]);
    }

    /** Umzug ausführen. Akzeptiert Zielort per target_code (Scan) oder target_id (Auswahl). */
    public function perform(): void
    {
        $user = $this->requireWrite();
        Csrf::validate($this->request->post('csrf_token'));

        $code = trim((string) $this->request->post('code', ''));
        $container = $code !== '' ? $this->labels->findByCode($code) : null;
        if ($container === null) {
            $this->flash('error', 'Behälter-Code „' . $code . '" wurde nicht gefunden.');
            $this->redirect(base_url('move'));
        }

        // Zielort bestimmen: target_code hat Vorrang (Scan), sonst target_id (Auswahl), sonst null.
        $targetId   = $this->nullableInt($this->request->post('target_id'));
        $targetCode = trim((string) $this->request->post('target_code', ''));
        if ($targetCode !== '') {
            $target = $this->labels->findByCode($targetCode);
            if ($target === null) {
                $this->flash('error', 'Zielort-Code „' . $targetCode . '" wurde nicht gefunden.');
                $this->redirect(base_url('move?code=' . urlencode($code)));
            }
            $targetId = (int) $target['id'];
        }

        $note = trim((string) $this->request->post('note', ''));
        $note = $note === '' ? null : $note;

        $result = $this->movements->move((int) $container['id'], $targetId, $note, $user->userId);

        $type = ($result['status'] === MovementRepository::MOVED) ? 'success'
              : (($result['status'] === MovementRepository::NOCHANGE) ? 'info' : 'error');
        $this->flash($type, $result['message']);

        $this->redirect(base_url('container/' . $container['id']));
    }

    /** Bewegungshistorie eines Behälters. */
    public function history($id): void
    {
        $container = $this->labels->find((int) $id);
        if ($container === null) {
            http_response_code(404);
            echo 'Behälter nicht gefunden';
            return;
        }
        $this->render('move/history', [
            'title'     => 'Verlauf · ' . $container['code'],
            'nav'       => 'container',
            'container' => $container,
            'history'   => $this->movements->history((int) $id),
        ]);
    }
}
