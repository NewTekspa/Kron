<?php

namespace App\Models;

use App\Core\Database;

class TaskIndicator
{
    public static function countCriticalNotFinished(?array $userIds = null): int
    {
        $sql = "SELECT COUNT(*) FROM kron_tasks WHERE prioridad = 'critica' AND estado <> 'terminada'";
        $params = [];
        if (is_array($userIds) && !empty($userIds)) {
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $sql .= ' AND user_id IN (' . $placeholders . ')';
            $params = array_values($userIds);
        }
        $config = require __DIR__ . '/../../config/database.php';
        $stmt = Database::connection($config)->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
