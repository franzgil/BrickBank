<?php
namespace App\Model\Repository;

use App\Core\Database;

/**
 * Read-only-Zugriff auf den Rebrickable-Katalog (rb_*), der in derselben
 * Datenbank wie BrickBank liegt. Liefert Detaildaten zu Teilen, Farben und
 * Elementen. BrickBank schreibt NIE in rb_*-Tabellen.
 */
class CatalogRepository
{
    /** Teile-Suche nach Teilenummer oder Name. */
    public function searchParts(string $query, int $limit = 25): array
    {
        $limit = max(1, min(100, $limit));
        $like  = '%' . $query . '%';
        $stmt = Database::app()->prepare(
            'SELECT p.part_num, p.name, pc.name AS category
             FROM rb_parts p
             LEFT JOIN rb_part_categories pc ON pc.id = p.part_cat_id
             WHERE p.part_num = ? OR p.part_num LIKE ? OR p.name LIKE ?
             ORDER BY (p.part_num = ?) DESC, p.name
             LIMIT ' . $limit
        );
        $stmt->execute([$query, $like, $like, $query]);
        return $stmt->fetchAll();
    }

    /** Ein Teil samt Kategorie. */
    public function findPart(string $partNum): ?array
    {
        $stmt = Database::app()->prepare(
            'SELECT p.part_num, p.name, p.part_cat_id, pc.name AS category
             FROM rb_parts p
             LEFT JOIN rb_part_categories pc ON pc.id = p.part_cat_id
             WHERE p.part_num = ? LIMIT 1'
        );
        $stmt->execute([$partNum]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Alle Farben (id, name, rgb, is_trans). */
    public function colors(): array
    {
        return Database::app()
            ->query('SELECT id, name, rgb, is_trans FROM rb_colors ORDER BY name')
            ->fetchAll();
    }

    public function color(int $id): ?array
    {
        $stmt = Database::app()->prepare(
            'SELECT id, name, rgb, is_trans FROM rb_colors WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Welche Farben gibt es für ein Teil (laut rb_elements)? */
    public function colorsForPart(string $partNum): array
    {
        $stmt = Database::app()->prepare(
            'SELECT DISTINCT c.id, c.name, c.rgb
             FROM rb_elements e
             JOIN rb_colors c ON c.id = e.color_id
             WHERE e.part_num = ?
             ORDER BY c.name'
        );
        $stmt->execute([$partNum]);
        return $stmt->fetchAll();
    }

    /** Offizielle LEGO-Element-ID für part+color, falls vorhanden. */
    public function officialElementId(string $partNum, int $colorId): ?string
    {
        $stmt = Database::app()->prepare(
            'SELECT element_id FROM rb_elements
             WHERE part_num = ? AND color_id = ? ORDER BY element_id LIMIT 1'
        );
        $stmt->execute([$partNum, $colorId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string) $val : null;
    }

    /** Anreicherung eines bb_element (Teil+Farbe) mit Katalog-Details. */
    public function elementDetail(string $partNum, int $colorId): ?array
    {
        $stmt = Database::app()->prepare(
            'SELECT p.part_num, p.name AS part_name, pc.name AS category,
                    c.id AS color_id, c.name AS color_name, c.rgb AS color_rgb
             FROM rb_parts p
             LEFT JOIN rb_part_categories pc ON pc.id = p.part_cat_id
             JOIN rb_colors c ON c.id = ?
             WHERE p.part_num = ? LIMIT 1'
        );
        $stmt->execute([$colorId, $partNum]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
