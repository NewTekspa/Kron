<?php

namespace App\Models;

use App\Core\Database;

class TaskClassification
{
    private static function db()
    {
        $config = require __DIR__ . '/../../config/database.php';
        return Database::connection($config);
    }

    public static function all(): array
    {
        return self::db()->query('SELECT * FROM kron_task_classifications ORDER BY nombre')->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = self::db()->prepare('SELECT * FROM kron_task_classifications WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(string $nombre): int
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return 0;
        }

        $stmt = self::db()->prepare('INSERT INTO kron_task_classifications (nombre) VALUES (:nombre)');
        $stmt->execute(['nombre' => $nombre]);

        return (int) self::db()->lastInsertId();
    }

    public static function updateName(int $id, string $nombre): void
    {
        $nombre = trim($nombre);
        if ($id <= 0 || $nombre === '') {
            return;
        }

        $stmt = self::db()->prepare('UPDATE kron_task_classifications SET nombre = :nombre WHERE id = :id');
        $stmt->execute([
            'nombre' => $nombre,
            'id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        $stmt = self::db()->prepare('DELETE FROM kron_task_classifications WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function findOrCreateByName(string $nombre): ?int
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return null;
        }
        $stmt = self::db()->prepare('SELECT id FROM kron_task_classifications WHERE nombre = :nombre LIMIT 1');
        $stmt->execute(['nombre' => $nombre]);
        $row = $stmt->fetch();
        if ($row && isset($row['id'])) {
            return (int)$row['id'];
        }
        return self::create($nombre);
    }
}
