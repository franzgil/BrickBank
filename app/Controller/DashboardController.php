<?php
namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Integration\WcfUser;
use App\Model\Repository\HoldingRepository;
use App\Model\Repository\LocationRepository;
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
    /** @var LocationRepository */
    private $locations;

    public function __construct()
    {
        parent::__construct();
        $this->holdings  = new HoldingRepository();
        $this->owners    = new OwnerRepository();
        $this->locations = new LocationRepository();
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
        $branches = $this->locations->treeForOwner((int) $id);
        $byParent = [];
        foreach ($branches as $b) {
            $byParent[(int) $b['parent_id']][] = $b;   // parent_id NULL → Schlüssel 0 (Root)
        }

        $this->render('dashboard/account', [
            'title'    => ($owner['type'] === 'projekt' ? 'Projekt · ' : 'Konto · ') . $owner['name'],
            'nav'      => $owner['type'] === 'projekt' ? 'project' : 'dashboard',
            'owner'    => $owner,
            'isMe'     => (int) $owner['wcf_user_id'] === $user->userId,
            'byParent' => $byParent,
            'branches' => $branches,
            'summary'  => $this->holdings->summaryByLocationForOwnerTree((int) $id, $user->userId),
            'canEdit'  => $this->mayEdit($owner, $user),
            'csrf'     => Csrf::token(),
        ]);
    }

    private function mayEdit(array $owner, WcfUser $user): bool
    {
        if ($owner['type'] === 'verein') {
            return $user->canWrite();
        }
        // Privat + Projekt: der/die Zugeordnete (Mitglied bzw. Projektleitung) oder Vorstand/Lagerwart.
        return ((int) $owner['wcf_user_id'] === $user->userId) || $user->canWrite();
    }
}
