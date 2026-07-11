<?php
namespace App\Model\Repository;

use App\Core\Database;

/**
 * Projekte. Ein Projekt ist ein bb_owner vom Typ 'projekt' und wurzelt – wie
 * ein Konto – einen eigenen Lagerbaum (bb_location.owner_id). Die gesamte
 * Standort-/Behälter-Logik wird unverändert wiederverwendet.
 */
class ProjectRepository
{
    /** Alle Projekte mit Anzahl der Standorte/Äste (für die Übersicht). */
    public function all(): array
    {
        return Database::app()->query(
            "SELECT o.id, o.name, o.note, o.wcf_user_id, o.created_at,
                    (SELECT COUNT(*) FROM bb_location l WHERE l.owner_id = o.id) AS location_count
             FROM bb_owner o
             WHERE o.type = 'projekt'
             ORDER BY o.name"
        )->fetchAll();
    }

    /** Ein Projekt (oder null, wenn es kein Projekt ist). */
    public function find(int $id): ?array
    {
        $stmt = Database::app()->prepare(
            "SELECT id, name, note, wcf_user_id, created_at
             FROM bb_owner WHERE id = ? AND type = 'projekt' LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Neues Projekt anlegen; gibt die owner_id (= Baumwurzel) zurück. */
    public function create(string $name, ?string $note, int $leadWcfUserId): int
    {
        $db = Database::app();
        $db->prepare(
            "INSERT INTO bb_owner (type, wcf_user_id, name, note) VALUES ('projekt', ?, ?, ?)"
        )->execute([$leadWcfUserId, $name, $note]);
        return (int) $db->lastInsertId();
    }
}
