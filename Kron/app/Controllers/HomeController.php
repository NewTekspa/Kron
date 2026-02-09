<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Task;
use App\Models\TaskIndicator;
use App\Models\Team;
use App\Models\User;
use App\Models\TeamTaskIndicator;

class HomeController extends Controller
{
    private array $statusOptions = ['pendiente', 'en_curso', 'congelada', 'atrasada', 'terminada'];

    public function index(): void
    {
        $this->requireLogin();

        $user = Auth::user();
        $userId = (int) $user['id'];
        $roleName = Auth::roleName() ?? '';
        $isAdmin = Auth::isAdmin();
        $personalOnly = true;
        $visibleIds = [$userId];

        $openTasks = Task::openTasksForUsers($visibleIds, 12);
        $criticalNotFinished = TaskIndicator::countCriticalNotFinished($visibleIds);
        $monthStart = (new \DateTimeImmutable('first day of this month'))->format('Y-m-d');
        $monthEnd = (new \DateTimeImmutable('today'))->format('Y-m-d');

        $userViewUsers = User::allWithRoleByIds([$userId]);
        $userViewIds = array_map(static fn ($item) => (int) $item['id'], $userViewUsers);
        $countsByUserView = Task::countsByUserIds($userViewIds);
        $hoursTotalsByUserView = Task::hoursTotalsByUserIdsInRange($userViewIds, $monthStart, $monthEnd);

        $userStats = [];
        $userStatsHours = [];
        foreach ($userViewUsers as $item) {
            $itemUserId = (int) $item['id'];
            $counts = $countsByUserView[$itemUserId] ?? ['total' => 0, 'terminadas' => 0];
            $userStats[] = [
                'label' => $item['nombre'],
                'total' => (int) ($counts['total'] ?? 0),
                'terminadas' => (int) ($counts['terminadas'] ?? 0),
            ];
            $userStatsHours[] = [
                'label' => $item['nombre'],
                'horas' => (float) ($hoursTotalsByUserView[$itemUserId] ?? 0),
            ];
        }

        $teamStats = [];
        $teamStatsHours = [];
        $criticalByTeam = TeamTaskIndicator::countCriticalNotFinishedByTeam();

        $months = 6;
        $startMonth = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months');
        $sinceDate = $startMonth->format('Y-m-01');
        $hoursByUser = Task::hoursByUserIdsByMonth($userViewIds, $sinceDate);
        $tasksByUserByMonth = Task::countsByUserIdsByMonth($userViewIds, $sinceDate);
        $hoursMonths = [];
        $cursor = $startMonth;
        $end = new \DateTimeImmutable('first day of this month');
        while ($cursor <= $end) {
            $hoursMonths[] = $cursor->format('Y-m-01');
            $cursor = $cursor->modify('+1 month');
        }
        $hoursSeriesByView = [
            'user' => [],
            'team' => [],
        ];
        $tasksSeriesByView = [
            'user' => [],
            'team' => [],
        ];
        foreach ($userViewUsers as $item) {
            $itemUserId = (int) $item['id'];
            $values = [];
            $hasData = false;
            foreach ($hoursMonths as $month) {
                $value = (float) ($hoursByUser[$itemUserId][$month] ?? 0);
                $values[] = $value;
                if ($value > 0) {
                    $hasData = true;
                }
            }
            if ($hasData) {
                $hoursSeriesByView['user'][] = [
                    'label' => $item['nombre'],
                    'values' => $values,
                ];
            }

            $taskValues = [];
            $taskHasData = false;
            foreach ($hoursMonths as $month) {
                $value = (int) ($tasksByUserByMonth[$itemUserId][$month] ?? 0);
                $taskValues[] = $value;
                if ($value > 0) {
                    $taskHasData = true;
                }
            }
            if ($taskHasData) {
                $tasksSeriesByView['user'][] = [
                    'label' => $item['nombre'],
                    'values' => $taskValues,
                ];
            }
        }

        $this->view('home', [
            'title' => 'Dashboard',
            'openTasks' => $openTasks,
            'teamStats' => $teamStats,
            'userStats' => $userStats,
            'teamStatsHours' => $teamStatsHours,
            'criticalByTeam' => $criticalByTeam,
            'userStatsHours' => $userStatsHours,
            'hoursMonths' => $hoursMonths,
            'hoursSeriesByView' => $hoursSeriesByView,
            'tasksSeriesByView' => $tasksSeriesByView,
            'monthLabel' => (new \DateTimeImmutable($monthStart))->format('m/Y'),
            'personalOnly' => $personalOnly,
            'authUserName' => (string) ($user['nombre'] ?? ''),
            'statusOptions' => $this->statusOptions,
            'criticalNotFinished' => $criticalNotFinished,
        ]);
    }
}
