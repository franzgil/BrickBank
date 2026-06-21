<?php
namespace App\Model\Repository;

use App\Core\Database;

/**
 * Zugriff auf generische Lagerorte (`location`).
 */
class LocationRepository
{
    public function find(int $id): ?array
    {
        $stmt = Database::app()->prepare(
            'SELECT id, parent_id, name, kind, note FROM bb_location WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Alle Orte (für Auswahllisten). */
    public function all(): array
    {
        return Database::app()
            ->query('SELECT id, parent_id, name, kind FROM bb_location ORDER BY kind, name')
            ->fetchAll();
    }

    /** Orte der angegebenen Arten (z. B. alle Kartons als Zielort einer Tüte). */
    public function byKinds(array $kinds): array
    {
        if (empty($kinds)) {
            return [];
        }
        $place = implode(',', array_fill(0, count($kinds), '?'));
        $stmt = Database::app()->prepare(
            'SELECT l.id, l.name, l.kind, ll.code
             FROM bb_location l
             LEFT JOIN bb_location_label ll ON ll.location_id = l.id
             WHERE l.kind IN (' . $place . ')
             ORDER BY l.kind, COALESCE(ll.code, l.name)'
        );
        $stmt->execute(array_values($kinds));
        return $stmt->fetchAll();
    }

    /** Nicht-Behälter-Orte (Raum/Schrank/…); sinnvoll als Standort eines Containers. */
    public function placeLocations(): array
    {
        return $this->byKinds(['raum', 'schrank', 'schublade', 'box', 'fach', 'sonstiges']);
    }

    /**
     * Alle Nachfahren-IDs eines Ortes inkl. des Ortes selbst (rekursiv, in PHP).
     * Für die Inventur-Eingrenzung (root_location_id).
     */
    public function descendantIds(int $rootId): array
    {
        $rows = Database::app()
            ->query('SELECT id, parent_id FROM bb_location')
            ->fetchAll();
        $childrenOf = [];
        foreach ($rows as $r) {
            $childrenOf[(int) $r['parent_id']][] = (int) $r['id'];
        }
        $result = [];
        $queue = [$rootId];
        while ($queue) {
            $id = array_pop($queue);
            if (isset($result[$id])) {
                continue;
            }
            $result[$id] = true;
            if (isset($childrenOf[$id])) {
                foreach ($childrenOf[$id] as $child) {
                    $queue[] = $child;
                }
            }
        }
        return array_keys($result);
    }
}
