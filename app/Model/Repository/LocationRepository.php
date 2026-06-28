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
            'SELECT id, parent_id, owner_id, name, kind, note, image_path FROM bb_location WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Setzt/löscht den Bildpfad eines Astes. */
    public function setImagePath(int $id, ?string $path): void
    {
        Database::app()->prepare('UPDATE bb_location SET image_path = ? WHERE id = ?')
            ->execute([$path, $id]);
    }

    /**
     * Löscht einen Ast – nur wenn leer (keine Unter-Äste, kein Bestand).
     * @return array{ok:bool,message:string,image_path:?string}
     */
    public function deleteBranch(int $id): array
    {
        $db = Database::app();

        $c = $db->prepare('SELECT COUNT(*) FROM bb_location WHERE parent_id = ?');
        $c->execute([$id]);
        if ((int) $c->fetchColumn() > 0) {
            return ['ok' => false, 'message' => 'Ast hat Unter-Äste – diese zuerst löschen oder verschieben.', 'image_path' => null];
        }

        $h = $db->prepare('SELECT COUNT(*) FROM bb_holding WHERE location_id = ?');
        $h->execute([$id]);
        if ((int) $h->fetchColumn() > 0) {
            return ['ok' => false, 'message' => 'Ast enthält Bestand – zuerst entnehmen oder umbuchen.', 'image_path' => null];
        }

        $img = $db->prepare('SELECT image_path FROM bb_location WHERE id = ?');
        $img->execute([$id]);
        $imagePath = $img->fetchColumn();

        // bb_location_label / _movement hängen per ON DELETE CASCADE mit.
        $db->prepare('DELETE FROM bb_location WHERE id = ?')->execute([$id]);

        return ['ok' => true, 'message' => 'Ast gelöscht.', 'image_path' => $imagePath ?: null];
    }

    /** Detaildaten eines Astes (generisch: mit oder ohne Etikett/Code). */
    public function findDetail(int $id): ?array
    {
        $stmt = Database::app()->prepare(
            'SELECT l.id, l.parent_id, l.owner_id, l.name, l.kind, l.note, l.image_path,
                    ll.code, ll.custom_code, ll.rfid_epc,
                    p.name AS parent_name, p.kind AS parent_kind, pll.code AS parent_code
             FROM bb_location l
             LEFT JOIN bb_location_label ll ON ll.location_id = l.id
             LEFT JOIN bb_location p       ON p.id = l.parent_id
             LEFT JOIN bb_location_label pll ON pll.location_id = p.id
             WHERE l.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Alle Äste eines Kontos (für den Lagerbaum), inkl. Behälter-Code. */
    public function treeForOwner(int $ownerId): array
    {
        $stmt = Database::app()->prepare(
            'SELECT l.id, l.parent_id, l.name, l.kind, l.note, l.image_path, ll.code
             FROM bb_location l
             LEFT JOIN bb_location_label ll ON ll.location_id = l.id
             WHERE l.owner_id = ?
             ORDER BY l.name'
        );
        $stmt->execute([$ownerId]);
        return $stmt->fetchAll();
    }

    /** Direkte Unter-Äste eines Ortes inkl. Code/Bild (für die Detailansicht). */
    public function childrenOf(int $parentId): array
    {
        $stmt = Database::app()->prepare(
            'SELECT l.id, l.name, l.kind, l.image_path, ll.code
             FROM bb_location l
             LEFT JOIN bb_location_label ll ON ll.location_id = l.id
             WHERE l.parent_id = ?
             ORDER BY COALESCE(ll.code, l.name)'
        );
        $stmt->execute([$parentId]);
        return $stmt->fetchAll();
    }

    /** Einfachen Ast (kein etikettierter Behälter) anlegen; gibt id zurück. */
    public function createPlace(int $ownerId, ?int $parentId, string $name, string $kind, ?string $note): int
    {
        $db = Database::app();
        $db->prepare(
            'INSERT INTO bb_location (owner_id, parent_id, name, kind, note) VALUES (?,?,?,?,?)'
        )->execute([$ownerId, $parentId, $name, $kind, $note]);
        return (int) $db->lastInsertId();
    }

    /**
     * Ast im Baum verschieben (frei, aber zyklensicher).
     * $newParentId === null hängt den Ast direkt an den Konto-Root.
     * @return array{ok:bool,message:string}
     */
    public function moveBranch(int $locationId, ?int $newParentId): array
    {
        if ($newParentId !== null) {
            if ($newParentId === $locationId) {
                return ['ok' => false, 'message' => 'Ein Ast kann nicht in sich selbst verschoben werden.'];
            }
            if (in_array($newParentId, $this->descendantIds($locationId), true)) {
                return ['ok' => false, 'message' => 'Der Zielast liegt im eigenen Teilbaum.'];
            }
        }
        Database::app()->prepare('UPDATE bb_location SET parent_id = ? WHERE id = ?')
            ->execute([$newParentId, $locationId]);
        return ['ok' => true, 'message' => 'Ast verschoben.'];
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
