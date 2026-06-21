<?php
namespace App\Model\Repository;

use App\Core\Database;
use PDO;

/**
 * Inventurläufe, Scans und Soll-Ist-Abgleich.
 *  - Soll = etikettierte Behälter im Bereich (root_location_id rekursiv,
 *           optional auf owner gefiltert).
 *  - Ist  = distinct gescannte Codes des Laufs.
 */
class StocktakeRepository
{
    /** @var LocationRepository */
    private $locations;

    public function __construct()
    {
        $this->locations = new LocationRepository();
    }

    public function create(string $title, ?int $rootLocationId, ?int $ownerId, ?int $wcfUserId): int
    {
        $db = Database::app();
        $stmt = $db->prepare(
            'INSERT INTO bb_stocktake (title, root_location_id, owner_id, wcf_user_id) VALUES (?,?,?,?)'
        );
        $stmt->execute([$title, $rootLocationId, $ownerId, $wcfUserId]);
        return (int) $db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::app()->prepare(
            'SELECT id, title, root_location_id, owner_id, wcf_user_id, started_at, finished_at
             FROM bb_stocktake WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function all(): array
    {
        return Database::app()->query(
            'SELECT s.id, s.title, s.started_at, s.finished_at,
                    (SELECT COUNT(*) FROM bb_stocktake_scan ss WHERE ss.stocktake_id = s.id) AS scan_count
             FROM bb_stocktake s ORDER BY s.started_at DESC'
        )->fetchAll();
    }

    public function addScan(int $stocktakeId, string $code): void
    {
        $stmt = Database::app()->prepare(
            'INSERT INTO bb_stocktake_scan (stocktake_id, code) VALUES (?,?)'
        );
        $stmt->execute([$stocktakeId, $code]);
    }

    public function finish(int $id): void
    {
        $stmt = Database::app()->prepare(
            'UPDATE bb_stocktake SET finished_at = CURRENT_TIMESTAMP WHERE id = ? AND finished_at IS NULL'
        );
        $stmt->execute([$id]);
    }

    public function scanCount(int $id): int
    {
        $stmt = Database::app()->prepare('SELECT COUNT(*) FROM bb_stocktake_scan WHERE stocktake_id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }

    public function recentScans(int $id, int $limit = 25): array
    {
        $limit = max(1, min(200, $limit));
        $stmt = Database::app()->prepare(
            'SELECT code, scanned_at FROM bb_stocktake_scan WHERE stocktake_id = ? ORDER BY id DESC LIMIT ' . $limit
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    /** Soll-Codes (Behälter im Bereich), als [code => name]. */
    private function sollCodes(array $stocktake): array
    {
        $where  = [];
        $params = [];

        if (!empty($stocktake['root_location_id'])) {
            $ids = $this->locations->descendantIds((int) $stocktake['root_location_id']);
            if (empty($ids)) {
                return [];
            }
            $where[]  = 'l.id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
            $params   = array_merge($params, $ids);
        }
        if (!empty($stocktake['owner_id'])) {
            $where[]  = 'll.owner_id = ?';
            $params[] = (int) $stocktake['owner_id'];
        }

        $sql = 'SELECT ll.code, l.name
                FROM bb_location_label ll
                JOIN bb_location l ON l.id = ll.location_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = Database::app()->prepare($sql);
        $stmt->execute($params);

        $map = [];
        foreach ($stmt->fetchAll() as $r) {
            $map[$r['code']] = $r['name'];
        }
        return $map;
    }

    /** Distinct gescannte Codes (Ist). */
    private function istCodes(int $id): array
    {
        $stmt = Database::app()->prepare('SELECT DISTINCT code FROM bb_stocktake_scan WHERE stocktake_id = ?');
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Soll-Ist-Auswertung.
     * @return array{found:array,missing:array,unexpected:array,counts:array}
     */
    public function reconcile(array $stocktake): array
    {
        $soll   = $this->sollCodes($stocktake);          // code => name
        $ist    = $this->istCodes((int) $stocktake['id']); // [code, …]
        $istSet = array_flip($ist);

        $found = [];
        $missing = [];
        foreach ($soll as $code => $name) {
            if (isset($istSet[$code])) {
                $found[$code] = $name;
            } else {
                $missing[$code] = $name;
            }
        }
        $unexpected = [];
        foreach ($ist as $code) {
            if (!isset($soll[$code])) {
                $unexpected[] = $code;
            }
        }

        return [
            'found'      => $found,
            'missing'    => $missing,
            'unexpected' => $unexpected,
            'counts'     => [
                'soll'       => count($soll),
                'found'      => count($found),
                'missing'    => count($missing),
                'unexpected' => count($unexpected),
            ],
        ];
    }
}
