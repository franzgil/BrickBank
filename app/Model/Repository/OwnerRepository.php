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
        $stmt = Database::app()->prepare('SELECT id, type, name FROM bb_owner WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
