<?php
namespace App\Service;

use App\Core\Database;
use App\Model\Repository\HoldingRepository;
use App\Model\Repository\StockMovementRepository;

/**
 * Bucht Bestandsänderungen und protokolliert jede als Bewegung – beides in
 * einer Transaktion. Einzige Stelle, an der sich Mengen ändern.
 */
class StockService
{
    /** @var HoldingRepository */
    private $holdings;
    /** @var StockMovementRepository */
    private $movements;

    public function __construct()
    {
        $this->holdings  = new HoldingRepository();
        $this->movements = new StockMovementRepository();
    }

    /** Zugang: Menge an einem Ort erfassen. */
    public function add(int $itemId, int $locationId, int $ownerId, string $cond, string $visibility, int $qty, ?int $wcfUserId, ?string $note): void
    {
        $this->assertQty($qty);
        $db = Database::app();
        $db->beginTransaction();
        try {
            $this->holdings->applyDelta($itemId, $locationId, $ownerId, $cond, $visibility, $qty);
            $this->movements->log($itemId, $ownerId, $cond, null, $locationId, $qty, 'zugang', $wcfUserId, $note);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** Entnahme: Menge von einem Ort abbuchen (nicht mehr als vorhanden). */
    public function remove(int $itemId, int $locationId, int $ownerId, string $cond, int $qty, ?int $wcfUserId, ?string $note): void
    {
        $this->assertQty($qty);
        $current = $this->holdings->findExact($itemId, $locationId, $ownerId, $cond);
        if ($current === null || (int) $current['quantity'] < $qty) {
            throw new \RuntimeException('Nicht genug Bestand für die Entnahme.');
        }
        $db = Database::app();
        $db->beginTransaction();
        try {
            $this->holdings->applyDelta($itemId, $locationId, $ownerId, $cond, $current['visibility'], -$qty);
            $this->movements->log($itemId, $ownerId, $cond, $locationId, null, $qty, 'entnahme', $wcfUserId, $note);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** Umbuchung: Menge von einem Ort an einen anderen verschieben. */
    public function move(int $itemId, int $fromLocationId, int $toLocationId, int $ownerId, string $cond, int $qty, ?int $wcfUserId, ?string $note): void
    {
        $this->assertQty($qty);
        if ($fromLocationId === $toLocationId) {
            throw new \RuntimeException('Quell- und Zielort sind identisch.');
        }
        $from = $this->holdings->findExact($itemId, $fromLocationId, $ownerId, $cond);
        if ($from === null || (int) $from['quantity'] < $qty) {
            throw new \RuntimeException('Nicht genug Bestand am Quellort.');
        }
        $db = Database::app();
        $db->beginTransaction();
        try {
            $this->holdings->applyDelta($itemId, $fromLocationId, $ownerId, $cond, $from['visibility'], -$qty);
            $this->holdings->applyDelta($itemId, $toLocationId, $ownerId, $cond, $from['visibility'], $qty);
            $this->movements->log($itemId, $ownerId, $cond, $fromLocationId, $toLocationId, $qty, 'umbuchung', $wcfUserId, $note);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Umklassifizierung am selben Ort: Menge von einer Position (Item/Zustand)
     * auf eine andere (andere Farbe → anderes Item und/oder anderer Zustand)
     * umbuchen. Wird als zwei 'korrektur'-Bewegungen protokolliert.
     */
    public function reclassify(int $fromItemId, int $toItemId, int $locationId, int $ownerId, string $fromCond, string $toCond, int $qty, ?int $wcfUserId, ?string $note): void
    {
        $this->assertQty($qty);
        if ($fromItemId === $toItemId && $fromCond === $toCond) {
            throw new \RuntimeException('Farbe und Zustand sind unverändert.');
        }
        $from = $this->holdings->findExact($fromItemId, $locationId, $ownerId, $fromCond);
        if ($from === null || (int) $from['quantity'] < $qty) {
            throw new \RuntimeException('Nicht genug Bestand für die Änderung.');
        }
        $visibility = $from['visibility'];
        $db = Database::app();
        $db->beginTransaction();
        try {
            $this->holdings->applyDelta($fromItemId, $locationId, $ownerId, $fromCond, $visibility, -$qty);
            $this->holdings->applyDelta($toItemId, $locationId, $ownerId, $toCond, $visibility, $qty);
            $this->movements->log($fromItemId, $ownerId, $fromCond, $locationId, null, $qty, 'korrektur', $wcfUserId, $note);
            $this->movements->log($toItemId, $ownerId, $toCond, null, $locationId, $qty, 'korrektur', $wcfUserId, $note);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private function assertQty(int $qty): void
    {
        if ($qty < 1) {
            throw new \RuntimeException('Menge muss mindestens 1 sein.');
        }
    }
}
