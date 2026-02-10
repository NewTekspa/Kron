<?php
namespace App\Models;

use App\Core\Database;
use App\Models\Team;

class Task
{
    /**
     * Elimina un registro de bitácora (log) por su ID
     */
    public static function deleteLog(int $logId): bool
    {
        $stmt = self::db()->prepare('DELETE FROM kron_task_logs WHERE id = :id');
        return $stmt->execute(['id' => $logId]);
    }
    public static function getTitleById(int $taskId): ?string
    {
        $stmt = self::db()->prepare('SELECT titulo FROM kron_tasks WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $taskId]);
        $row = $stmt->fetch();
        return $row ? $row['titulo'] : null;
    }

    private static function db()
    {
        $config = require __DIR__ . '/../../config/database.php';
        return Database::connection($config);
    }

    public static function allForUser(int $userId, string $roleName): array
    {
        if ($roleName === 'administrador') {
            $rows = self::allWithAssignee();
            return self::applyOverdueStatus($rows);
        }

        $ids = Team::visibleUserIdsForRole($userId, $roleName);
        $rows = self::allWithAssigneeByIds($ids);
        return self::applyOverdueStatus($rows);
    }

    public static function allForUserByCategory(int $userId, string $roleName, int $categoryId): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        if ($roleName === 'administrador') {
            $rows = self::allWithAssigneeByCategory($categoryId);
            return self::applyOverdueStatus($rows);
        }

        $ids = Team::visibleUserIdsForRole($userId, $roleName);
        $rows = self::allWithAssigneeByIdsAndCategory($ids, $categoryId);
        return self::applyOverdueStatus($rows);
    }

    public static function allForUserIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $rows = self::allWithAssigneeByIds($userIds);
        return self::applyOverdueStatus($rows);
    }

    public static function allForUserIdsByCategory(array $userIds, int $categoryId): array
    {
        if ($categoryId <= 0 || empty($userIds)) {
            return [];
        }

        $rows = self::allWithAssigneeByIdsAndCategory($userIds, $categoryId);
        return self::applyOverdueStatus($rows);
    }

    public static function openTasksForUsers(?array $userIds, int $limit = 12): array
    {
        if (is_array($userIds) && empty($userIds)) {
            return [];
        }

        $sql = "SELECT t.id, t.titulo, t.estado, t.prioridad, t.fecha_compromiso,
            COALESCE(u.nombre, 'Sin asignar') AS asignado_nombre,
            cat.nombre AS categoria_nombre,
            cls.nombre AS clasificacion_nombre,
            (SELECT COUNT(*) FROM kron_task_times tt WHERE tt.task_id = t.id) AS time_count
            FROM kron_tasks t
            LEFT JOIN kron_users u ON u.id = t.user_id
            LEFT JOIN kron_task_categories cat ON cat.id = t.category_id
            LEFT JOIN kron_task_classifications cls ON cls.id = cat.classification_id
            WHERE t.estado <> 'terminada'";
        $params = [];
        if (is_array($userIds)) {
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $sql .= ' AND t.user_id IN (' . $placeholders . ')';
            $params = array_values($userIds);
        }
        $sql .= ' ORDER BY t.id DESC
                  LIMIT ' . (int) $limit;

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return $rows;
    }

    public static function countsByUserIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql = 'SELECT t.user_id,
                COUNT(*) AS total,
                SUM(CASE WHEN t.estado = "terminada" THEN 1 ELSE 0 END) AS terminadas
                FROM kron_tasks t
                WHERE t.user_id IN (' . $placeholders . ')
                GROUP BY t.user_id';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_values($userIds));
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['user_id']] = [
                'total' => (int) $row['total'],
                'terminadas' => (int) $row['terminadas'],
            ];
        }

        return $result;
    }

    public static function hoursByUserIdsByDate(array $userIds, string $sinceDate): array
    {
        if (empty($userIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql = 'SELECT t.user_id,
                tt.fecha,
                SUM(tt.horas) AS horas
                FROM kron_task_times tt
                JOIN kron_tasks t ON t.id = tt.task_id
                WHERE t.user_id IN (' . $placeholders . ')
                AND tt.fecha >= ?
                GROUP BY t.user_id, tt.fecha
                ORDER BY tt.fecha ASC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_merge(array_values($userIds), [$sinceDate]));
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $userId = (int) $row['user_id'];
            $date = $row['fecha'];
            $result[$userId][$date] = (float) $row['horas'];
        }

        return $result;
    }

    public static function hoursByUserIdsByMonth(array $userIds, string $sinceDate): array
    {
        if (empty($userIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql = 'SELECT t.user_id,
                DATE_FORMAT(tt.fecha, "%Y-%m-01") AS mes,
                SUM(tt.horas) AS horas
                FROM kron_task_times tt
                JOIN kron_tasks t ON t.id = tt.task_id
                WHERE t.user_id IN (' . $placeholders . ')
                AND tt.fecha >= ?
                GROUP BY t.user_id, mes
                ORDER BY mes ASC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_merge(array_values($userIds), [$sinceDate]));
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $userId = (int) $row['user_id'];
            $month = $row['mes'];
            $result[$userId][$month] = (float) $row['horas'];
        }

        return $result;
    }

    public static function hoursTotalsByUserIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql = 'SELECT t.user_id,
                COALESCE(SUM(tt.horas), 0) AS horas
                FROM kron_tasks t
                LEFT JOIN kron_task_times tt ON tt.task_id = t.id
                WHERE t.user_id IN (' . $placeholders . ')
                GROUP BY t.user_id';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_values($userIds));
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['user_id']] = (float) $row['horas'];
        }

        return $result;
    }

    public static function countsByUserIdsInRange(array $userIds, string $startDate, string $endDate): array
    {
        if (empty($userIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql = 'SELECT t.user_id,
                COUNT(*) AS total
                FROM kron_tasks t
                WHERE t.user_id IN (' . $placeholders . ')
                AND DATE(t.created_at) BETWEEN ? AND ?
                GROUP BY t.user_id';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_merge(array_values($userIds), [$startDate, $endDate]));
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['user_id']] = (int) $row['total'];
        }

        return $result;
    }

    public static function completedCountsByUserIdsInRange(array $userIds, string $startDate, string $endDate): array
    {
        if (empty($userIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql = 'SELECT t.user_id,
                COUNT(*) AS total
                FROM kron_tasks t
                WHERE t.user_id IN (' . $placeholders . ')
                AND t.estado = "terminada"
                AND DATE(t.created_at) BETWEEN ? AND ?
                GROUP BY t.user_id';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_merge(array_values($userIds), [$startDate, $endDate]));
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['user_id']] = (int) $row['total'];
        }

        return $result;
    }

    private static function columnExists(string $table, string $column): bool
    {
        try {
            $stmt = self::db()->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function countsByUserIdsByMonth(array $userIds, string $sinceDate): array
    {
        if (empty($userIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql = 'SELECT t.user_id,
                DATE_FORMAT(t.created_at, "%Y-%m-01") AS mes,
                COUNT(*) AS total
                FROM kron_tasks t
                WHERE t.user_id IN (' . $placeholders . ')
                AND DATE(t.created_at) >= ?
                GROUP BY t.user_id, mes
                ORDER BY mes ASC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_merge(array_values($userIds), [$sinceDate]));
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $userId = (int) $row['user_id'];
            $month = $row['mes'];
            $result[$userId][$month] = (int) $row['total'];
        }

        return $result;
    }

    public static function hoursTotalsByUserIdsInRange(array $userIds, string $startDate, string $endDate): array
    {
        if (empty($userIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql = 'SELECT t.user_id,
                COALESCE(SUM(tt.horas), 0) AS horas
                FROM kron_tasks t
                LEFT JOIN kron_task_times tt ON tt.task_id = t.id
                AND tt.fecha BETWEEN ? AND ?
                WHERE t.user_id IN (' . $placeholders . ')
                GROUP BY t.user_id';
        $stmt = self::db()->prepare($sql);
        $params = array_merge([$startDate, $endDate], array_values($userIds));
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['user_id']] = (float) $row['horas'];
        }

        return $result;
    }

    public static function countsByStateAndUserIdsInRange(array $userIds, string $estado, string $startDate, string $endDate): array
    {
        if (empty($userIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql = 'SELECT t.user_id,
                COUNT(*) AS total
                FROM kron_tasks t
                WHERE t.user_id IN (' . $placeholders . ')
                AND t.estado = ?
                AND DATE(t.created_at) BETWEEN ? AND ?
                GROUP BY t.user_id';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_merge(array_values($userIds), [$estado, $startDate, $endDate]));
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['user_id']] = (int) $row['total'];
        }

        return $result;
    }

    public static function findWithDetails(int $id): ?array
    {
        $sql = 'SELECT t.*, t.fecha_termino_real,
            (SELECT COALESCE(SUM(tt.horas), 0) FROM kron_task_times tt WHERE tt.task_id = t.id) AS total_horas,
            COALESCE(u.nombre, \'Sin asignar\') AS asignado_nombre, COALESCE(u.email, \'\') AS asignado_email,
            cat.nombre AS categoria_nombre,
            cls.nombre AS clasificacion_nombre,
            eq.nombre AS equipo_nombre,
            c.nombre AS creador_nombre
            FROM kron_tasks t
            LEFT JOIN kron_users u ON u.id = t.user_id
            JOIN kron_users c ON c.id = t.created_by
            LEFT JOIN kron_task_categories cat ON cat.id = t.category_id
            LEFT JOIN kron_task_classifications cls ON cls.id = cat.classification_id
            LEFT JOIN kron_teams eq ON eq.id = cat.team_id
            WHERE t.id = :id
            LIMIT 1';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (! $row) {
            return null;
        }

        $rows = self::applyOverdueStatus([$row]);
        return $rows[0] ?? null;
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare('INSERT INTO kron_tasks (category_id, user_id, created_by, titulo, fecha_compromiso, prioridad, estado)
            VALUES (:category_id, :user_id, :created_by, :titulo, :fecha_compromiso, :prioridad, :estado)');
        $stmt->execute([
            'category_id' => $data['category_id'],
            'user_id' => $data['user_id'],
            'created_by' => $data['created_by'],
            'titulo' => $data['titulo'],
            'fecha_compromiso' => $data['fecha_compromiso'],
            'prioridad' => $data['prioridad'],
            'estado' => $data['estado'],
        ]);

        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        // Si el estado cambia a terminada y no hay fecha_termino_real, registrarla
        $fechaTerminoReal = $data['fecha_termino_real'] ?? null;
        if ($data['estado'] === 'terminada' && !$fechaTerminoReal) {
            $fechaTerminoReal = date('Y-m-d H:i:s');
        }
        
        $stmt = self::db()->prepare('UPDATE kron_tasks
            SET category_id = :category_id,
                user_id = :user_id,
                titulo = :titulo,
                fecha_compromiso = :fecha_compromiso,
                prioridad = :prioridad,
                estado = :estado,
                fecha_termino_real = :fecha_termino_real
            WHERE id = :id');
        $stmt->execute([
            'category_id' => $data['category_id'],
            'user_id' => $data['user_id'],
            'titulo' => $data['titulo'],
            'fecha_compromiso' => $data['fecha_compromiso'],
            'prioridad' => $data['prioridad'],
            'estado' => $data['estado'],
            'fecha_termino_real' => $fechaTerminoReal,
            'id' => $id,
        ]);
    }

    public static function updateStatus(int $id, string $estado, ?string $fechaTermino = null): void
    {
        if ($estado === 'terminada' && $fechaTermino) {
            $stmt = self::db()->prepare('UPDATE kron_tasks SET estado = :estado, fecha_termino_real = :fecha_termino_real WHERE id = :id');
            $stmt->execute([
                'estado' => $estado,
                'fecha_termino_real' => $fechaTermino . ' ' . date('H:i:s'),
                'id' => $id,
            ]);
        } else {
            $stmt = self::db()->prepare('UPDATE kron_tasks SET estado = :estado WHERE id = :id');
            $stmt->execute([
                'estado' => $estado,
                'id' => $id,
            ]);
        }
    }

    public static function addLog(int $taskId, int $userId, string $contenido): void
    {
        $stmt = self::db()->prepare('INSERT INTO kron_task_logs (task_id, user_id, contenido) VALUES (:task_id, :user_id, :contenido)');
        $stmt->execute([
            'task_id' => $taskId,
            'user_id' => $userId,
            'contenido' => $contenido,
        ]);
    }

    public static function logs(int $taskId): array
    {
        $sql = 'SELECT l.*, u.nombre AS autor_nombre
                FROM kron_task_logs l
                JOIN kron_users u ON u.id = l.user_id
                WHERE l.task_id = :task_id
                ORDER BY l.created_at DESC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['task_id' => $taskId]);

        return $stmt->fetchAll();
    }

    public static function addTimeEntry(int $taskId, string $fecha, float $horas): void
    {
        $stmt = self::db()->prepare('INSERT INTO kron_task_times (task_id, fecha, horas) VALUES (:task_id, :fecha, :horas)');
        $stmt->execute([
            'task_id' => $taskId,
            'fecha' => $fecha,
            'horas' => $horas,
        ]);
    }

    public static function timeEntries(int $taskId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM kron_task_times WHERE task_id = :task_id ORDER BY fecha DESC');
        $stmt->execute(['task_id' => $taskId]);

        return $stmt->fetchAll();
    }

    public static function timeEntryById(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM kron_task_times WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function timeEntryExists(int $taskId, string $fecha): bool
    {
        $stmt = self::db()->prepare('SELECT 1 FROM kron_task_times WHERE task_id = :task_id AND fecha = :fecha LIMIT 1');
        $stmt->execute([
            'task_id' => $taskId,
            'fecha' => $fecha,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public static function deleteTimeEntry(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM kron_task_times WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function deleteById(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM kron_tasks WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function allWithAssignee(): array
    {
        $sql = 'SELECT t.*, COALESCE(u.nombre, \'Sin asignar\') AS asignado_nombre, u.email AS asignado_email,
                cat.nombre AS categoria_nombre,
                (SELECT COUNT(*) FROM kron_task_times tt WHERE tt.task_id = t.id) AS time_count,
                (SELECT COALESCE(SUM(tt.horas), 0) FROM kron_task_times tt WHERE tt.task_id = t.id) AS total_horas
                FROM kron_tasks t
                JOIN kron_users u ON u.id = t.user_id
                LEFT JOIN kron_task_categories cat ON cat.id = t.category_id
                ORDER BY t.fecha_compromiso ASC, t.id DESC';
        return self::db()->query($sql)->fetchAll();
    }

    private static function allWithAssigneeByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT t.*, u.nombre AS asignado_nombre, u.email AS asignado_email,
                cat.nombre AS categoria_nombre,
                (SELECT COUNT(*) FROM kron_task_times tt WHERE tt.task_id = t.id) AS time_count,
                (SELECT COALESCE(SUM(tt.horas), 0) FROM kron_task_times tt WHERE tt.task_id = t.id) AS total_horas
                FROM kron_tasks t
                LEFT JOIN kron_users u ON u.id = t.user_id
                LEFT JOIN kron_task_categories cat ON cat.id = t.category_id
                WHERE (t.user_id IN (' . $placeholders . ') OR t.user_id IS NULL OR t.user_id = 0)
                ORDER BY t.id DESC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_values($ids));

        return $stmt->fetchAll();
    }

    private static function allWithAssigneeByCategory(int $categoryId): array
    {
        $sql = 'SELECT t.*, u.nombre AS asignado_nombre, u.email AS asignado_email,
                cat.nombre AS categoria_nombre,
                (SELECT COUNT(*) FROM kron_task_times tt WHERE tt.task_id = t.id) AS time_count,
                (SELECT COALESCE(SUM(tt.horas), 0) FROM kron_task_times tt WHERE tt.task_id = t.id) AS total_horas
                FROM kron_tasks t
                JOIN kron_users u ON u.id = t.user_id
                LEFT JOIN kron_task_categories cat ON cat.id = t.category_id
                WHERE t.category_id = :category_id
                ORDER BY t.id DESC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(['category_id' => $categoryId]);

        return $stmt->fetchAll();
    }

    private static function allWithAssigneeByIdsAndCategory(array $ids, int $categoryId): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT t.id, t.titulo, t.estado, t.prioridad, t.fecha_compromiso, t.user_id,
            COALESCE(u.nombre, "Sin asignar") AS asignado_nombre, 
            COALESCE(u.email, "") AS asignado_email,
            cat.nombre AS categoria_nombre,
            (SELECT COUNT(*) FROM kron_task_times tt WHERE tt.task_id = t.id) AS time_count,
            (SELECT COALESCE(SUM(tt.horas), 0) FROM kron_task_times tt WHERE tt.task_id = t.id) AS total_horas
            FROM kron_tasks t
            LEFT JOIN kron_users u ON u.id = t.user_id
            LEFT JOIN kron_task_categories cat ON cat.id = t.category_id
            WHERE t.category_id = ?
            AND t.user_id IN (' . $placeholders . ')
            ORDER BY t.id DESC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(array_merge([$categoryId], array_values($ids)));

        return $stmt->fetchAll();
    }

    public static function hasTimeEntries(int $taskId): bool
    {
        $stmt = self::db()->prepare('SELECT 1 FROM kron_task_times WHERE task_id = :task_id LIMIT 1');
        $stmt->execute(['task_id' => $taskId]);

        return (bool) $stmt->fetchColumn();
    }

    public static function copyFromCategory(int $fromCategoryId, int $toCategoryId, int $createdBy, ?array $userIds = null): int
    {
        $sql = 'SELECT user_id, titulo, prioridad
            FROM kron_tasks
            WHERE category_id = ?';
        $params = [$fromCategoryId];
        if (is_array($userIds) && ! empty($userIds)) {
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $sql .= ' AND user_id IN (' . $placeholders . ')';
            $params = array_merge($params, array_values($userIds));
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            return 0;
        }

        $insert = self::db()->prepare('INSERT INTO kron_tasks (category_id, user_id, created_by, titulo, prioridad, estado)
            VALUES (:category_id, :user_id, :created_by, :titulo, :prioridad, "pendiente")');

        $count = 0;
        foreach ($rows as $row) {
            $insert->execute([
                'category_id' => $toCategoryId,
                'user_id' => $row['user_id'],
                'created_by' => $createdBy,
                'titulo' => $row['titulo'],
                'prioridad' => $row['prioridad'],
            ]);
            $count += 1;
        }

        return $count;
    }

    /**
     * Elimina todas las tareas asociadas a una categoría (actividad)
     */
    public static function deleteByCategoryId(int $categoryId): void
    {
        // Primero obtener todas las tareas de la categoría
        $stmt = self::db()->prepare('SELECT id FROM kron_tasks WHERE category_id = :category_id');
        $stmt->execute(['category_id' => $categoryId]);
        $taskIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        if (!empty($taskIds)) {
            $placeholders = implode(',', array_fill(0, count($taskIds), '?'));
            
            // Eliminar registros de tiempo
            $stmt = self::db()->prepare('DELETE FROM kron_task_times WHERE task_id IN (' . $placeholders . ')');
            $stmt->execute($taskIds);
            
            // Eliminar logs
            $stmt = self::db()->prepare('DELETE FROM kron_task_logs WHERE task_id IN (' . $placeholders . ')');
            $stmt->execute($taskIds);
        }
        
        // Finalmente eliminar las tareas
        $stmt = self::db()->prepare('DELETE FROM kron_tasks WHERE category_id = :category_id');
        $stmt->execute(['category_id' => $categoryId]);
    }

    private static function applyOverdueStatus(array $rows): array
    {
        // Método deshabilitado temporalmente - requiere columnas fecha_compromiso y fecha_termino_real
        return $rows;
    }
}
