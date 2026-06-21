<?php
namespace App\Model\Repository;

use App\Core\Database;
use App\Service\ContainerRules;

/**
 * Führt Behälter-Umzüge aus und protokolliert sie lückenlos.
 */
class MovementRepository
{
    const MOVED    = 'moved';
    const NOCHANGE = 'nochange';
    const INVALID  = 'invalid';
    const NOTFOUND = 'notfound';

    /**
     * Räumt einen Behälter (location mit Label) an einen neuen Standort.
     * Prüft die Typregel, schreibt UPDATE + Historie in einer Transaktion.
     *
     * @return array{status:string,message:string}
     */
    public function move(int $locationId, ?int $newParentId, ?string $note, ?int $wcfUserId): array
    {
        $db = Database::app();

        // Bewegten Behälter laden (inkl. Snapshot des bisherigen Orts-Codes).
        $stmt = $db->prepare(
            'SELECT l.id, l.parent_id, l.kind, ll.code, pll.code AS from_code
             FROM bb_location l
             JOIN bb_location_label ll ON ll.location_id = l.id
             LEFT JOIN bb_location_label pll ON pll.location_id = l.parent_id
             WHERE l.id = ? LIMIT 1'
        );
        $stmt->execute([$locationId]);
        $obj = $stmt->fetch();
        if (!$obj) {
            return ['status' => self::NOTFOUND, 'message' => 'Behälter nicht gefunden.'];
        }

        $kind          = $obj['kind'];
        $currentParent = $obj['parent_id'] !== null ? (int) $obj['parent_id'] : null;

        // Zielort prüfen (sofern gesetzt).
        $toCode = null;
        if ($newParentId !== null) {
            if ($newParentId === $locationId) {
                return ['status' => self::INVALID, 'message' => 'Ein Behälter kann nicht in sich selbst gelegt werden.'];
            }
            $t = $db->prepare(
                'SELECT l.kind, ll.code
                 FROM bb_location l LEFT JOIN bb_location_label ll ON ll.location_id = l.id
                 WHERE l.id = ? LIMIT 1'
            );
            $t->execute([$newParentId]);
            $target = $t->fetch();
            if (!$target) {
                return ['status' => self::NOTFOUND, 'message' => 'Zielort nicht gefunden.'];
            }
            if (!ContainerRules::parentAllowed($kind, $target['kind'])) {
                return ['status' => self::INVALID, 'message' => ContainerRules::ruleMessage($kind)];
            }
            $toCode = $target['code'];
        }

        if ($newParentId === $currentParent) {
            return ['status' => self::NOCHANGE, 'message' => 'Der Behälter liegt bereits an diesem Ort.'];
        }

        $db->beginTransaction();
        try {
            $u = $db->prepare('UPDATE bb_location SET parent_id = ? WHERE id = ?');
            $u->execute([$newParentId, $locationId]);

            $ins = $db->prepare(
                'INSERT INTO bb_location_movement
                    (location_id, code, from_parent_id, from_code, to_parent_id, to_code, wcf_user_id, note)
                 VALUES (?,?,?,?,?,?,?,?)'
            );
            $ins->execute([
                $locationId, $obj['code'],
                $currentParent, $obj['from_code'],
                $newParentId, $toCode,
                $wcfUserId, $note,
            ]);

            $db->commit();
            return [
                'status'  => self::MOVED,
                'message' => 'Umgeräumt: ' . $obj['code'] . ' → ' . ($toCode ?? 'kein Standort'),
            ];
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** Bewegungshistorie eines Behälters, neueste zuerst. */
    public function history(int $locationId): array
    {
        $stmt = Database::app()->prepare(
            'SELECT moved_at, from_code, to_code, from_parent_id, to_parent_id, wcf_user_id, note
             FROM bb_location_movement
             WHERE location_id = ?
             ORDER BY moved_at DESC, id DESC'
        );
        $stmt->execute([$locationId]);
        return $stmt->fetchAll();
    }
}
