<?php

namespace App\Models;

use App\Core\Database;

class Role
{
    private static function db()
    {
        $config = require __DIR__ . '/../../config/database.php';
        return Database::connection($config);
    }

    public static function all(): array
    {
        return self::db()->query('SELECT * FROM kron_roles ORDER BY nombre')->fetchAll();
    }

    public static function create(array $data): void
    {
        $stmt = self::db()->prepare('INSERT INTO kron_roles (nombre, descripcion) VALUES (:nombre, :descripcion)');
        $stmt->execute([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'],
        ]);
    }

    public static function update(int $id, array $data): void
    {
        $stmt = self::db()->prepare('UPDATE kron_roles SET nombre = :nombre, descripcion = :descripcion WHERE id = :id');
        $stmt->execute([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'],
            'id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM kron_roles WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function assignToUser(int $userId, int $roleId): void
    {
        $stmt = self::db()->prepare('DELETE FROM kron_user_roles WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        $stmt = self::db()->prepare('INSERT INTO kron_user_roles (user_id, role_id) VALUES (:user_id, :role_id)');
        $stmt->execute([
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);
    }

    public static function userHasRole(int $userId, string $roleName): bool
    {
        $stmt = self::db()->prepare('SELECT 1 FROM kron_user_roles ur JOIN kron_roles r ON r.id = ur.role_id WHERE ur.user_id = :user_id AND r.nombre = :nombre LIMIT 1');
        $stmt->execute([
            'user_id' => $userId,
            'nombre' => $roleName,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public static function getUserRoleName(int $userId): ?string
    {
        $stmt = self::db()->prepare('SELECT r.nombre FROM kron_user_roles ur JOIN kron_roles r ON r.id = ur.role_id WHERE ur.user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $role = $stmt->fetchColumn();

        return $role ? (string) $role : null;
    }

    public static function isRoleInUse(int $roleId): bool
    {
        $stmt = self::db()->prepare('SELECT 1 FROM kron_user_roles WHERE role_id = :role_id LIMIT 1');
        $stmt->execute(['role_id' => $roleId]);

        return (bool) $stmt->fetchColumn();
    }
}
