<?php

namespace App\Models;

use App\Core\Database;

class Team
{
    private static function db()
    {
        $config = require __DIR__ . '/../../config/database.php';
        return Database::connection($config);
    }

    public static function all(): array
    {
        $sql = 'SELECT t.*, s.nombre AS subgerente_nombre, j.nombre AS jefe_nombre,
                (SELECT COUNT(*) FROM kron_team_members tm WHERE tm.team_id = t.id) AS colaboradores
                FROM kron_teams t
                JOIN kron_users s ON s.id = t.subgerente_id
                JOIN kron_users j ON j.id = t.jefe_id
                ORDER BY t.nombre';
        return self::db()->query($sql)->fetchAll();
    }

    public static function visibleTeamsForRole(int $userId, string $roleName, bool $isAdmin = false): array
    {
        if ($isAdmin || $roleName === 'administrador') {
            $sql = 'SELECT id, nombre, jefe_id, subgerente_id
                    FROM kron_teams
                    ORDER BY nombre';
            return self::db()->query($sql)->fetchAll();
        }

        if ($roleName === 'jefe') {
            $stmt = self::db()->prepare('SELECT id, nombre, jefe_id, subgerente_id
                    FROM kron_teams
                    WHERE jefe_id = :jefe_id
                    ORDER BY nombre');
            $stmt->execute(['jefe_id' => $userId]);
            return $stmt->fetchAll();
        }

        if ($roleName === 'subgerente') {
            $stmt = self::db()->prepare('SELECT id, nombre, jefe_id, subgerente_id
                    FROM kron_teams
                    WHERE subgerente_id = :subgerente_id
                    ORDER BY nombre');
            $stmt->execute(['subgerente_id' => $userId]);
            return $stmt->fetchAll();
        }

        $stmt = self::db()->prepare('SELECT t.id, t.nombre, t.jefe_id, t.subgerente_id
                FROM kron_teams t
                JOIN kron_team_members tm ON tm.team_id = t.id
                WHERE tm.user_id = :user_id
                ORDER BY t.nombre');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function create(string $nombre, int $subgerenteId, int $jefeId): void
    {
        $stmt = self::db()->prepare('INSERT INTO kron_teams (nombre, subgerente_id, jefe_id) VALUES (:nombre, :subgerente_id, :jefe_id)');
        $stmt->execute([
            'nombre' => $nombre,
            'subgerente_id' => $subgerenteId,
            'jefe_id' => $jefeId,
        ]);
    }

    public static function exists(int $id): bool
    {
        $stmt = self::db()->prepare('SELECT 1 FROM kron_teams WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return (bool) $stmt->fetchColumn();
    }

    public static function searchByName(string $term, int $limit = 15): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $stmt = self::db()->prepare('SELECT id, nombre FROM kron_teams WHERE nombre LIKE :like ORDER BY nombre LIMIT :limit');
        $stmt->bindValue(':like', '%' . $term . '%');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function assignMember(int $teamId, int $userId): void
    {
        $stmt = self::db()->prepare('DELETE FROM kron_team_members WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        $stmt = self::db()->prepare('INSERT INTO kron_team_members (team_id, user_id) VALUES (:team_id, :user_id)');
        $stmt->execute([
            'team_id' => $teamId,
            'user_id' => $userId,
        ]);
    }

    public static function membersForTeams(array $teamIds): array
    {
        if (empty($teamIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
        $sql = 'SELECT tm.team_id, u.id, u.nombre, u.email
                FROM kron_team_members tm
                JOIN kron_users u ON u.id = tm.user_id
                WHERE tm.team_id IN (' . $placeholders . ')
                ORDER BY u.nombre';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_values($teamIds));
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $teamId = (int) $row['team_id'];
            if (! isset($grouped[$teamId])) {
                $grouped[$teamId] = [];
            }
            $grouped[$teamId][] = $row;
        }

        return $grouped;
    }

    public static function findWithLeaders(int $id): ?array
    {
        $sql = 'SELECT t.*, s.nombre AS subgerente_nombre, s.email AS subgerente_email,
                j.nombre AS jefe_nombre, j.email AS jefe_email
                FROM kron_teams t
                JOIN kron_users s ON s.id = t.subgerente_id
                JOIN kron_users j ON j.id = t.jefe_id
                WHERE t.id = :id
                LIMIT 1';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function members(int $teamId): array
    {
        $sql = 'SELECT u.id, u.nombre, u.email, r.nombre AS rol_nombre
                FROM kron_team_members tm
                JOIN kron_users u ON u.id = tm.user_id
                LEFT JOIN kron_user_roles ur ON ur.user_id = u.id
                LEFT JOIN kron_roles r ON r.id = ur.role_id
                WHERE tm.team_id = :team_id
                ORDER BY u.nombre';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['team_id' => $teamId]);

        return $stmt->fetchAll();
    }

    public static function removeMember(int $teamId, int $userId): void
    {
        $stmt = self::db()->prepare('DELETE FROM kron_team_members WHERE team_id = :team_id AND user_id = :user_id');
        $stmt->execute([
            'team_id' => $teamId,
            'user_id' => $userId,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM kron_teams WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function visibleUserIdsForRole(int $userId, string $roleName): array
    {
        $ids = [$userId];

        if ($roleName === 'jefe') {
            $stmt = self::db()->prepare('SELECT tm.user_id
                FROM kron_teams t
                JOIN kron_team_members tm ON tm.team_id = t.id
                WHERE t.jefe_id = :jefe_id');
            $stmt->execute(['jefe_id' => $userId]);
            $rows = $stmt->fetchAll();
            $ids = array_merge($ids, array_map('intval', array_column($rows, 'user_id')));
        }

        if ($roleName === 'subgerente') {
            $stmt = self::db()->prepare('SELECT t.jefe_id AS user_id
                FROM kron_teams t
                WHERE t.subgerente_id = :subgerente_id');
            $stmt->execute(['subgerente_id' => $userId]);
            $rows = $stmt->fetchAll();
            $ids = array_merge($ids, array_map('intval', array_column($rows, 'user_id')));

            $stmt = self::db()->prepare('SELECT tm.user_id
                FROM kron_teams t
                JOIN kron_team_members tm ON tm.team_id = t.id
                WHERE t.subgerente_id = :subgerente_id');
            $stmt->execute(['subgerente_id' => $userId]);
            $rows = $stmt->fetchAll();
            $ids = array_merge($ids, array_map('intval', array_column($rows, 'user_id')));
        }

        $ids = array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));

        return $ids;
    }
}
