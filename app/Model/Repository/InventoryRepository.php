<?php
namespace App\Model\Repository;

use App\Core\Database;

/**
 * Bestandsbuchungen (bb_inventory_item) und Auflösen/Anlegen von bb_element.
 */
class InventoryRepository
{
    /**
     * Liefert die bb_element-ID für ein Teil+Farbe, legt es bei Bedarf an.
     * INSERT IGNORE + SELECT ist race-sicher (uq_element schützt).
     */
    public function findOrCreateElement(string $partNum, int $colorId): int
    {
        $db = Database::app();
        $db->prepare('INSERT IGNORE INTO bb_element (part_num, color_id) VALUES (?, ?)')
           ->execute([$partNum, $colorId]);
        $stmt = $db->prepare('SELECT id FROM bb_element WHERE part_num = ? AND color_id = ? LIMIT 1');
        $stmt->execute([$partNum, $colorId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Bucht Menge auf einen Bestandsposten (Element + Ort + Besitzer).
     * Existiert der Posten bereits, wird die Menge addiert.
     */
    public function addStock(int $elementId, int $locationId, int $ownerId, int $quantity): void
    {
        $stmt = Database::app()->prepare(
            'INSERT INTO bb_inventory_item (element_id, location_id, owner_id, quantity)
             VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE quantity = quantity + ?'
        );
        $stmt->execute([$elementId, $locationId, $ownerId, $quantity, $quantity]);
    }
}
