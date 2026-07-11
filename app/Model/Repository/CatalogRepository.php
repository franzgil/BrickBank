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
    /**
     * Teile-Suche nach Teilenummer oder Name (token- und leerzeichentolerant).
     * $offset erlaubt seitenweises Blättern (z. B. die nächsten 200).
     * Liefert je Teil eine repräsentative element_id (für ein Vorschaubild).
     */
    public function searchParts(string $query, int $limit = 200, int $offset = 0, array $excludeCatIds = [], bool $includePrinted = true): array
    {
        $limit  = max(1, min(1000, $limit));
        $offset = max(0, $offset);
        list($where, $params) = $this->buildSearch($query);
        $where .= $this->categoryExclusion($excludeCatIds, $params);
        $where .= $this->printedExclusion($includePrinted);

        $params[] = trim($query); // für die Sortierung (exakte Teilenummer zuerst)
        $sql = 'SELECT p.part_num, p.name, pc.name AS category,
                       (SELECT re.element_id FROM rb_elements re
                         WHERE re.part_num = p.part_num
                         ORDER BY re.element_id LIMIT 1) AS element_id
                FROM rb_parts p
                LEFT JOIN rb_part_categories pc ON pc.id = p.part_cat_id
                WHERE ' . $where . '
                ORDER BY (p.part_num = ?) DESC, p.name
                LIMIT ' . $limit . ' OFFSET ' . $offset;
        $stmt = Database::app()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Gesamtzahl der Treffer zu einer Teile-Suche (für die Anzeige). */
    public function countParts(string $query, array $excludeCatIds = [], bool $includePrinted = true): int
    {
        list($where, $params) = $this->buildSearch($query);
        $where .= $this->categoryExclusion($excludeCatIds, $params);
        $where .= $this->printedExclusion($includePrinted);
        $stmt = Database::app()->prepare('SELECT COUNT(*) FROM rb_parts p WHERE ' . $where);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Schließt bedruckte Teile aus, wenn $includePrinted = false.
     * Bedruckte Teilenummern haben „pr" gefolgt von Ziffern (z. B. 2431pr0121).
     */
    private function printedExclusion(bool $includePrinted): string
    {
        return $includePrinted ? '' : " AND p.part_num NOT REGEXP 'pr[0-9]'";
    }

    /** Alle Teilekategorien (id, name) für den Kategorie-Filter. */
    public function categories(): array
    {
        return Database::app()
            ->query('SELECT id, name FROM rb_part_categories ORDER BY name')
            ->fetchAll();
    }

    /**
     * Baut die Kategorie-Ausschluss-Bedingung und hängt die Parameter an.
     * Teile ohne Kategorie bleiben sichtbar. Gibt das SQL-Fragment zurück
     * (mit führendem AND) oder '' bei leerem Ausschluss.
     */
    private function categoryExclusion(array $excludeCatIds, array &$params): string
    {
        $ids = array_values(array_unique(array_map('intval', $excludeCatIds)));
        if (empty($ids)) {
            return '';
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        foreach ($ids as $cid) {
            $params[] = $cid;
        }
        return ' AND (p.part_cat_id IS NULL OR p.part_cat_id NOT IN (' . $ph . '))';
    }

    /**
     * Baut die WHERE-Bedingung für die Teile-Suche.
     * Jeder Suchbegriff (durch Leerzeichen getrennt) muss vorkommen –
     * entweder in der Teilenummer oder im Namen.
     *
     * - Der erste Wort-Begriff (nur Buchstaben, z. B. „Plate") ist der Teiletyp
     *   und muss am NAMENSANFANG stehen. So trifft „Plate" weder „Baseplate"
     *   (ein Wort) noch „Base Plate" (zwei Wörter).
     * - Weitere Wort-Begriffe matchen am Wortanfang (Namensanfang oder nach
     *   einem Leerzeichen).
     * - Begriffe mit Ziffern/„x" (z. B. „2x4") matchen leerzeichentolerant
     *   („2x4" trifft „2 x 4").
     *
     * @return array{0:string,1:array}
     */
    private function buildSearch(string $query): array
    {
        $tokens = preg_split('~\s+~', trim($query), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($tokens)) {
            return ['1=0', []];
        }
        $clauses  = [];
        $params   = [];
        $firstAlpha = true;   // erster reiner Wort-Begriff = Teiletyp
        foreach ($tokens as $t) {
            // LIKE-Sonderzeichen entschärfen.
            $esc = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $t);
            if (ctype_alpha($t)) {
                if ($firstAlpha) {
                    // Teiletyp: Name muss mit dem Begriff beginnen.
                    $clauses[] = '(p.part_num LIKE ? OR p.name LIKE ?)';
                    $params[]  = '%' . $esc . '%';
                    $params[]  = $esc . '%';
                    $firstAlpha = false;
                } else {
                    // Wortanfang: Namensanfang oder nach einem Leerzeichen.
                    $clauses[] = '(p.part_num LIKE ? OR p.name LIKE ? OR p.name LIKE ?)';
                    $params[]  = '%' . $esc . '%';
                    $params[]  = $esc . '%';
                    $params[]  = '% ' . $esc . '%';
                }
            } else {
                // Leerzeichentolerant (Maße wie „2x4").
                $like = '%' . $esc . '%';
                $clauses[] = "(p.part_num LIKE ? OR REPLACE(p.name, ' ', '') LIKE ?)";
                $params[]  = $like;
                $params[]  = $like;
            }
        }
        return ['(' . implode(' AND ', $clauses) . ')', $params];
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

    /** Anreicherung eines Elements (Teil+Farbe) mit Katalog-Details. */
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
