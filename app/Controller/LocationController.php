<?php
namespace App\Controller;

use App\Core\Controller;
use App\Core\Csrf;
use App\Integration\WcfUser;
use App\Model\Repository\LabelRepository;
use App\Model\Repository\LocationRepository;
use App\Model\Repository\OwnerRepository;
use App\Service\CodeGenerator;
use App\Service\ContainerRules;

/**
 * Lagerbaum eines Kontos: Äste (Standorte/Behälter) anlegen und verschieben.
 */
class LocationController extends Controller
{
    /** @var LocationRepository */
    private $locations;
    /** @var LabelRepository */
    private $labels;
    /** @var OwnerRepository */
    private $owners;

    public function __construct()
    {
        parent::__construct();
        $this->locations = new LocationRepository();
        $this->labels    = new LabelRepository();
        $this->owners    = new OwnerRepository();
    }

    /** Ast anlegen (Standort oder Behälter) unter einem Konto. */
    public function addBranch($id): void
    {
        $user      = $this->requireLogin();
        $accountId = (int) $id;
        $owner     = $this->owners->find($accountId);
        if ($owner === null || !$this->mayEdit($owner, $user)) {
            $this->flash('error', 'Keine Berechtigung für diesen Lagerbaum.');
            $this->redirect(base_url('account/' . $accountId));
        }
        Csrf::validate($this->request->post('csrf_token'));

        $name     = trim((string) $this->request->post('name', ''));
        $kind     = (string) $this->request->post('kind', 'sonstiges');
        $parentId = $this->nullableInt($this->request->post('parent_id'));
        $count    = (int) $this->request->post('count', 1);
        $count    = max(1, min(200, $count));
        $note     = trim((string) $this->request->post('note', ''));
        $note     = $note === '' ? null : $note;

        if ($name === '') {
            $this->flash('error', 'Name darf nicht leer sein.');
            $this->redirect(base_url('account/' . $accountId));
        }
        // Zielast muss zum selben Konto gehören (sofern gesetzt).
        if ($parentId !== null) {
            $parent = $this->locations->find($parentId);
            if ($parent === null || (int) $parent['owner_id'] !== $accountId) {
                $this->flash('error', 'Ungültiger übergeordneter Ast.');
                $this->redirect(base_url('account/' . $accountId));
            }
        }

        $isContainer = CodeGenerator::isContainerKind($kind);
        $codes = [];
        for ($i = 1; $i <= $count; $i++) {
            $thisName = $count > 1 ? ($name . ' ' . $i) : $name;
            if ($isContainer) {
                $created = $this->labels->createContainer($kind, $thisName, $parentId, $accountId, $note);
                $codes[] = $created['code'];
            } else {
                $this->locations->createPlace($accountId, $parentId, $thisName, $kind, $note);
            }
        }

        if ($isContainer) {
            $this->flash('success', $count . '× ' . ContainerRules::label($kind)
                . ' angelegt (' . $codes[0] . ($count > 1 ? ' … ' . end($codes) : '') . ').');
        } else {
            $this->flash('success', $count . '× Ast „' . $name . '" angelegt.');
        }
        $this->redirect(base_url('account/' . $accountId));
    }

    /** Ast im Baum verschieben (frei, zyklensicher; Root = direkt am Konto). */
    public function moveBranch($id): void
    {
        $user      = $this->requireLogin();
        $accountId = (int) $id;
        $owner     = $this->owners->find($accountId);
        if ($owner === null || !$this->mayEdit($owner, $user)) {
            $this->flash('error', 'Keine Berechtigung für diesen Lagerbaum.');
            $this->redirect(base_url('account/' . $accountId));
        }
        Csrf::validate($this->request->post('csrf_token'));

        $locationId  = (int) $this->request->post('location_id', 0);
        $toParentId  = $this->nullableInt($this->request->post('to_parent_id'));

        $branch = $this->locations->find($locationId);
        if ($branch === null || (int) $branch['owner_id'] !== $accountId) {
            $this->flash('error', 'Ast gehört nicht zu diesem Konto.');
            $this->redirect(base_url('account/' . $accountId));
        }
        if ($toParentId !== null) {
            $target = $this->locations->find($toParentId);
            if ($target === null || (int) $target['owner_id'] !== $accountId) {
                $this->flash('error', 'Zielast gehört nicht zu diesem Konto.');
                $this->redirect(base_url('account/' . $accountId));
            }
        }

        $result = $this->locations->moveBranch($locationId, $toParentId);
        $this->flash($result['ok'] ? 'success' : 'error', $result['message']);
        $this->redirect(base_url('account/' . $accountId));
    }

    private function mayEdit(array $owner, WcfUser $user): bool
    {
        if ($owner['type'] === 'verein') {
            return $user->canWrite();
        }
        return ((int) $owner['wcf_user_id'] === $user->userId) || $user->canWrite();
    }
}
