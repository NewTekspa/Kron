<?php

namespace App\Models;

use App\Core\Database;

class UserRelation
{
    private static function db()
    {
        $config = require __DIR__ . '/../../config/database.php';
        return Database::connection($config);
    }

    public static function all(): array
    {
        $sql = 'SELECT ur.id, s.nombre AS supervisor, sub.nombre AS subordinado
                FROM kron_user_relations ur
                JOIN kron_users s ON s.id = ur.supervisor_id
                JOIN kron_users sub ON sub.id = ur.subordinado_id
                ORDER BY ur.created_at DESC';
        return self::db()->query($sql)->fetchAll();
    }

    public static function assign(int $supervisorId, int $subordinadoId): void
    {
        $stmt = self::db()->prepare('DELETE FROM kron_user_relations WHERE subordinado_id = :subordinado_id');
        $stmt->execute(['subordinado_id' => $subordinadoId]);

        $stmt = self::db()->prepare('INSERT INTO kron_user_relations (supervisor_id, subordinado_id) VALUES (:supervisor_id, :subordinado_id)');
        $stmt->execute([
            'supervisor_id' => $supervisorId,
            'subordinado_id' => $subordinadoId,
        ]);
    }

    public static function subordinateIds(int $supervisorId): array
    {
        $stmt = self::db()->prepare('SELECT subordinado_id FROM kron_user_relations WHERE supervisor_id = :supervisor_id');
        $stmt->execute(['supervisor_id' => $supervisorId]);
        $rows = $stmt->fetchAll();

        return array_map('intval', array_column($rows, 'subordinado_id'));
    }

    public static function subordinateIdsTwoLevels(int $supervisorId): array
    {
        $sql = 'SELECT ur2.subordinado_id
                FROM kron_user_relations ur1
                JOIN kron_user_relations ur2 ON ur2.supervisor_id = ur1.subordinado_id
                WHERE ur1.supervisor_id = :supervisor_id';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['supervisor_id' => $supervisorId]);
        $rows = $stmt->fetchAll();

        return array_map('intval', array_column($rows, 'subordinado_id'));
    }

    public static function visibleUserIdsForRole(int $userId, string $roleName): array
    {
        $ids = [$userId];

        if ($roleName === 'jefe') {
            $ids = array_merge($ids, self::subordinateIds($userId));
        }

        if ($roleName === 'subgerente') {
            $direct = self::subordinateIds($userId);
            $second = self::subordinateIdsTwoLevels($userId);
            $ids = array_merge($ids, $direct, $second);
        }

        $ids = array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));

        return $ids;
    }

    public static function delete(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM kron_user_relations WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
