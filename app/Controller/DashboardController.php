<?php
namespace App\Controller;

use App\Core\Controller;
use App\Integration\WcfSession;
use App\Model\Repository\HoldingRepository;
use App\Model\Repository\OwnerRepository;

/**
 * Dashboard: Karten je „Konto" (Besitzer-Bestand), zu dem das Mitglied Zugang
 * hat – eigener Bestand, Verein und intern/verein-freigegebener Bestand anderer.
 */
class DashboardController extends Controller
{
    /** @var HoldingRepository */
    private $holdings;
    /** @var OwnerRepository */
    private $owners;

    public function __construct()
    {
        parent::__construct();
        $this->holdings = new HoldingRepository();
        $this->owners   = new OwnerRepository();
    }

    public function index(): void
    {
        $user = $this->requireLogin();
        $this->render('dashboard/index', [
            'title'    => 'Dashboard',
            'nav'      => 'dashboard',
            'accounts' => $this->holdings->accountsForViewer($user->userId),
            'meId'     => $user->userId,
        ]);
    }

    /** Konto-Detail: sichtbare Bestände eines Besitzers. */
    public function account($id): void
    {
        $user  = $this->requireLogin();
        $owner = $this->owners->find((int) $id);
        if ($owner === null) {
            http_response_code(404);
            echo 'Konto nicht gefunden';
            return;
        }
        $this->render('dashboard/account', [
            'title'    => 'Konto · ' . $owner['name'],
            'nav'      => 'dashboard',
            'owner'    => $owner,
            'isMe'     => (int) $owner['wcf_user_id'] === $user->userId,
            'holdings' => $this->holdings->holdingsForOwner((int) $id, $user->userId),
        ]);
    }
}
