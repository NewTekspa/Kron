<?php
/** @var string $title */
/** @var array $teamStats */
/** @var array $collaboratorStats */
/** @var string $monthLabel */
/** @var int $totalTasks */
/** @var int $totalCompleted */
/** @var float $totalHours */
/** @var float $completionRate */
/** @var string $content */
ob_start();
?>
<div class="page-header">
	<h1>Seguimiento de Equipo</h1>
	</div>

<!-- Filtros de periodo y equipo -->
<?php
$user = \App\Core\Auth::user();
$roleName = \App\Core\Auth::roleName() ?? '';
$isAdmin = \App\Core\Auth::isAdmin();
$equipos = \App\Models\Team::visibleTeamsForRole($user['id'], $roleName, $isAdmin);
$selectedTeamId = isset($_GET['team_id']) && $_GET['team_id'] !== '' ? (int)$_GET['team_id'] : null;
$selectedPeriod = isset($_GET['periodo']) ? $_GET['periodo'] : 'todos';
?>
<form method="get" style="margin-bottom: 1.5em; display: flex; gap: 1.5em; align-items: flex-end;">
    <div>
        <label for="periodo" style="font-weight:bold;">Mes:</label>
        <select name="periodo" id="periodo" style="padding:0.5em; border:1px solid #ddd; border-radius:4px; min-width: 180px;">
            <option value="todos"<?= $selectedPeriod === 'todos' ? ' selected' : '' ?>>Todos (Acumulado)</option>
            <?php if (!empty($availableMonths)): ?>
                <?php foreach ($availableMonths as $mes): ?>
                    <?php 
                        $mesLabel = (new \DateTimeImmutable($mes . '-01'))->format('F Y');
                        $mesLabelEs = str_replace(
                            ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                            ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                            $mesLabel
                        );
                    ?>
                    <option value="<?= htmlspecialchars($mes) ?>"<?= $selectedPeriod === $mes ? ' selected' : '' ?>><?= htmlspecialchars($mesLabelEs) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>
    <div>
        <label for="team_id" style="font-weight:bold;">Equipo:</label>
        <select name="team_id" id="team_id" style="padding:0.5em; border:1px solid #ddd; border-radius:4px; min-width: 180px;">
            <option value="">Todos los equipos</option>
            <?php foreach ($equipos as $equipo): ?>
                <option value="<?= (int)$equipo['id'] ?>"<?= $selectedTeamId == $equipo['id'] ? ' selected' : '' ?>><?= htmlspecialchars($equipo['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn" style="margin-bottom:0;">Filtrar</button>
</form>

<?php
// Calcular totales solo para colaboradores, filtrando por equipo si corresponde
$totalPendientes = 0;
$totalAtrasadas = 0;
$totalEnCurso = 0;
$totalTasksFicha = 0;
$totalCompletedFicha = 0;
$totalHoursFicha = 0.0;
$totalRateSum = 0.0;
$totalRateCount = 0;
$selectedTeamId = isset($_GET['team_id']) && $_GET['team_id'] !== '' ? (int)$_GET['team_id'] : null;
$filteredCollaborators = [];
if (!empty($collaboratorStats)) {
    foreach ($collaboratorStats as $col) {
        if ($selectedTeamId) {
            if (isset($col['team_ids']) && is_array($col['team_ids']) && !in_array($selectedTeamId, $col['team_ids'], true)) {
                continue;
            }
        }
        $filteredCollaborators[] = $col;
        $totalPendientes += $col['pendientes'] ?? 0;
        $totalAtrasadas += $col['atrasadas'] ?? 0;
        $totalEnCurso += $col['encurso'] ?? 0;
        $totalTasksFicha += $col['total'] ?? 0;
        $totalCompletedFicha += $col['terminadas'] ?? 0;
        $totalHoursFicha += $col['horas'] ?? 0;
        $totalRateSum += $col['cumplimiento'] ?? 0;
        $totalRateCount++;
    }
}
$completionRateFicha = $totalRateCount > 0 ? round($totalRateSum / $totalRateCount, 1) : 0;

// Preparar datos para el gráfico de horas por colaborador (últimos 6 meses)
$chartLabels = $months ?? [];
$chartLabels = array_reverse($chartLabels); // Invertir para mostrar de menor a mayor
$chartData = [];
$chartNames = [];
if (!empty($filteredCollaborators) && !empty($hoursByUserByMonth)) {
    foreach ($filteredCollaborators as $col) {
        $colId = (int)$col['id'];
        $chartNames[] = $col['nombre'];
        $data = [];
        foreach ($chartLabels as $month) { // Usar chartLabels ya invertido
            $data[] = isset($hoursByUserByMonth[$colId][$month]) ? (float)$hoursByUserByMonth[$colId][$month] : 0.0;
        }
        $chartData[] = $data;
    }
}
?>
<div class="summary-grid" style="display:flex;gap:1em;flex-wrap:nowrap;justify-content:space-between;align-items:flex-start;margin-bottom:0.3em;overflow-x:auto;">
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;background:#ffeaea;border:1px solid #c00;color:#c00;padding:0.5em 0;">
        <h3 style="font-size:0.85em;margin:0;line-height:1;">CRÍTICAS</h3>
        <p style="font-size:1.1em;margin:0;line-height:1.2;white-space:nowrap;"><?= htmlspecialchars((string) $totalCritical) ?></p>
    </div>
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;padding:0.5em 0;">
        <h3 style="font-size:0.85em;margin:0;line-height:1;">TAREAS</h3>
        <p style="font-size:1.1em;margin:0;line-height:1.2;white-space:nowrap;"><?= htmlspecialchars((string) $totalTasksFicha) ?></p>
    </div>
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;padding:0.5em 0;">
        <h3 style="font-size:0.85em;margin:0;line-height:1;">TERMINADAS</h3>
        <p style="font-size:1.1em;margin:0;line-height:1.2;white-space:nowrap;"><?= htmlspecialchars((string) $totalCompletedFicha) ?></p>
    </div>
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;padding:0.5em 0;">
        <h3 style="font-size:0.85em;margin:0;line-height:1;">PENDIENTES</h3>
        <p style="font-size:1.1em;margin:0;line-height:1.2;white-space:nowrap;"><?= htmlspecialchars((string) $totalPendientes) ?></p>
    </div>
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;padding:0.5em 0;">
        <h3 style="font-size:0.85em;margin:0;line-height:1;">ATRASADAS</h3>
        <p style="font-size:1.1em;margin:0;line-height:1.2;white-space:nowrap;"><?= htmlspecialchars((string) $totalAtrasadas) ?></p>
    </div>
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;padding:0.5em 0;">
        <h3 style="font-size:0.85em;margin:0;line-height:1;">EN CURSO</h3>
        <p style="font-size:1.1em;margin:0;line-height:1.2;white-space:nowrap;"><?= htmlspecialchars((string) $totalEnCurso) ?></p>
    </div>
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;padding:0.5em 0;">
        <h3 style="font-size:0.85em;margin:0;line-height:1;">CUMPLIMIENTO</h3>
        <p style="font-size:1.1em;margin:0;line-height:1.2;white-space:nowrap;"><?= htmlspecialchars(number_format($completionRateFicha, 1)) ?>%</p>
    </div>
    <!-- Línea de tareas -->
</div>

<section class="card-block">
    <div class="card-header">
        <h2>Resumen</h2>
    </div>
    <div class="table-wrap">
        <table class="table table-compact">
            <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>Rol</th>
                    <th class="table-center">Tareas</th>
                    <th class="table-center">Terminadas</th>
                    <th class="table-center">Pendientes</th>
                    <th class="table-center">Atrasadas</th>
                    <th class="table-center">En curso</th>
                       <th class="table-center" style="color:#c00;">Críticas</th>
                    <th class="table-center">Cumplimiento</th>
                    <th class="table-center">Total de horas</th>
                    <th class="table-center">Ver tareas</th>
                    <!-- Columna de acciones eliminada -->
                </tr>
            </thead>
            <tbody>
                <?php if (empty($filteredCollaborators)): ?>
                    <tr>
                        <td colspan="6" class="muted">No hay colaboradores disponibles.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($filteredCollaborators as $collaborator): ?>
                        <tr>
                            <td><?= htmlspecialchars($collaborator['nombre']) ?></td>
                            <td><?= htmlspecialchars(ucfirst(strtolower($collaborator['rol']))) ?></td>
                            <td class="table-center"><?= htmlspecialchars((string) $collaborator['total']) ?></td>
                            <td class="table-center"><?= htmlspecialchars((string) $collaborator['terminadas']) ?></td>
                            <td class="table-center"><?= htmlspecialchars((string) ($collaborator['pendientes'] ?? 0)) ?></td>
                            <td class="table-center"><?= htmlspecialchars((string) ($collaborator['atrasadas'] ?? 0)) ?></td>
                            <td class="table-center"><?= htmlspecialchars((string) ($collaborator['encurso'] ?? 0)) ?></td>
                               <td class="table-center" style="font-weight:bold;color:#c00;">
                                   <?= htmlspecialchars((string) ($collaborator['criticas'] ?? 0)) ?>
                               </td>
                            <td class="table-center"><?= htmlspecialchars(number_format((float) $collaborator['cumplimiento'], 1)) ?>%</td>
                            <td class="table-center" style="font-size:1em; font-weight:bold;">
                                <?= htmlspecialchars(number_format((float) $collaborator['horas'], 1)) ?> h
                            </td>
                            <td class="table-center">
                                <a href="<?= $basePath ?>/tareas/colaborador-tareas?colaborador_id=<?= (int)$collaborator['id'] ?>&return_url=<?= urlencode($_SERVER['REQUEST_URI']) ?>" title="Ver tareas asignadas" class="btn btn-small btn-icon" style="padding:4px 8px;">
                                    <img src="<?= $basePath ?>/assets/icons/eye.svg" alt="Ver tareas" width="18" height="18" />
                                </a>
                            </td>
                            <!-- Celda de acciones eliminada -->
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Gráfico de horas por colaborador (últimos 6 meses) -->
<div style="margin-top:2em; background:white; padding:1em; border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
    <canvas id="horasColaboradoresChart" height="80"></canvas>
</div>


<script>
(() => {
    const selector = document.getElementById('productivityView');
    if (!selector) {
        return;
    }
    const panels = document.querySelectorAll('.view-panel');
    const toggle = () => {
        const value = selector.value;
        panels.forEach((panel) => {
            panel.classList.toggle('is-hidden', panel.dataset.view !== value);
        });
    };
    selector.addEventListener('change', toggle);
    toggle();
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Gráfico de horas por colaborador (últimos 6 meses)
document.addEventListener('DOMContentLoaded', function() {
    // Esperar a que Chart.js esté disponible
    const initChart = () => {
        if (!window.Chart) {
            setTimeout(initChart, 100);
            return;
        }
        
        const ctx = document.getElementById('horasColaboradoresChart');
        if (!ctx) return;
        
        const chartLabels = <?= json_encode($chartLabels) ?>;
        const chartData = <?= json_encode($chartData) ?>;
        const chartNames = <?= json_encode($chartNames) ?>;
        
        const datasets = chartData.map((data, idx) => ({
            label: chartNames[idx],
            data,
            fill: false,
            borderColor: `hsl(${(idx * 60) % 360}, 70%, 50%)`,
            backgroundColor: `hsl(${(idx * 60) % 360}, 70%, 50%)`,
            tension: 0.2
        }));
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Total de horas por colaborador (últimos 6 meses)' }
                },
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Horas' } },
                    x: { title: { display: true, text: 'Mes' } }
                }
            }
        });
    };
    
    initChart();
});
</script>



<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
