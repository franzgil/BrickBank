<?php
namespace App\Model\Repository;

use App\Core\Database;

class OwnerRepository
{
    public function all(): array
    {
        return Database::app()
            ->query('SELECT id, type, name FROM bb_owner ORDER BY type, name')
            ->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::app()->prepare('SELECT id, type, name, wcf_user_id FROM bb_owner WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Der Vereins-Besitzer (erster gefundener). */
    public function verein(): ?array
    {
        $row = Database::app()
            ->query("SELECT id, type, name FROM bb_owner WHERE type = 'verein' ORDER BY id LIMIT 1")
            ->fetch();
        return $row ?: null;
    }

    /** Privat-Besitzer eines WSC-Mitglieds finden oder anlegen; gibt id zurück. */
    public function findOrCreateForWcfUser(int $wcfUserId, string $name, ?string $email): int
    {
        $db = Database::app();
        $stmt = $db->prepare("SELECT id FROM bb_owner WHERE type = 'privat' AND wcf_user_id = ? LIMIT 1");
        $stmt->execute([$wcfUserId]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }
        $db->prepare("INSERT INTO bb_owner (type, wcf_user_id, name, email) VALUES ('privat', ?, ?, ?)")
            ->execute([$wcfUserId, $name, $email]);
        return (int) $db->lastInsertId();
    }
}
