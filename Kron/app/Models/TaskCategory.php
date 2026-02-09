<?php

namespace App\Models;

use App\Core\Database;

class TaskCategory
{
    /**
     * Clona una actividad (categoría) con sus tareas y colaboradores, sin copiar fechas ni horas
     * @param int $fromCategoryId ID de la actividad origen
     * @param string $newName Nombre de la nueva actividad
     * @param int $createdBy Usuario que realiza el clonado
     * @return int|null ID de la nueva actividad creada, o null en error
     */
    public static function cloneWithTasksAndMembers(int $fromCategoryId, string $newName, int $createdBy): ?int
    {
        // 1. Obtener la actividad original
        $original = self::findById($fromCategoryId);
        if (!$original) return null;

        // 2. Crear la nueva actividad (misma clasificación y equipo, nuevo nombre)
        $stmt = self::db()->prepare('INSERT INTO kron_task_categories (nombre, created_by, classification_id, team_id) VALUES (:nombre, :created_by, :classification_id, :team_id)');
        $stmt->execute([
            'nombre' => $newName,
            'created_by' => $createdBy,
            'classification_id' => $original['classification_id'] ?? null,
            'team_id' => $original['team_id'] ?? null,
        ]);
        $newCategoryId = (int)self::db()->lastInsertId();

        // 3. Clonar tareas (sin fechas ni horas)
        $taskStmt = self::db()->prepare('SELECT user_id, titulo, prioridad FROM kron_tasks WHERE category_id = :catid');
        $taskStmt->execute(['catid' => $fromCategoryId]);
        $tasks = $taskStmt->fetchAll();
        if ($tasks) {
            $insertTask = self::db()->prepare('INSERT INTO kron_tasks (category_id, user_id, created_by, titulo, prioridad, estado) VALUES (:category_id, :user_id, :created_by, :titulo, :prioridad, "pendiente")');
            foreach ($tasks as $t) {
                $insertTask->execute([
                    'category_id' => $newCategoryId,
                    'user_id' => $t['user_id'],
                    'created_by' => $createdBy,
                    'titulo' => $t['titulo'],
                    'prioridad' => $t['prioridad'],
                ]);
            }
        }

        // 4. Clonar colaboradores (miembros del equipo, si aplica)
        if (!empty($original['team_id'])) {
            $memberStmt = self::db()->prepare('SELECT user_id FROM kron_team_members WHERE team_id = :team_id');
            $memberStmt->execute(['team_id' => $original['team_id']]);
            $members = $memberStmt->fetchAll();
            if ($members) {
                $insertMember = self::db()->prepare('INSERT IGNORE INTO kron_team_members (team_id, user_id) VALUES (:team_id, :user_id)');
                foreach ($members as $m) {
                    $insertMember->execute([
                        'team_id' => $original['team_id'],
                        'user_id' => $m['user_id'],
                    ]);
                }
            }
        }

        return $newCategoryId;
    }
    /**
     * Obtiene todas las actividades donde el usuario participa (por tareas asignadas)
     */
    public static function allForUser(int $userId): array
    {
        // Obtener equipos del usuario
        $teamIds = [];
        $stmt = self::db()->prepare('SELECT team_id FROM kron_team_members WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        foreach ($stmt->fetchAll() as $row) {
            $teamIds[] = (int)$row['team_id'];
        }
        $teamIds = array_unique($teamIds);
        $where = 'c.team_id IS NULL';
        $params = [];
        if (!empty($teamIds)) {
            $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
            $where = '(c.team_id IN (' . $placeholders . ') OR c.created_by = ? OR c.team_id IS NULL)';
            $params = array_merge($teamIds, [$userId]);
        } else {
            $where = 'c.created_by = ? OR c.team_id IS NULL';
            $params = [$userId];
        }
        $sql = 'SELECT c.id, c.nombre, c.classification_id, cl.nombre AS clasificacion_nombre,
                   (SELECT COUNT(*) FROM kron_tasks t WHERE t.category_id = c.id) AS total_tareas,
                   (SELECT COUNT(*) FROM kron_tasks t WHERE t.category_id = c.id AND t.estado != "terminada") AS tareas_abiertas,
                   (SELECT COUNT(*) FROM kron_tasks t WHERE t.category_id = c.id AND t.estado = "terminada") AS tareas_terminadas,
                   CASE WHEN (SELECT COUNT(*) FROM kron_tasks t WHERE t.category_id = c.id AND t.estado != "terminada") > 0 THEN "Abierta" ELSE "Cerrada" END AS estado_actividad
            FROM kron_task_categories c
            LEFT JOIN kron_task_classifications cl ON cl.id = c.classification_id
            WHERE ' . $where . '
            ORDER BY c.nombre';
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
        $stmt = self::db()->prepare('SELECT * FROM kron_task_categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findByName(string $nombre): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM kron_task_categories WHERE nombre = :nombre LIMIT 1');
        $stmt->execute(['nombre' => $nombre]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findOrCreate(string $nombre, int $createdBy): int
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return 0;
        }

        $existing = self::findByName($nombre);
        if ($existing) {
            return (int) $existing['id'];
        }

        $stmt = self::db()->prepare('INSERT INTO kron_task_categories (nombre, created_by) VALUES (:nombre, :created_by)');
        $stmt->execute([
            'nombre' => $nombre,
            'created_by' => $createdBy,
        ]);

        return (int) self::db()->lastInsertId();
    }

    public static function createWithClassification(string $nombre, int $createdBy, ?int $classificationId): int
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return 0;
        }

        $existing = self::findByName($nombre);
        if ($existing) {
            return (int) $existing['id'];
        }

        $teamId = isset($GLOBALS['team_id']) ? (int)$GLOBALS['team_id'] : null;
        $stmt = self::db()->prepare('INSERT INTO kron_task_categories (nombre, created_by, classification_id, team_id)
            VALUES (:nombre, :created_by, :classification_id, :team_id)');
        $stmt->execute([
            'nombre' => $nombre,
            'created_by' => $createdBy,
            'classification_id' => $classificationId ?: null,
            'team_id' => $teamId ?: null,
        ]);

        return (int) self::db()->lastInsertId();
    }

    public static function searchByName(string $term, int $limit = 15): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $stmt = self::db()->prepare('SELECT id, nombre FROM kron_task_categories WHERE nombre LIKE :like ORDER BY nombre LIMIT :limit');
        $stmt->bindValue(':like', '%' . $term . '%');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function allWithCounts(?array $userIds = null): array
    {
        $where = '';
        $params = [];
        $repeatCount = 0;
        if (is_array($userIds) && ! empty($userIds)) {
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $where = ' AND (t.user_id IN (' . $placeholders . ') OR t.user_id IS NULL OR t.user_id = 0)';
            $params = array_values($userIds);
            $repeatCount = 7;
        }

        $sql = 'SELECT c.id, c.nombre, c.classification_id, cl.nombre AS clasificacion_nombre, c.team_id, t.nombre AS equipo_nombre,
                (SELECT COUNT(*) FROM kron_tasks t WHERE t.category_id = c.id' . $where . ') AS tareas,
                (SELECT COUNT(*) FROM kron_tasks t WHERE t.category_id = c.id AND t.estado = "pendiente"' . $where . ') AS pendientes,
                (SELECT COUNT(*) FROM kron_tasks t WHERE t.category_id = c.id AND t.estado = "en_curso"' . $where . ') AS en_curso,
                (SELECT COUNT(*) FROM kron_tasks t WHERE t.category_id = c.id AND t.estado = "atrasada"' . $where . ') AS atrasadas,
                (SELECT COUNT(*) FROM kron_tasks t WHERE t.category_id = c.id AND t.estado = "terminada"' . $where . ') AS terminadas,
                (SELECT COUNT(*) FROM kron_tasks t WHERE t.category_id = c.id AND t.fecha_compromiso IS NULL' . $where . ') AS sin_fecha,
                (SELECT COALESCE(SUM(tt.horas), 0)
                 FROM kron_tasks t
                 LEFT JOIN kron_task_times tt ON tt.task_id = t.id
                 WHERE t.category_id = c.id' . $where . ') AS horas_total
                FROM kron_task_categories c
                LEFT JOIN kron_task_classifications cl ON cl.id = c.classification_id
                LEFT JOIN kron_teams t ON t.id = c.team_id
                ORDER BY c.nombre';
        $stmt = self::db()->prepare($sql);
        if ($repeatCount > 1) {
            $finalParams = [];
            for ($i = 0; $i < $repeatCount; $i++) {
                $finalParams = array_merge($finalParams, $params);
            }
            $stmt->execute($finalParams);
        } else {
            $stmt->execute($params);
        }

        return $stmt->fetchAll();
    }

    public static function updateName(int $id, string $nombre): void
    {
        $stmt = self::db()->prepare('UPDATE kron_task_categories SET nombre = :nombre WHERE id = :id');
        $stmt->execute([
            'nombre' => $nombre,
            'id' => $id,
        ]);
    }

    public static function updateDetails(int $id, string $nombre, ?int $classificationId): void
    {
        $stmt = self::db()->prepare('UPDATE kron_task_categories
            SET nombre = :nombre,
                classification_id = :classification_id
            WHERE id = :id');
        $stmt->execute([
            'nombre' => $nombre,
            'classification_id' => $classificationId ?: null,
            'id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        // Eliminar tareas asociadas a la categoría antes de eliminar la categoría
        \App\Models\Task::deleteByCategoryId($id);
        $stmt = self::db()->prepare('DELETE FROM kron_task_categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Cambia el estado manualmente de una categoría (actividad) entre activa y terminada
     */
    public static function toggleStatus(int $id, string $estadoActual): void
    {
        // El esquema actual no incluye la columna 'estado_manual'. Esta operación es un no-op hasta
        // que se agregue soporte en la base de datos.
        return;
    }
}
