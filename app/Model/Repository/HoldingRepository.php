<?php
namespace App\Model\Repository;

use App\Core\Database;

/**
 * Bestand (bb_holding). Mengen werden ausschließlich über den StockService
 * geändert (der zugleich eine Bewegung protokolliert). Lesezugriffe
 * berücksichtigen die Sichtbarkeit (privat/intern/verein).
 */
class HoldingRepository
{
    /** Sichtbarkeits-Bedingung: nur eigener privat-Bestand, sonst intern/verein. */
    private function visClause(string $h = 'h', string $o = 'o'): string
    {
        return '(' . $h . ".visibility <> 'privat' OR " . $o . '.wcf_user_id = ?)';
    }

    public function findExact(int $itemId, int $locationId, int $ownerId, string $cond): ?array
    {
        $stmt = Database::app()->prepare(
            'SELECT id, quantity, visibility FROM bb_holding
             WHERE item_id = ? AND location_id = ? AND owner_id = ? AND cond = ? LIMIT 1'
        );
        $stmt->execute([$itemId, $locationId, $ownerId, $cond]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Saldo einer Position um $delta verändern (legt sie bei Bedarf an, nie < 0).
     * Visibility wird nur beim Anlegen gesetzt, nicht bei Folgebuchungen.
     * Gibt die neue Menge zurück. NUR aus dem StockService aufrufen.
     */
    public function applyDelta(int $itemId, int $locationId, int $ownerId, string $cond, string $visibility, int $delta): int
    {
        $db = Database::app();
        $db->prepare(
            'INSERT INTO bb_holding (item_id, location_id, owner_id, cond, visibility, quantity)
             VALUES (?,?,?,?,?, GREATEST(?,0))
             ON DUPLICATE KEY UPDATE quantity = GREATEST(quantity + ?, 0)'
        )->execute([$itemId, $locationId, $ownerId, $cond, $visibility, $delta, $delta]);

        $stmt = $db->prepare(
            'SELECT quantity FROM bb_holding
             WHERE item_id = ? AND location_id = ? AND owner_id = ? AND cond = ? LIMIT 1'
        );
        $stmt->execute([$itemId, $locationId, $ownerId, $cond]);
        return (int) $stmt->fetchColumn();
    }

    /** Setzt die Sichtbarkeit einer Position. */
    public function setVisibility(int $holdingId, string $visibility): void
    {
        Database::app()->prepare('UPDATE bb_holding SET visibility = ? WHERE id = ?')
            ->execute([$visibility, $holdingId]);
    }

    /** Inhalt eines Ortes (sichtbar für $viewer), inkl. Item-/Besitzerdaten. */
    public function contentsOfLocation(int $locationId, ?int $viewer): array
    {
        $stmt = Database::app()->prepare(
            "SELECT h.id, h.quantity, h.cond, h.visibility,
                    o.id AS owner_id, o.name AS owner_name, o.type AS owner_type,
                    o.wcf_user_id AS owner_wcf_user_id,
                    i.id AS item_id, i.type AS item_type, i.part_num, i.color_id,
                    rp.name AS part_name, rc.name AS color_name,
                    (SELECT re.element_id FROM rb_elements re
                      WHERE i.type = 'element' AND re.part_num = i.part_num
                            AND re.color_id = i.color_id
                      ORDER BY re.element_id LIMIT 1) AS element_id
             FROM bb_holding h
             JOIN bb_item  i ON i.id = h.item_id
             JOIN bb_owner o ON o.id = h.owner_id
             LEFT JOIN rb_parts  rp ON i.type = 'element' AND rp.part_num = i.part_num
             LEFT JOIN rb_colors rc ON i.type = 'element' AND rc.id = i.color_id
             WHERE h.location_id = ? AND " . $this->visClause() . "
             ORDER BY rp.name, rc.name, h.cond"
        );
        $stmt->execute([$locationId, $viewer]);
        return $stmt->fetchAll();
    }

    /** Wo liegt ein Item (sichtbar für $viewer)? Inkl. Schlüssel für Buchungen. */
    public function locationsForItem(int $itemId, ?int $viewer): array
    {
        $stmt = Database::app()->prepare(
            'SELECT h.id AS holding_id, h.quantity, h.cond, h.visibility,
                    h.owner_id, o.name AS owner_name, o.type AS owner_type, o.wcf_user_id,
                    l.id AS location_id, l.name AS location_name, ll.code AS location_code
             FROM bb_holding h
             JOIN bb_owner o ON o.id = h.owner_id
             JOIN bb_location l ON l.id = h.location_id
             LEFT JOIN bb_location_label ll ON ll.location_id = l.id
             WHERE h.item_id = ? AND ' . $this->visClause() . '
             ORDER BY ll.code, l.name, h.cond'
        );
        $stmt->execute([$itemId, $viewer]);
        return $stmt->fetchAll();
    }

    /** Items mit (sichtbarem) Bestand suchen – globale Bestandssuche. */
    public function searchItemsWithStock(string $q, ?int $viewer, int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        $esc         = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
        $like        = '%' . $esc . '%';
        $likeNoSpace = '%' . str_replace(' ', '', $esc) . '%';
        $stmt = Database::app()->prepare(
            "SELECT i.id AS item_id, i.type AS item_type, i.part_num,
                    rp.name AS part_name, rc.name AS color_name,
                    COALESCE(SUM(h.quantity),0) AS on_hand
             FROM bb_holding h
             JOIN bb_item  i ON i.id = h.item_id
             JOIN bb_owner o ON o.id = h.owner_id
             LEFT JOIN rb_parts  rp ON i.type = 'element' AND rp.part_num = i.part_num
             LEFT JOIN rb_colors rc ON i.type = 'element' AND rc.id = i.color_id
             WHERE " . $this->visClause() . "
                   AND (i.part_num LIKE ? OR REPLACE(rp.name,' ','') LIKE ?)
             GROUP BY i.id
             ORDER BY rp.name, rc.name
             LIMIT " . $limit
        );
        $stmt->execute([$viewer, $like, $likeNoSpace]);
        return $stmt->fetchAll();
    }

    /** Sichtbarer Gesamtbestand eines Items. */
    public function onHandByItem(int $itemId, ?int $viewer): int
    {
        $stmt = Database::app()->prepare(
            'SELECT COALESCE(SUM(h.quantity),0)
             FROM bb_holding h JOIN bb_owner o ON o.id = h.owner_id
             WHERE h.item_id = ? AND ' . $this->visClause() . ''
        );
        $stmt->execute([$itemId, $viewer]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Verfügbarer Bestand = Gesamtbestand − reserviert.
     * Reservierungen (Projekte/Verleih) kommen in späteren Phasen; aktuell 0.
     */
    public function availableByItem(int $itemId, ?int $viewer): int
    {
        return $this->onHandByItem($itemId, $viewer); // − reserviert (Phase C/D)
    }

    /**
     * „Konten" (Besitzer), zu deren Bestand der Betrachter Zugang hat,
     * mit Kennzahlen – für das Dashboard.
     */
    public function accountsForViewer(?int $viewer): array
    {
        $stmt = Database::app()->prepare(
            'SELECT o.id, o.name, o.type, o.wcf_user_id,
                    COUNT(*) AS positions,
                    COALESCE(SUM(h.quantity),0) AS total_qty
             FROM bb_holding h
             JOIN bb_owner o ON o.id = h.owner_id
             WHERE o.type <> \'projekt\' AND ' . $this->visClause() . '
             GROUP BY o.id
             ORDER BY (o.type = \'verein\') DESC, (o.wcf_user_id = ?) DESC, o.name'
        );
        $stmt->execute([$viewer, $viewer]);
        return $stmt->fetchAll();
    }

    /** Sichtbare Bestands-Summen je Ast eines Konto-Baums: location_id → [cnt, qty]. */
    public function summaryByLocationForOwnerTree(int $accountOwnerId, ?int $viewer): array
    {
        $stmt = Database::app()->prepare(
            'SELECT h.location_id, COUNT(*) AS cnt, COALESCE(SUM(h.quantity),0) AS qty
             FROM bb_holding h
             JOIN bb_owner o    ON o.id = h.owner_id
             JOIN bb_location l ON l.id = h.location_id
             WHERE l.owner_id = ? AND ' . $this->visClause() . '
             GROUP BY h.location_id'
        );
        $stmt->execute([$accountOwnerId, $viewer]);
        $map = [];
        foreach ($stmt->fetchAll() as $r) {
            $map[(int) $r['location_id']] = ['cnt' => (int) $r['cnt'], 'qty' => (int) $r['qty']];
        }
        return $map;
    }

    /** Sichtbare Bestände eines Besitzers (für die Konto-Detailseite). */
    public function holdingsForOwner(int $ownerId, ?int $viewer): array
    {
        $stmt = Database::app()->prepare(
            "SELECT h.id, h.quantity, h.cond, h.visibility,
                    i.id AS item_id, i.type AS item_type, i.part_num,
                    rp.name AS part_name, rc.name AS color_name,
                    l.name AS location_name, ll.code AS location_code
             FROM bb_holding h
             JOIN bb_item  i ON i.id = h.item_id
             JOIN bb_owner o ON o.id = h.owner_id
             JOIN bb_location l ON l.id = h.location_id
             LEFT JOIN bb_location_label ll ON ll.location_id = l.id
             LEFT JOIN rb_parts  rp ON i.type = 'element' AND rp.part_num = i.part_num
             LEFT JOIN rb_colors rc ON i.type = 'element' AND rc.id = i.color_id
             WHERE h.owner_id = ? AND " . $this->visClause() . "
             ORDER BY rp.name, rc.name, h.cond"
        );
        $stmt->execute([$ownerId, $viewer]);
        return $stmt->fetchAll();
    }
}
