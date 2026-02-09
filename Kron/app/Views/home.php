<?php
/** @var string $title */
/** @var array $openTasks */
/** @var array $teamStats */
/** @var array $userStats */
/** @var array $teamStatsHours */
/** @var array $userStatsHours */
/** @var array $hoursMonths */
/** @var array $hoursSeriesByView */
/** @var array $tasksSeriesByView */
/** @var string $monthLabel */
/** @var bool $personalOnly */
/** @var string $authUserName */
/** @var array $statusOptions */
/** @var string $content */
ob_start();
$formatDate = function (?string $value): string {
    if (! $value) {
        return '-';
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('d-m-Y', $timestamp) : $value;
};
$formatShortMonth = function (?string $value): string {
    if (! $value) {
        return '-';
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('m/Y', $timestamp) : $value;
};
$statusLabels = [
    'pendiente' => 'Pendiente',
    'en_curso' => 'En curso',
    'atrasada' => 'Atrasada',
    'congelada' => 'Congelada',
    'terminada' => 'Terminada',
];
$maxTeamTotal = 0;
foreach ($teamStats as $stat) {
    $maxTeamTotal = max($maxTeamTotal, (int) ($stat['total'] ?? 0));
}
$maxUserTotal = 0;
foreach ($userStats as $stat) {
    $maxUserTotal = max($maxUserTotal, (int) ($stat['total'] ?? 0));
}
$maxTeamHours = 0;
foreach ($teamStatsHours as $stat) {
    $maxTeamHours = max($maxTeamHours, (float) ($stat['horas'] ?? 0));
}
$maxUserHours = 0;
foreach ($userStatsHours as $stat) {
    $maxUserHours = max($maxUserHours, (float) ($stat['horas'] ?? 0));
}
$maxTeamTotal = max($maxTeamTotal, 1);
$maxUserTotal = max($maxUserTotal, 1);
$maxTeamHours = max($maxTeamHours, 1);
$maxUserHours = max($maxUserHours, 1);
$chartWidth = 100;
$chartHeight = 40;
$chartPadding = 4;
$chartPaddingX = 6;
$lineColors = ['#2563eb', '#22c55e', '#f97316', '#ef4444', '#0ea5e9', '#14b8a6', '#eab308', '#64748b'];
$buildPointCoords = function (array $values, float $max, int $width, int $height, int $padding, int $paddingX): array {
    $lastIndex = max(count($values) - 1, 1);
    $usableHeight = $height - ($padding * 2);
    $usableWidth = $width - ($paddingX * 2);
    $points = [];
    foreach ($values as $index => $value) {
        $x = $lastIndex === 0 ? $paddingX : $paddingX + (($index / $lastIndex) * $usableWidth);
        $ratio = $max > 0 ? ((float) $value / $max) : 0;
        $y = $padding + ($usableHeight * (1 - $ratio));
        $points[] = [
            'x' => round($x, 2),
            'y' => round($y, 2),
            'value' => (float) $value,
        ];
    }
    return $points;
};
$getSeriesMax = function (array $series): float {
    $max = 0;
    foreach ($series as $item) {
        foreach ($item['values'] as $value) {
            $max = max($max, (float) $value);
        }
    }
    return max($max, 1);
};
$showTeamViews = ! $personalOnly;
?>
<div class="page-header">
    <div>
        <h1 style="display: flex; align-items: center; gap: 12px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--blue);">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            Dashboard
        </h1>
        <p class="muted" style="margin: 4px 0 0 44px; font-size: 15px;">Bienvenido, <strong style="color: var(--text-medium);"><?= htmlspecialchars($authUserName) ?></strong></p>
    </div>
</div>
<div class="dashboard-top">
    <div class="dashboard-col">
        <section class="card-block dashboard-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: var(--shadow);">
            <div class="card-header">
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--blue) 0%, var(--blue-light) 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M9 11l3 3L22 4"></path>
                            <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 style="margin: 0 0 4px;">Cumplimiento</h2>
                        <?php
                        // Calcular el porcentaje de cumplimiento del equipo (tareas terminadas / total)
                        $cumplimiento = 0;
                        if (!empty($teamStats)) {
                            $totalTareas = 0;
                            $totalTerminadas = 0;
                            foreach ($teamStats as $stat) {
                                $totalTareas += (int)($stat['total'] ?? 0);
                                $totalTerminadas += (int)($stat['terminadas'] ?? 0);
                            }
                            if ($totalTareas > 0) {
                                $cumplimiento = round(($totalTerminadas / $totalTareas) * 100);
                            }
                        }
                        ?>
                        <p class="muted" style="margin: 0; font-size: 32px; font-weight: bold; color: var(--blue);">
                            <?= $cumplimiento ?>%
                        </p>
                        <p class="muted" style="margin: 0; font-size: 13px;">Porcentaje de tareas terminadas</p>
                    </div>
                </div>
            </div>
            <?php if ($showTeamViews): ?>
                <div class="chart-view" data-view="team">
                    <?php if (empty($teamStats)): ?>
                        <p class="muted">No hay equipos disponibles.</p>
                    <?php else: ?>
                        <table class="table table-compact">
                            <thead>
                                <tr>
                                    <th>Equipo</th>
                                    <th>Total tareas</th>
                                    <th>Críticas no terminadas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teamStats as $stat): ?>
                                    <?php
                                    $total = (int) ($stat['total'] ?? 0);
                                    $teamId = isset($stat['id']) ? (int)$stat['id'] : null;
                                    $criticas = $teamId && isset($criticalByTeam[$teamId]) ? (int)$criticalByTeam[$teamId] : 0;
                                    ?>
                                    <tr>
                                        <td style="font-weight: 500;"><?= htmlspecialchars($stat['label']) ?></td>
                                        <td><span style="background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 12px;"><?= htmlspecialchars($total) ?></span></td>
                                        <td style="text-align:center;">
                                            <?php if ($criticas > 0): ?>
                                                <span style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #b91c1c; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 12px; border: 1px solid #fca5a5;">
                                                    🔥 <?= $criticas ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #94a3b8; font-size: 12px;">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="chart-view<?= $showTeamViews ? ' is-hidden' : '' ?>" data-view="user">
                <?php if (empty($userStats)): ?>
                    <p class="muted">No hay colaboradores disponibles.</p>
                <?php else: ?>
                    <?php foreach ($userStats as $stat): ?>
                        <?php
                        $total = (int) ($stat['total'] ?? 0);
                        $totalPct = $maxUserTotal > 0 ? (int) round(($total / $maxUserTotal) * 100) : 0;
                        ?>
                        <div class="chart-row">
                            <div class="chart-label"><?= htmlspecialchars($stat['label']) ?></div>
                            <div class="chart-bar-wrap">
                                <div class="chart-bar">
                                    <div class="chart-bar-total" style="width: <?= $totalPct ?>%;"></div>
                                </div>
                                <div class="chart-value"><?= htmlspecialchars($total) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        <section class="card-block dashboard-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: var(--shadow);">
            <div class="card-header">
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #10b981 0%, #34d399 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                    </div>
                    <div>
                        <h2 style="margin: 0 0 4px;">Evolutivo de tareas</h2>
                        <p class="muted" style="margin: 0; font-size: 13px;">📈 Últimos <?= (int) count($hoursMonths) ?> meses</p>
                    </div>
                </div>
                <div class="muted" style="font-size: 12px; background: #f1f5f9; padding: 6px 12px; border-radius: 6px;">Total por mes</div>
            </div>
            <?php if (empty($tasksSeriesByView['user']) && empty($tasksSeriesByView['team'])): ?>
                <p class="muted">No hay tareas registradas en este periodo.</p>
            <?php else: ?>
                <?php foreach (($showTeamViews ? ['team' => 'team', 'user' => 'user'] : ['user' => 'user']) as $viewKey => $viewLabel): ?>
                    <?php
                    $series = $tasksSeriesByView[$viewKey] ?? [];
                    $tasksMax = $getSeriesMax($series) + 3;
                    ?>
                    <div class="line-chart-view<?= ($showTeamViews && $viewKey === 'team') ? '' : ($showTeamViews ? ' is-hidden' : '') ?>" data-view="<?= htmlspecialchars($viewLabel) ?>">
                        <?php if (empty($series)): ?>
                            <p class="muted">No hay tareas registradas para este grupo.</p>
                        <?php else: ?>
                            <div class="line-chart-wrap">
                                <svg viewBox="0 0 <?= (int) $chartWidth ?> <?= (int) $chartHeight ?>" class="line-chart" preserveAspectRatio="none">
                                    <?php for ($i = 0; $i <= 4; $i++): ?>
                                        <?php $y = $chartPadding + (($chartHeight - ($chartPadding * 2)) * ($i / 4)); ?>
                                        <line class="line-grid" x1="<?= (int) $chartPaddingX ?>" y1="<?= (float) $y ?>" x2="<?= (int) ($chartWidth - $chartPaddingX) ?>" y2="<?= (float) $y ?>"></line>
                                    <?php endfor; ?>
                                    <?php foreach ($series as $index => $item): ?>
                                        <?php
                                        $color = $lineColors[$index % count($lineColors)];
                                        $coords = $buildPointCoords($item['values'], $tasksMax, $chartWidth, $chartHeight, $chartPadding, $chartPaddingX);
                                        $points = implode(' ', array_map(static fn ($point) => $point['x'] . ',' . $point['y'], $coords));
                                        ?>
                                        <polyline
                                            fill="none"
                                            stroke="<?= htmlspecialchars($color) ?>"
                                            stroke-width="1.4"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            points="<?= htmlspecialchars($points) ?>"></polyline>
                                        <?php foreach ($coords as $point): ?>
                                            <circle cx="<?= htmlspecialchars((string) $point['x']) ?>" cy="<?= htmlspecialchars((string) $point['y']) ?>" r="1.1" class="line-point" style="stroke: <?= htmlspecialchars($color) ?>;"></circle>
                                            <text x="<?= htmlspecialchars((string) $point['x']) ?>" y="<?= htmlspecialchars((string) ($point['y'] - 2.2)) ?>" class="line-value" text-anchor="middle">
                                                <?= htmlspecialchars((string) $point['value']) ?>
                                            </text>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </svg>
                            </div>
                            <div class="line-axis">
                                <span><?= htmlspecialchars($formatShortMonth($hoursMonths[0] ?? null)) ?></span>
                                <span><?= htmlspecialchars($formatShortMonth($hoursMonths[count($hoursMonths) - 1] ?? null)) ?></span>
                            </div>
                            <div class="line-legend">
                                <?php foreach ($series as $index => $item): ?>
                                    <?php $color = $lineColors[$index % count($lineColors)]; ?>
                                    <div class="line-legend-item">
                                        <span class="line-legend-swatch" style="background: <?= htmlspecialchars($color) ?>;"></span>
                                        <span class="muted"><?= htmlspecialchars($item['label']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
    <div class="dashboard-col">
        <section class="card-block dashboard-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: var(--shadow);">
            <div class="card-header">
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div>
                        <h2 style="margin: 0 0 4px;">Horas registradas</h2>
                        <p class="muted" style="margin: 0; font-size: 13px;">⏱️ Acumulado del mes <?= htmlspecialchars($monthLabel) ?></p>
                    </div>
                </div>
                <div class="muted" style="font-size: 12px; background: #f1f5f9; padding: 6px 12px; border-radius: 6px;">Total acumulado</div>
            </div>
            <?php if ($showTeamViews): ?>
                <div class="hours-view" data-view="team">
                    <?php if (empty($teamStatsHours)): ?>
                        <p class="muted">No hay horas registradas.</p>
                    <?php else: ?>
                        <?php foreach ($teamStatsHours as $stat): ?>
                            <?php
                            $hours = (float) ($stat['horas'] ?? 0);
                            $hoursPct = $maxTeamHours > 0 ? (int) round(($hours / $maxTeamHours) * 100) : 0;
                            ?>
                            <div class="chart-row">
                                <div class="chart-label"><?= htmlspecialchars($stat['label']) ?></div>
                                <div class="chart-bar-wrap">
                                    <div class="chart-bar">
                                        <div class="chart-bar-hours" style="width: <?= $hoursPct ?>%;"></div>
                                    </div>
                                    <div class="chart-value"><?= htmlspecialchars(number_format($hours, 1)) ?> h</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="hours-view<?= $showTeamViews ? ' is-hidden' : '' ?>" data-view="user">
                <?php if (empty($userStatsHours)): ?>
                    <p class="muted">No hay horas registradas.</p>
                <?php else: ?>
                    <?php foreach ($userStatsHours as $stat): ?>
                        <?php
                        $hours = (float) ($stat['horas'] ?? 0);
                        $hoursPct = $maxUserHours > 0 ? (int) round(($hours / $maxUserHours) * 100) : 0;
                        ?>
                        <div class="chart-row">
                            <div class="chart-label"><?= htmlspecialchars($stat['label']) ?></div>
                            <div class="chart-bar-wrap">
                                <div class="chart-bar">
                                    <div class="chart-bar-hours" style="width: <?= $hoursPct ?>%;"></div>
                                </div>
                                <div class="chart-value"><?= htmlspecialchars(number_format($hours, 1)) ?> h</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        <section class="card-block dashboard-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: var(--shadow);">
            <div class="card-header">
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 style="margin: 0 0 4px;">Horas por colaborador</h2>
                        <p class="muted" style="margin: 0; font-size: 13px;">👥 Evolutivo mensual de horas</p>
                    </div>
                </div>
                <div class="muted" style="font-size: 12px; background: #f1f5f9; padding: 6px 12px; border-radius: 6px;">Últimos <?= (int) count($hoursMonths) ?> meses</div>
            </div>
            <?php if (empty($hoursSeriesByView['user']) && empty($hoursSeriesByView['team'])): ?>
                <p class="muted">No hay horas registradas en este periodo.</p>
            <?php else: ?>
                <?php foreach (($showTeamViews ? ['team' => 'team', 'user' => 'user'] : ['user' => 'user']) as $viewKey => $viewLabel): ?>
                    <?php
                    $series = $hoursSeriesByView[$viewKey] ?? [];
                    $hoursMax = $getSeriesMax($series) + 3;
                    ?>
                    <div class="line-chart-view<?= ($showTeamViews && $viewKey === 'team') ? '' : ($showTeamViews ? ' is-hidden' : '') ?>" data-view="<?= htmlspecialchars($viewLabel) ?>">
                        <?php if (empty($series)): ?>
                            <p class="muted">No hay horas registradas para este grupo.</p>
                        <?php else: ?>
                            <div class="line-chart-wrap">
                                <svg viewBox="0 0 <?= (int) $chartWidth ?> <?= (int) $chartHeight ?>" class="line-chart" preserveAspectRatio="none">
                                    <?php for ($i = 0; $i <= 4; $i++): ?>
                                        <?php $y = $chartPadding + (($chartHeight - ($chartPadding * 2)) * ($i / 4)); ?>
                                        <line class="line-grid" x1="<?= (int) $chartPaddingX ?>" y1="<?= (float) $y ?>" x2="<?= (int) ($chartWidth - $chartPaddingX) ?>" y2="<?= (float) $y ?>"></line>
                                    <?php endfor; ?>
                                    <?php foreach ($series as $index => $item): ?>
                                        <?php
                                        $color = $lineColors[$index % count($lineColors)];
                                        $coords = $buildPointCoords($item['values'], $hoursMax, $chartWidth, $chartHeight, $chartPadding, $chartPaddingX);
                                        $points = implode(' ', array_map(static fn ($point) => $point['x'] . ',' . $point['y'], $coords));
                                        ?>
                                        <polyline
                                            fill="none"
                                            stroke="<?= htmlspecialchars($color) ?>"
                                            stroke-width="1.4"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            points="<?= htmlspecialchars($points) ?>"></polyline>
                                        <?php foreach ($coords as $point): ?>
                                            <circle cx="<?= htmlspecialchars((string) $point['x']) ?>" cy="<?= htmlspecialchars((string) $point['y']) ?>" r="1.1" class="line-point" style="stroke: <?= htmlspecialchars($color) ?>;"></circle>
                                            <text x="<?= htmlspecialchars((string) $point['x']) ?>" y="<?= htmlspecialchars((string) ($point['y'] - 2.2)) ?>" class="line-value" text-anchor="middle">
                                                <?= htmlspecialchars(number_format($point['value'], 1)) ?>
                                            </text>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </svg>
                            </div>
                            <div class="line-axis">
                                <span><?= htmlspecialchars($formatShortMonth($hoursMonths[0] ?? null)) ?></span>
                                <span><?= htmlspecialchars($formatShortMonth($hoursMonths[count($hoursMonths) - 1] ?? null)) ?></span>
                            </div>
                            <div class="line-legend">
                                <?php foreach ($series as $index => $item): ?>
                                    <?php $color = $lineColors[$index % count($lineColors)]; ?>
                                    <div class="line-legend-item">
                                        <span class="line-legend-swatch" style="background: <?= htmlspecialchars($color) ?>;"></span>
                                        <span class="muted"><?= htmlspecialchars($item['label']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
</div>
    <section class="card-block dashboard-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: var(--shadow); margin-top: 24px;">
        <div class="card-header">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #ef4444 0%, #f87171 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <div>
                    <h2 style="margin: 0 0 4px;">Tareas abiertas</h2>
                    <p class="muted" style="margin: 0; font-size: 13px;">📋 Pendientes y en curso, ordenadas por fecha</p>
                </div>
            </div>
            <div class="filter-bar" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 10px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="taskFilter" style="margin-bottom:0;">Buscar</label>
                    <input type="text" id="taskFilter" class="input-medium" placeholder="Titulo, actividad, clasificacion o estado" autocomplete="off">
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="priorityFilter" style="margin-bottom:0;">Prioridad</label>
                    <select id="priorityFilter" class="input-medium">
                        <option value="">Todas</option>
                        <option value="baja">Baja</option>
                        <option value="media">Media</option>
                        <option value="alta">Alta</option>
                        <option value="critica">Crítica</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="table-wrap">
            <table class="table table-compact">
                <thead>
                    <tr>
                        <th>Titulo</th>
                        <th>Actividad</th>
                        <th>Clasificacion</th>
                        <th class="table-center">Prioridad</th>
                        <th class="table-center">Compromiso</th>
                        <th class="table-center">Estado</th>
                        <th class="table-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="openTasksBody">
                    <?php if (empty($openTasks)): ?>
                        <tr>
                            <td colspan="7" class="muted">No hay tareas abiertas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($openTasks as $task): ?>
                            <?php $statusKey = str_replace('_', '-', $task['estado']); ?>
                            <tr data-task-row="<?= (int) $task['id'] ?>" data-prioridad="<?= htmlspecialchars(strtolower($task['prioridad'] ?? '')) ?>">
                                <td><?= htmlspecialchars($task['titulo']) ?></td>
                                <td><?= htmlspecialchars($task['categoria_nombre'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($task['clasificacion_nombre'] ?? '-') ?></td>
                                <td class="table-center"><?= htmlspecialchars(ucfirst($task['prioridad'] ?? '-')) ?></td>
                                <td class="table-center"><?= htmlspecialchars($formatDate($task['fecha_compromiso'] ?? null)) ?></td>
                                <td class="table-center">
                                    <span class="status-badge status-<?= htmlspecialchars($statusKey) ?>" data-status-badge>
                                        <?= htmlspecialchars($task['estado']) ?>
                                    </span>
                                </td>
                                <td class="table-center">
                                    <div style="display: flex; flex-direction: row; gap: 4px; align-items: center; justify-content: center; flex-wrap: nowrap;">
                                        <a href="/horas/registrar?tarea_id=<?= (int) $task['id'] ?>&return_url=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-small btn-icon" title="Registrar horas" aria-label="Registrar horas">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10" />
                                                <polyline points="12 6 12 12 16 14" />
                                            </svg>
                                        </a>
                                        <button type="button" class="btn btn-small btn-icon" data-open-modal="logModal" data-task-id="<?= (int) $task['id'] ?>" data-task-title="<?= htmlspecialchars($task['titulo']) ?>" title="Agregar observación" aria-label="Agregar observación">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                                                <polyline points="14 2 14 8 20 8" />
                                                <line x1="16" y1="13" x2="8" y2="13" />
                                                <line x1="16" y1="17" x2="8" y2="17" />
                                                <line x1="10" y1="9" x2="8" y2="9" />
                                            </svg>
                                        </button>
                                        <a href="<?= $basePath ?>/tareas/detalle-informativo?id=<?= (int) $task['id'] ?>" class="btn btn-small btn-icon" title="Ver detalle informativo" aria-label="Ver detalle informativo">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <script>
                    document.getElementById('priorityFilter').addEventListener('change', function() {
                        const value = this.value;
                        document.querySelectorAll('#openTasksBody tr[data-task-row]').forEach(row => {
                            if (!value || row.getAttribute('data-prioridad') === value) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        });
                    });
                    </script>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<div class="modal" id="logModal" data-modal>
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="logModalTitle">
        <div class="modal-header">
            <h2 id="logModalTitle">Agregar observación</h2>
            <button type="button" class="btn btn-secondary btn-small" data-close-modal>Cerrar</button>
        </div>
        <form method="post" action="<?= $basePath ?>/tareas/bitacora" class="form" id="logForm">
            <input type="hidden" name="task_id" value="">
            <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
            <div class="alert is-hidden" id="logError"></div>
            <p class="muted" id="logTaskTitle" style="margin-bottom: 16px;"></p>
            <label>Observación</label>
            <textarea name="contenido" rows="4" required placeholder="Escribe tu observación aquí..."></textarea>
            <div class="form-actions">
                <button type="submit" class="btn">Guardar</button>
            </div>
        </form>
    </div>
</div>
<div class="modal" id="statusModal" data-modal>
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="statusModalTitle">
        <div class="modal-header">
            <h2 id="statusModalTitle">Cambiar estado</h2>
            <button type="button" class="btn btn-secondary btn-small" data-close-modal>Cerrar</button>
        </div>
        <form method="post" action="<?= $basePath ?>/tareas/estado" class="form" id="statusForm">
            <input type="hidden" name="task_id" value="">
            <input type="hidden" name="return_url" value="<?= $basePath ?>/">
            <div class="alert is-hidden" id="statusError"></div>
            <label>Estado</label>
            <select name="estado" required>
                <?php foreach ($statusOptions as $option): ?>
                    <option value="<?= htmlspecialchars($option) ?>">
                        <?= htmlspecialchars($statusLabels[$option] ?? $option) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div id="statusHours" class="is-hidden">
                <label>Fecha</label>
                <input type="date" name="fecha">
                <label>Horas (HH:MM)</label>
                <input type="time" name="horas" step="60">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Actualizar</button>
            </div>
        </form>
    </div>
</div>
<script>
(() => {
    const setupModal = (modalId, onOpen) => {
        const modal = document.getElementById(modalId);
        if (!modal) {
            return null;
        }
        const openButtons = document.querySelectorAll(`[data-open-modal="${modalId}"]`);
        const closeTargets = modal.querySelectorAll('[data-close-modal]');
        const openModal = (event) => {
            modal.classList.add('is-open');
            if (onOpen) {
                onOpen(event, modal);
            }
        };
        const closeModal = () => {
            modal.classList.remove('is-open');
        };

        openButtons.forEach((btn) => btn.addEventListener('click', openModal));
        closeTargets.forEach((btn) => btn.addEventListener('click', closeModal));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        return { openModal, closeModal, modal };
    };

    const statusModal = setupModal('statusModal', (event, modal) => {
        const button = event?.currentTarget;
        const taskId = button?.getAttribute('data-task-id') || '';
        const taskStatus = button?.getAttribute('data-task-status') || '';
        const taskCommitment = button?.getAttribute('data-task-commitment') || '';
        const taskHasHours = button?.getAttribute('data-task-has-hours') || '0';
        const idInput = modal.querySelector('input[name="task_id"]');
        if (idInput) {
            idInput.value = taskId;
        }
        const select = modal.querySelector('select[name="estado"]');
        if (select && taskStatus) {
            select.value = taskStatus;
        }
        modal.dataset.taskStatus = taskStatus;
        modal.dataset.taskCommitment = taskCommitment;
        modal.dataset.taskHasHours = taskHasHours;
        const errorBox = modal.querySelector('#statusError');
        if (errorBox) {
            errorBox.classList.add('is-hidden');
            errorBox.textContent = '';
        }
        const hoursWrap = modal.querySelector('#statusHours');
        if (hoursWrap) {
            hoursWrap.classList.add('is-hidden');
            hoursWrap.querySelectorAll('input').forEach((input) => {
                input.removeAttribute('required');
                input.value = '';
            });
        }
    });

    const statusForm = document.getElementById('statusForm');
    if (statusForm) {
        const statusSelect = statusForm.querySelector('select[name="estado"]');
        const hoursWrap = statusForm.querySelector('#statusHours');
        const dateInput = statusForm.querySelector('input[name="fecha"]');
        const timeInput = statusForm.querySelector('input[name="horas"]');
        const errorBox = document.getElementById('statusError');

        const setError = (message) => {
            if (!errorBox) {
                return;
            }
            if (message) {
                errorBox.textContent = message;
                errorBox.classList.remove('is-hidden');
            } else {
                errorBox.textContent = '';
                errorBox.classList.add('is-hidden');
            }
        };

        const toggleHours = () => {
            const modal = document.getElementById('statusModal');
            if (!hoursWrap || !modal) {
                return;
            }
            const selected = statusSelect?.value || '';
            const hasHours = modal.dataset.taskHasHours === '1';
            if (selected === 'terminada' && !hasHours) {
                hoursWrap.classList.remove('is-hidden');
                hoursWrap.querySelectorAll('input').forEach((input) => {
                    input.setAttribute('required', 'required');
                });
                if (dateInput && !dateInput.value) {
                    dateInput.value = new Date().toISOString().slice(0, 10);
                }
            } else {
                hoursWrap.classList.add('is-hidden');
                hoursWrap.querySelectorAll('input').forEach((input) => {
                    input.removeAttribute('required');
                });
            }
        };

        statusSelect?.addEventListener('change', () => {
            setError('');
            toggleHours();
        });

        statusForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const modal = document.getElementById('statusModal');
            const currentStatus = modal?.dataset?.taskStatus || '';
            const commitment = modal?.dataset?.taskCommitment || '';
            const target = statusSelect?.value || '';
            if (currentStatus === 'pendiente' && target !== 'pendiente' && !commitment) {
                setError('Debes definir fecha de compromiso.');
                return;
            }
            const formData = new FormData(statusForm);
            const res = await fetch('<?= $basePath ?>/tareas/estado', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'fetch', Accept: 'application/json' },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                setError(data.error || 'No se pudo actualizar el estado.');
                return;
            }
            setError('');
            const taskId = formData.get('task_id');
            const row = document.querySelector(`[data-task-row="${taskId}"]`);
            if (row) {
                const badge = row.querySelector('[data-status-badge]');
                if (badge && data.status) {
                    badge.textContent = data.status;
                    badge.className = 'status-badge status-' + data.status.replace('_', '-');
                }
                const button = row.querySelector('[data-open-modal="statusModal"]');
                if (button && data.status) {
                    button.dataset.taskStatus = data.status;
                }
            }
            if (statusModal) {
                statusModal.closeModal();
            }
        });
    }

    const filter = document.getElementById('taskFilter');
    if (filter) {
        const rows = Array.from(document.querySelectorAll('#openTasksBody tr'));
        const handle = () => {
            const term = filter.value.trim().toLowerCase();
            rows.forEach((row) => {
                const haystack = row.textContent.toLowerCase();
                row.style.display = haystack.includes(term) ? '' : 'none';
            });
        };
        filter.addEventListener('input', handle);
    }

    const selector = document.getElementById('chartView');
    if (selector) {
        const barViews = document.querySelectorAll('.chart-view');
        const lineViews = document.querySelectorAll('.line-chart-view');
        const hoursViews = document.querySelectorAll('.hours-view');
        const toggle = () => {
            const value = selector.value;
            barViews.forEach((view) => {
                view.classList.toggle('is-hidden', view.dataset.view !== value);
            });
            lineViews.forEach((view) => {
                view.classList.toggle('is-hidden', view.dataset.view !== value);
            });
            hoursViews.forEach((view) => {
                view.classList.toggle('is-hidden', view.dataset.view !== value);
            });
        };
        selector.addEventListener('change', toggle);
        toggle();
    }

    const logModal = setupModal('logModal', (event, modal) => {
        const button = event?.currentTarget;
        const taskId = button?.getAttribute('data-task-id') || '';
        const taskTitle = button?.getAttribute('data-task-title') || '';
        const idInput = modal.querySelector('input[name="task_id"]');
        if (idInput) {
            idInput.value = taskId;
        }
        const titleElement = modal.querySelector('#logTaskTitle');
        if (titleElement) {
            titleElement.textContent = 'Tarea: ' + taskTitle;
        }
        const textarea = modal.querySelector('textarea[name="contenido"]');
        if (textarea) {
            textarea.value = '';
        }
        const errorBox = modal.querySelector('#logError');
        if (errorBox) {
            errorBox.classList.add('is-hidden');
            errorBox.textContent = '';
        }
    });
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layouts/main.php';
