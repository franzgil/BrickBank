<?php
namespace App\Model\Repository;

use App\Core\Database;

/**
 * Lückenloses Mengen-Audit (bb_inventory_movement).
 */
class StockMovementRepository
{
    public function log(
        int $itemId,
        int $ownerId,
        string $cond,
        ?int $fromLocationId,
        ?int $toLocationId,
        int $quantity,
        string $type,
        ?int $wcfUserId,
        ?string $note
    ): void {
        Database::app()->prepare(
            'INSERT INTO bb_inventory_movement
                (item_id, owner_id, cond, from_location_id, to_location_id, quantity, type, wcf_user_id, note)
             VALUES (?,?,?,?,?,?,?,?,?)'
        )->execute([$itemId, $ownerId, $cond, $fromLocationId, $toLocationId, $quantity, $type, $wcfUserId, $note]);
    }

    /**
     * Alle Bewegungen einer konkreten Position (Item·Ort·Besitzer·Zustand),
     * neueste zuerst – inkl. Namen/Codes der Quell-/Zielorte.
     */
    public function historyByPosition(int $itemId, int $locationId, int $ownerId, string $cond, int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        $stmt = Database::app()->prepare(
            'SELECT m.created_at, m.type, m.quantity, m.cond, m.note, m.wcf_user_id,
                    m.from_location_id, m.to_location_id,
                    fl.name AS from_name, fll.code AS from_code,
                    tl.name AS to_name,  tll.code AS to_code
             FROM bb_inventory_movement m
             LEFT JOIN bb_location fl        ON fl.id = m.from_location_id
             LEFT JOIN bb_location_label fll ON fll.location_id = fl.id
             LEFT JOIN bb_location tl        ON tl.id = m.to_location_id
             LEFT JOIN bb_location_label tll ON tll.location_id = tl.id
             WHERE m.item_id = ? AND m.owner_id = ? AND m.cond = ?
                   AND (m.from_location_id = ? OR m.to_location_id = ?)
             ORDER BY m.created_at DESC, m.id DESC
             LIMIT ' . $limit
        );
        $stmt->execute([$itemId, $ownerId, $cond, $locationId, $locationId]);
        return $stmt->fetchAll();
    }

    /** Bewegungshistorie eines Items, neueste zuerst. */
    public function historyByItem(int $itemId, int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $stmt = Database::app()->prepare(
            'SELECT m.created_at, m.type, m.quantity, m.cond, m.note,
                    m.from_location_id, m.to_location_id, m.wcf_user_id, o.name AS owner_name
             FROM bb_inventory_movement m
             JOIN bb_owner o ON o.id = m.owner_id
             WHERE m.item_id = ?
             ORDER BY m.created_at DESC, m.id DESC
             LIMIT ' . $limit
        );
        $stmt->execute([$itemId]);
        return $stmt->fetchAll();
    }
}
