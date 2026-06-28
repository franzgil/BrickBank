<?php
namespace App\Model\Repository;

use App\Core\Database;
use App\Service\CodeGenerator;
use PDO;

/**
 * Behälter-CRUD und Etiketten-/Code-Vergabe.
 * Ein Behälter ist eine `location` (kind = container|karton|tuete) plus eine
 * 1:1-Zeile in `location_label` mit ortsneutralem Code.
 */
class LabelRepository
{
    /**
     * Legt einen Behälter an (location + label) und vergibt den nächsten Code.
     * Läuft in einer Transaktion. Gibt id und code zurück.
     */
    public function createContainer(string $kind, string $name, ?int $parentId, ?int $ownerId, ?string $note): array
    {
        if (!CodeGenerator::isContainerKind($kind)) {
            throw new \InvalidArgumentException('Ungültiger Behältertyp: ' . $kind);
        }

        $db = Database::app();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('INSERT INTO bb_location (parent_id, name, kind, note) VALUES (?,?,?,?)');
            $stmt->execute([$parentId, $name, $kind, $note]);
            $locationId = (int) $db->lastInsertId();

            $seq  = $this->nextSeq($db, $kind);
            $code = CodeGenerator::format($kind, $seq);

            $stmt = $db->prepare(
                'INSERT INTO bb_location_label (location_id, code, code_seq, owner_id) VALUES (?,?,?,?)'
            );
            $stmt->execute([$locationId, $code, $seq, $ownerId]);

            $db->commit();
            return ['id' => $locationId, 'code' => $code, 'kind' => $kind, 'name' => $name];
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** Nächste laufende Nummer je Typ; sperrt die betroffenen Zeilen (FOR UPDATE). */
    private function nextSeq(PDO $db, string $kind): int
    {
        $stmt = $db->prepare(
            'SELECT COALESCE(MAX(ll.code_seq),0)+1
             FROM bb_location_label ll
             JOIN bb_location l ON l.id = ll.location_id
             WHERE l.kind = ? FOR UPDATE'
        );
        $stmt->execute([$kind]);
        return (int) $stmt->fetchColumn();
    }

    /** Behälterliste, optional auf einen Typ gefiltert. */
    public function listContainers(?string $kind = null): array
    {
        $sql = 'SELECT l.id, l.name, l.kind, l.parent_id,
                       ll.code, ll.owner_id,
                       p.name AS parent_name, pll.code AS parent_code,
                       o.name AS owner_name
                FROM bb_location_label ll
                JOIN bb_location l        ON l.id = ll.location_id
                LEFT JOIN bb_location p   ON p.id = l.parent_id
                LEFT JOIN bb_location_label pll ON pll.location_id = p.id
                LEFT JOIN bb_owner o      ON o.id = ll.owner_id';
        $params = [];
        if ($kind !== null) {
            $sql .= ' WHERE l.kind = ?';
            $params[] = $kind;
        }
        $sql .= ' ORDER BY l.kind, ll.code';
        $stmt = Database::app()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Ein Behälter (location + label + übergeordneter Ort) oder null. */
    public function find(int $id): ?array
    {
        $stmt = Database::app()->prepare(
            'SELECT l.id, l.name, l.kind, l.parent_id, l.note,
                    ll.code, ll.code_seq, ll.owner_id, ll.rfid_epc,
                    p.name AS parent_name, p.kind AS parent_kind, pll.code AS parent_code
             FROM bb_location l
             JOIN bb_location_label ll ON ll.location_id = l.id
             LEFT JOIN bb_location p   ON p.id = l.parent_id
             LEFT JOIN bb_location_label pll ON pll.location_id = p.id
             WHERE l.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Behälter per (ortsneutralem) Code finden. */
    public function findByCode(string $code): ?array
    {
        $stmt = Database::app()->prepare(
            'SELECT l.id, l.name, l.kind, l.parent_id, ll.code, ll.owner_id
             FROM bb_location_label ll
             JOIN bb_location l ON l.id = ll.location_id
             WHERE ll.code = ? LIMIT 1'
        );
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Inhalt eines Behälters (Bestandsposten je Element).
     * Teil-/Farbnamen kommen aus dem Rebrickable-Katalog (rb_parts/rb_colors).
     */
    public function contents(int $locationId): array
    {
        $stmt = Database::app()->prepare(
            'SELECT e.part_num AS part_no, rp.name AS part_name, rc.name AS color_name, ii.quantity
             FROM bb_inventory_item ii
             JOIN bb_element e ON e.id = ii.element_id
             JOIN rb_parts rp  ON rp.part_num = e.part_num
             JOIN rb_colors rc ON rc.id = e.color_id
             WHERE ii.location_id = ?
             ORDER BY rp.name, rc.name'
        );
        $stmt->execute([$locationId]);
        return $stmt->fetchAll();
    }

    /** Alle etikettierten Behälter (für Etikettenbogen/CSV). */
    public function allLabels(): array
    {
        return Database::app()->query(
            'SELECT l.id, l.name, l.kind, ll.code,
                    p.name AS parent_name, pll.code AS parent_code
             FROM bb_location_label ll
             JOIN bb_location l      ON l.id = ll.location_id
             LEFT JOIN bb_location p ON p.id = l.parent_id
             LEFT JOIN bb_location_label pll ON pll.location_id = p.id
             ORDER BY l.kind, ll.code'
        )->fetchAll();
    }
}
