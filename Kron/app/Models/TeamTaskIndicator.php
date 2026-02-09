<?php

namespace App\Models;

use App\Core\Database;

class TeamTaskIndicator
{
    public static function countCriticalNotFinishedByTeam(): array
    {
        $sql = "SELECT tm.team_id, COUNT(*) AS criticas_no_terminadas
                FROM kron_tasks k
                JOIN kron_team_members tm ON tm.user_id = k.user_id
                WHERE k.prioridad = 'critica' AND k.estado <> 'terminada'
                GROUP BY tm.team_id";
        $config = require __DIR__ . '/../../config/database.php';
        $stmt = Database::connection($config)->prepare($sql);
        $stmt->execute();
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int)$row['team_id']] = (int)$row['criticas_no_terminadas'];
        }
        return $result;
    }
}
