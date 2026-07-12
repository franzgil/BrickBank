<?php
namespace App\Model\Repository;

use App\Core\Database;

/**
 * Zugriff auf die vereinheitlichte Item-Tabelle (element|set|minifig).
 * Verweist auf den Rebrickable-Katalog; keine harten FKs auf rb_*.
 */
class ItemRepository
{
    public function elementKey(string $partNum, int $colorId): string
    {
        return 'E:' . $partNum . ':' . $colorId;
    }

    /** Element-Item finden oder anlegen; gibt bb_item.id zurück (race-sicher). */
    public function findOrCreateElement(string $partNum, int $colorId): int
    {
        $key = $this->elementKey($partNum, $colorId);
        $db  = Database::app();
        $db->prepare(
            "INSERT IGNORE INTO bb_item (type, part_num, color_id, item_key)
             VALUES ('element', ?, ?, ?)"
        )->execute([$partNum, $colorId, $key]);

        $stmt = $db->prepare('SELECT id FROM bb_item WHERE item_key = ? LIMIT 1');
        $stmt->execute([$key]);
        return (int) $stmt->fetchColumn();
    }

    /** Einzelgewicht (Gramm) eines Items setzen/löschen. */
    public function setUnitWeight(int $id, ?float $grams): void
    {
        Database::app()->prepare('UPDATE bb_item SET unit_weight = ? WHERE id = ?')
            ->execute([$grams, $id]);
    }

    /**
     * Bekanntes Einzelgewicht für ein Teil (irgendeine Farbe), zum Vorbelegen.
     * Gewicht ist praktisch farbunabhängig, daher genügt die Teilenummer.
     */
    public function unitWeightForPart(string $partNum): ?float
    {
        $stmt = Database::app()->prepare(
            "SELECT unit_weight FROM bb_item
             WHERE type = 'element' AND part_num = ? AND unit_weight IS NOT NULL
             ORDER BY id LIMIT 1"
        );
        $stmt->execute([$partNum]);
        $v = $stmt->fetchColumn();
        return ($v !== false && $v !== null) ? (float) $v : null;
    }

    public function find(int $id): ?array
    {
        $stmt = Database::app()->prepare(
            'SELECT id, type, part_num, color_id, set_num, fig_num, item_key
             FROM bb_item WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Anzeigedetails inkl. Rebrickable-Namen (aktuell für Elemente). */
    public function detail(int $id): ?array
    {
        $stmt = Database::app()->prepare(
            "SELECT i.id, i.type, i.part_num, i.color_id, i.set_num, i.fig_num,
                    rp.name AS part_name, rc.name AS color_name
             FROM bb_item i
             LEFT JOIN rb_parts  rp ON i.type = 'element' AND rp.part_num = i.part_num
             LEFT JOIN rb_colors rc ON i.type = 'element' AND rc.id = i.color_id
             WHERE i.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
