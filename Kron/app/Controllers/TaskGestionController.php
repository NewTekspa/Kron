<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\TaskIndicator;

class TaskGestionController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        // Restricción de acceso solo para Jefes y Subgerentes en "Gestión"
        $user = Auth::user();
        $roleName = strtolower(trim(Auth::roleName() ?? ''));
        $isAdmin = Auth::isAdmin();

        if (! $isAdmin && ! in_array($roleName, ['jefe', 'subgerente'], true)) {
            http_response_code(403);
            echo 'Acceso denegado.';
            return;
        }

        $userId = (int) ($user['id'] ?? 0);
        // Permitir seleccionar el mes por GET (formato YYYY-MM)
        $selectedMonth = isset($_GET['mes']) ? $_GET['mes'] : (new \DateTimeImmutable())->format('Y-m');
        try {
            $monthStart = (new \DateTimeImmutable($selectedMonth . '-01'))->format('Y-m-d');
            // Fin de mes seleccionado
            $monthEnd = (new \DateTimeImmutable($monthStart))->modify('last day of this month')->format('Y-m-d');
        } catch (\Exception $e) {
            // Si el formato es inválido, usar mes actual
            $monthStart = (new \DateTimeImmutable('first day of this month'))->format('Y-m-d');
            $monthEnd = (new \DateTimeImmutable('today'))->format('Y-m-d');
        }


        $teams = Team::visibleTeamsForRole($userId, $roleName, $isAdmin);
        $teamIds = array_map(static fn ($team) => (int) $team['id'], $teams);
        $teamMembers = Team::membersForTeams($teamIds);

        // Filtro de equipo seleccionado
        $selectedTeamId = isset($_GET['team_id']) && $_GET['team_id'] !== '' ? (int)$_GET['team_id'] : null;
        if ($selectedTeamId && in_array($selectedTeamId, $teamIds, true)) {
            $filteredMemberIds = array_map(static fn ($member) => (int) $member['id'], $teamMembers[$selectedTeamId] ?? []);
            // Incluir jefe y subgerente explícitamente
            $selectedTeam = null;
            foreach ($teams as $t) {
                if ((int)$t['id'] === $selectedTeamId) {
                    $selectedTeam = $t;
                    break;
                }
            }
            if ($selectedTeam) {
                if (!in_array((int)$selectedTeam['jefe_id'], $filteredMemberIds, true)) {
                    $filteredMemberIds[] = (int)$selectedTeam['jefe_id'];
                }
                if (!in_array((int)$selectedTeam['subgerente_id'], $filteredMemberIds, true)) {
                    $filteredMemberIds[] = (int)$selectedTeam['subgerente_id'];
                }
            }
            $users = User::allWithRoleByIds($filteredMemberIds);
            $visibleUserIds = $filteredMemberIds;
        } else {
            if ($isAdmin) {
                $users = User::allWithRole();
                $visibleUserIds = array_map(static fn ($item) => (int) $item['id'], $users);
            } else {
                // Incluir todos los jefes y subgerentes de los equipos visibles
                $visibleUserIds = Team::visibleUserIdsForRole($userId, $roleName);
                foreach ($teams as $t) {
                    if (!in_array((int)$t['jefe_id'], $visibleUserIds, true)) {
                        $visibleUserIds[] = (int)$t['jefe_id'];
                    }
                    if (!in_array((int)$t['subgerente_id'], $visibleUserIds, true)) {
                        $visibleUserIds[] = (int)$t['subgerente_id'];
                    }
                }
                $users = User::allWithRoleByIds($visibleUserIds);
            }
        }

        $taskTotals = Task::countsByUserIdsInRange($visibleUserIds, $monthStart, $monthEnd);
        $taskCompleted = Task::completedCountsByUserIdsInRange($visibleUserIds, $monthStart, $monthEnd);
        $taskPendientes = Task::countsByStateAndUserIdsInRange($visibleUserIds, 'pendiente', $monthStart, $monthEnd);
        $taskAtrasadas = Task::countsByStateAndUserIdsInRange($visibleUserIds, 'atrasada', $monthStart, $monthEnd);
        $taskEnCurso = Task::countsByStateAndUserIdsInRange($visibleUserIds, 'en_curso', $monthStart, $monthEnd);
        $hoursTotals = Task::hoursTotalsByUserIdsInRange($visibleUserIds, $monthStart, $monthEnd);

        $collaboratorStats = [];
        $totalCritical = 0;
        foreach ($users as $item) {
            $itemUserId = (int) $item['id'];
            $itemRoleName = strtolower(trim($item['rol_nombre'] ?? ''));
            // Excluir subgerentes de los datos mostrados
            if ($itemRoleName === 'subgerente') {
                continue;
            }
            $total = (int) ($taskTotals[$itemUserId] ?? 0);
            $completed = (int) ($taskCompleted[$itemUserId] ?? 0);
            $pendientes = (int) ($taskPendientes[$itemUserId] ?? 0);
            $atrasadas = (int) ($taskAtrasadas[$itemUserId] ?? 0);
            $encurso = (int) ($taskEnCurso[$itemUserId] ?? 0);
            $hours = (float) ($hoursTotals[$itemUserId] ?? 0);
            
            $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
            $critical = TaskIndicator::countCriticalNotFinished([$itemUserId]);
            $totalCritical += $critical;
            
            // Obtener equipos del usuario para filtrado
            $userTeamIds = [];
            foreach ($teams as $t) {
                $members = $teamMembers[(int)$t['id']] ?? [];
                foreach ($members as $m) {
                    if ((int)$m['id'] === $itemUserId) {
                        $userTeamIds[] = (int)$t['id'];
                    }
                }
            }
            
            $collaboratorStats[] = [
                'id' => $itemUserId,
                'nombre' => $item['nombre'],
                'rol' => $item['rol_nombre'] ?? '',
                'total' => $total,
                'terminadas' => $completed,
                'pendientes' => $pendientes,
                'atrasadas' => $atrasadas,
                'encurso' => $encurso,
                'horas' => $hours,
                'cumplimiento' => $rate,
                'criticas' => $critical,
                'team_ids' => $userTeamIds,
            ];
        }

        $teamStats = [];
        foreach ($teams as $team) {

            $teamId = (int) $team['id'];
            $members = $teamMembers[$teamId] ?? [];
            $memberIds = array_map(static fn ($member) => (int) $member['id'], $members);

            $total = 0;
            $completed = 0;
            $hours = 0.0;
            $pendientes = 0;
            $atrasadas = 0;
            $encurso = 0;
            // Sumar los valores de collaboratorStats para los miembros de este equipo
            foreach ($collaboratorStats as $col) {
                if (in_array((int)$col['id'], $memberIds, true)) {
                    $total += (int) ($col['total'] ?? 0);
                    $completed += (int) ($col['terminadas'] ?? 0);
                    $hours += (float) ($col['horas'] ?? 0);
                    $pendientes += (int) ($col['pendientes'] ?? 0);
                    $atrasadas += (int) ($col['atrasadas'] ?? 0);
                    $encurso += (int) ($col['encurso'] ?? 0);
                }
            }
            $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
            $teamStats[] = [
                'id' => $teamId,
                'nombre' => $team['nombre'],
                'colaboradores' => count($members),
                'total' => $total,
                'pendientes' => $pendientes,
                'atrasadas' => $atrasadas,
                'encurso' => $encurso,
                'terminadas' => $completed,
                'horas' => $hours,
                'cumplimiento' => $rate,
            ];
        }

        // Calcular los últimos 6 meses móviles (YYYY-MM)
        $months = [];
        $now = new \DateTimeImmutable($monthStart);
        for ($i = 5; $i >= 0; $i--) {
            $months[] = $now->modify("-{$i} months")->format('Y-m');
        }

        // Rango de fechas para la consulta (inicio del primer mes, fin del último mes)
        $firstMonthStart = (new \DateTimeImmutable($months[0] . '-01'))->format('Y-m-d');
        $lastMonthEnd = (new \DateTimeImmutable($months[5] . '-01'))->modify('last day of this month')->format('Y-m-d');

        // Obtener IDs de colaboradores (sin subgerentes) para los gráficos
        $collaboratorIds = array_map(static fn ($col) => (int) $col['id'], $collaboratorStats);

        // Por colaborador: tareas y horas por mes (usando fecha_compromiso)
        $tasksByUserByMonth = [];
        $hoursByUserByMonth = [];
        $tasksByUserCurrentMonth = [];
        $hoursByUserCurrentMonth = [];
        
        foreach ($collaboratorIds as $colId) {
            $allTasks = Task::allForUserIds([$colId]);
            
            // Inicializar meses
            foreach ($months as $month) {
                $tasksByUserByMonth[$colId][$month] = 0;
                $hoursByUserByMonth[$colId][$month] = 0.0;
            }
            $tasksByUserCurrentMonth[$colId] = 0;
            $hoursByUserCurrentMonth[$colId] = 0.0;
            
            // Calcular tareas y horas por mes basado en fecha_compromiso
            foreach ($allTasks as $task) {
                if (!empty($task['fecha_compromiso'])) {
                    $taskMonth = substr($task['fecha_compromiso'], 0, 7);
                    
                    // Acumulativo 6 meses
                    if (in_array($taskMonth, $months)) {
                        $tasksByUserByMonth[$colId][$taskMonth]++;
                        $horas = isset($task['total_horas']) ? (float)$task['total_horas'] : (isset($task['horas']) ? (float)$task['horas'] : 0);
                        $hoursByUserByMonth[$colId][$taskMonth] += $horas;
                    }
                    
                    // Mes seleccionado actual
                    if ($taskMonth === $selectedMonth) {
                        $tasksByUserCurrentMonth[$colId]++;
                        $horas = isset($task['total_horas']) ? (float)$task['total_horas'] : (isset($task['horas']) ? (float)$task['horas'] : 0);
                        $hoursByUserCurrentMonth[$colId] += $horas;
                    }
                }
            }
        }

        // Por equipo: sumar por cada miembro
        $tasksByTeamByMonth = [];
        $hoursByTeamByMonth = [];
        foreach ($teams as $team) {
            $teamId = (int) $team['id'];
            $members = $teamMembers[$teamId] ?? [];
            $memberIds = array_map(static fn ($member) => (int) $member['id'], $members);
            foreach ($months as $month) {
                $tasksByTeamByMonth[$teamId][$month] = 0;
                $hoursByTeamByMonth[$teamId][$month] = 0.0;
                foreach ($memberIds as $memberId) {
                    $tasksByTeamByMonth[$teamId][$month] += $tasksByUserByMonth[$memberId][$month] ?? 0;
                    $hoursByTeamByMonth[$teamId][$month] += $hoursByUserByMonth[$memberId][$month] ?? 0.0;
                }
            }
        }

        $totalTasks = array_sum($taskTotals);
        $totalCompleted = array_sum($taskCompleted);
        $totalHours = array_sum($hoursTotals);
        $completionRate = $totalTasks > 0 ? round(($totalCompleted / $totalTasks) * 100, 1) : 0;

        $this->view('tasks/gestion', [
            'title' => 'Seguimiento de Equipo',
            'teams' => $teams,
            'teamStats' => $teamStats,
            'collaboratorStats' => $collaboratorStats,
            'totalCritical' => $totalCritical,
            'monthLabel' => (new \DateTimeImmutable($monthStart))->format('m/Y'),
            'selectedMonth' => $selectedMonth,
            'months' => $months,
            'tasksByUserByMonth' => $tasksByUserByMonth,
            'hoursByUserByMonth' => $hoursByUserByMonth,
            'tasksByUserCurrentMonth' => $tasksByUserCurrentMonth,
            'hoursByUserCurrentMonth' => $hoursByUserCurrentMonth,
            'tasksByTeamByMonth' => $tasksByTeamByMonth,
            'hoursByTeamByMonth' => $hoursByTeamByMonth,
            'totalTasks' => $totalTasks,
            'totalCompleted' => $totalCompleted,
            'totalHours' => $totalHours,
            'completionRate' => $completionRate,
        ]);
    }
}
