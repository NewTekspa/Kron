<?php

namespace App\Models;

use App\Core\Database;
use App\Models\Team;

class User
{
    // Eliminar registro de horas por fecha y tarea
    public static function deleteHourEntry(int $taskId, string $fecha): void
    {
        $stmt = self::db()->prepare('DELETE FROM kron_task_times WHERE task_id = :task_id AND fecha = :fecha');
        $stmt->execute(['task_id' => $taskId, 'fecha' => $fecha]);
    }
    // --- HORAS TRABAJADAS ---
    public static function hasHourEntry(int $userId, string $fecha, ?int $taskId = null): bool
    {
        if ($taskId === null) {
            return false; // Si no hay tarea, no se puede validar
        }
        $sql = 'SELECT 1 FROM kron_task_times WHERE task_id = :task_id AND fecha = :fecha LIMIT 1';
        $params = ['task_id' => $taskId, 'fecha' => $fecha];
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    public static function addHourEntry(int $userId, string $fecha, string $horas, ?int $taskId = null): void
    {
        if ($taskId === null) {
            throw new \Exception('Se requiere un task_id para registrar horas.');
        }
        // Convertir hh:mm a decimal
        $horasDecimal = $horas;
        if (strpos($horas, ':') !== false) {
            list($h, $m) = explode(':', $horas);
            $horasDecimal = (int)$h + ((int)$m / 60);
        }
        $stmt = self::db()->prepare('INSERT INTO kron_task_times (task_id, fecha, horas) VALUES (:task_id, :fecha, :horas)');
        $stmt->execute(['task_id' => $taskId, 'fecha' => $fecha, 'horas' => $horasDecimal]);
    }

    public static function getHourEntries(int $userId, ?int $taskId = null): array
    {
        if ($taskId === null) {
            return []; // Si no hay tarea, no hay registros
        }
        $sql = 'SELECT fecha, horas FROM kron_task_times WHERE task_id = :task_id ORDER BY fecha DESC';
        $params = ['task_id' => $taskId];
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    private static function db()
    {
        $config = require __DIR__ . '/../../config/database.php';
        return Database::connection($config);
    }

    public static function findById(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM kron_users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findWithRole(int $id): ?array
    {
        $sql = 'SELECT u.*, ur.role_id AS rol_id
            FROM kron_users u
            LEFT JOIN kron_user_roles ur ON ur.user_id = u.id
            WHERE u.id = :id
            LIMIT 1';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM kron_users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function emailExists(string $email, ?int $ignoreId = null): bool
    {
        if ($ignoreId) {
            $stmt = self::db()->prepare('SELECT 1 FROM kron_users WHERE email = :email AND id <> :id LIMIT 1');
            $stmt->execute(['email' => $email, 'id' => $ignoreId]);
        } else {
            $stmt = self::db()->prepare('SELECT 1 FROM kron_users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
        }

        return (bool) $stmt->fetchColumn();
    }

    public static function allWithRole(): array
    {
        $sql = 'SELECT u.*, r.nombre AS rol_nombre
                FROM kron_users u
                LEFT JOIN kron_user_roles ur ON ur.user_id = u.id
                LEFT JOIN kron_roles r ON r.id = ur.role_id
                ORDER BY u.nombre';
        return self::db()->query($sql)->fetchAll();
    }

    public static function allWithRoleByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT u.*, r.nombre AS rol_nombre
                FROM kron_users u
                LEFT JOIN kron_user_roles ur ON ur.user_id = u.id
                LEFT JOIN kron_roles r ON r.id = ur.role_id
                WHERE u.id IN (' . $placeholders . ')
                ORDER BY u.nombre';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_values($ids));

        return $stmt->fetchAll();
    }

    public static function allWithRoleVisibleTo(int $userId, string $roleName): array
    {
        if ($roleName === 'administrador') {
            return self::allWithRole();
        }

        $ids = Team::visibleUserIdsForRole($userId, $roleName);
        return self::allWithRoleByIds($ids);
    }

    public static function searchForTeam(string $term, array $roleNames = [], int $limit = 15): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $like = '%' . $term . '%';
        $params = [
            'like' => $like,
            'limit' => $limit,
        ];

        $roleFilter = '';
        if (! empty($roleNames)) {
            $placeholders = [];
            foreach (array_values($roleNames) as $index => $roleName) {
                $key = 'role_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $roleName;
            }
            $roleFilter = ' AND r.nombre IN (' . implode(',', $placeholders) . ')';
        }

        $sql = 'SELECT u.id, u.nombre, u.email, r.nombre AS rol_nombre
                FROM kron_users u
                LEFT JOIN kron_user_roles ur ON ur.user_id = u.id
                LEFT JOIN kron_roles r ON r.id = ur.role_id
                WHERE u.estado = "activo" AND (u.nombre LIKE :like OR u.email LIKE :like)'
                . $roleFilter . '
                ORDER BY u.nombre
                LIMIT :limit';
        $stmt = self::db()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, $key === 'limit' ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function searchByIds(string $term, array $ids, int $limit = 15): array
    {
        $term = trim($term);
        if ($term === '' || empty($ids)) {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $like = '%' . $term . '%';
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT u.id, u.nombre, u.email, r.nombre AS rol_nombre
                FROM kron_users u
                LEFT JOIN kron_user_roles ur ON ur.user_id = u.id
                LEFT JOIN kron_roles r ON r.id = ur.role_id
                WHERE u.id IN (' . $placeholders . ')
                  AND u.estado = "activo"
                  AND (u.nombre LIKE ? OR u.email LIKE ?)
                ORDER BY u.nombre
                LIMIT ' . (int) $limit;
        $stmt = self::db()->prepare($sql);
        $params = array_values($ids);
        $params[] = $like;
        $params[] = $like;
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare('INSERT INTO kron_users (nombre, email, estado, fecha_ingreso, password_hash) VALUES (:nombre, :email, :estado, :fecha_ingreso, :password_hash)');
        $stmt->execute([
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'estado' => $data['estado'],
            'fecha_ingreso' => $data['fecha_ingreso'],
            'password_hash' => $data['password_hash'],
        ]);

        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = self::db()->prepare('UPDATE kron_users SET nombre = :nombre, email = :email, estado = :estado, fecha_ingreso = :fecha_ingreso, password_hash = :password_hash WHERE id = :id');
        $stmt->execute([
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'estado' => $data['estado'],
            'fecha_ingreso' => $data['fecha_ingreso'],
            'password_hash' => $data['password_hash'],
            'id' => $id,
        ]);
    }

    public static function updateWithoutPassword(int $id, array $data): void
    {
        $stmt = self::db()->prepare('UPDATE kron_users SET nombre = :nombre, email = :email, estado = :estado, fecha_ingreso = :fecha_ingreso WHERE id = :id');
        $stmt->execute([
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'estado' => $data['estado'],
            'fecha_ingreso' => $data['fecha_ingreso'],
            'id' => $id,
        ]);
    }

    public static function deactivate(int $id): void
    {
        $stmt = self::db()->prepare('UPDATE kron_users SET estado = "inactivo" WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function activate(int $id): void
    {
        $stmt = self::db()->prepare('UPDATE kron_users SET estado = "activo" WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM kron_users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
