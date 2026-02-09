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
    <h1>Gestión de tarea</h1>
    <button type="button" class="btn">Nueva actividad</button>
    </div>

<!-- Filtros de periodo y equipo -->
<?php
$user = \App\Core\Auth::user();
$roleName = \App\Core\Auth::roleName() ?? '';
$isAdmin = \App\Core\Auth::isAdmin();
$equipos = \App\Models\Team::visibleTeamsForRole($user['id'], $roleName, $isAdmin);
$selectedTeamId = isset($_GET['team_id']) && $_GET['team_id'] !== '' ? (int)$_GET['team_id'] : null;
$selectedPeriodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'mes_actual';
?>
<form method="get" style="margin-bottom: 1.5em; display: flex; gap: 1.5em; align-items: flex-end;">
    <div>
        <label for="periodo" style="font-weight:bold;">Período:</label>
        <select name="periodo" id="periodo" style="padding:0.5em; border:1px solid #ddd; border-radius:4px;">
            <option value="mes_actual"<?= $selectedPeriodo === 'mes_actual' ? ' selected' : '' ?>>Mes actual</option>
            <option value="acumulativo"<?= $selectedPeriodo === 'acumulativo' ? ' selected' : '' ?>>Últimos 6 meses (acumulativo)</option>
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
                            <td><?= htmlspecialchars($collaborator['rol']) ?></td>
                            <td class="table-center"><?= htmlspecialchars((string) $collaborator['total']) ?></td>
                            <td class="table-center"><?= htmlspecialchars((string) $collaborator['terminadas']) ?></td>
                            <td class="table-center"><?= htmlspecialchars((string) ($collaborator['pendientes'] ?? 0)) ?></td>
                            <td class="table-center"><?= htmlspecialchars((string) ($collaborator['atrasadas'] ?? 0)) ?></td>
                            <td class="table-center"><?= htmlspecialchars((string) ($collaborator['encurso'] ?? 0)) ?></td>
                               <td class="table-center" style="font-weight:bold;color:#c00;">
                                   <?= htmlspecialchars((string) ($collaborator['criticas'] ?? 0)) ?>
                               </td>
                            <td class="table-center"><?= htmlspecialchars(number_format((float) $collaborator['cumplimiento'], 1)) ?>%</td>
                            <!-- Celda de acciones eliminada -->
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>


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

<div style="margin-top:3em; padding-top:2em; border-top:2px solid #e0e0e0;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5em;">
        <h2 style="font-size:1.2em; margin:0;">Evolutivo por colaborador</h2>
        <!-- Filtro de período eliminado -->
    </div>
    <div style="display: flex; gap: 2em; flex-wrap: wrap; justify-content: center;">
        <div style="flex: 1 1 90%; min-width: 500px; max-width: 900px; margin: 0 auto;">
            <h3 style="font-size:1em; margin-bottom:0.5em; text-align:center;">Total de tareas</h3>
            <div style="position: relative; height: 350px;">
                <canvas id="chartTareasColaboradores"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const meses = <?= json_encode($months ?? []) ?>;
    const tasksByUser = <?= json_encode($tasksByUserByMonth ?? []) ?>;
    const hoursByUser = <?= json_encode($hoursByUserByMonth ?? []) ?>;
    const tasksByUserCurrentMonth = <?= json_encode($tasksByUserCurrentMonth ?? []) ?>;
    const hoursByUserCurrentMonth = <?= json_encode($hoursByUserCurrentMonth ?? []) ?>;
    const colaboradores = <?= json_encode(array_map(function($col) { 
        return ['id' => $col['id'], 'nombre' => $col['nombre']]; 
    }, $collaboratorStats ?? [])) ?>;

    if (typeof Chart === 'undefined' || !colaboradores || colaboradores.length === 0) {
        return;
    }

    // Colores dinámicos para cada colaborador
    const colors = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', 
        '#858796', '#5a5c69', '#2e59d9', '#17a673', '#2c9faf'
    ];

    let chartTareas = null;

    function prepararDatos(periodo) {
        const colaboradorLabels = [];
        const tareasData = [];

        colaboradores.forEach(col => {
            const userId = col.id;
            let totalTareas = 0;

            if (periodo === 'acumulativo') {
                // Sumar últimos 6 meses
                meses.forEach(mes => {
                    totalTareas += (tasksByUser[userId] && tasksByUser[userId][mes]) ? Number(tasksByUser[userId][mes]) : 0;
                });
            } else {
                // Mes actual
                totalTareas = tasksByUserCurrentMonth[userId] || 0;
            }

            colaboradorLabels.push(col.nombre);
            tareasData.push(totalTareas);
        });

        const backgroundColors = colaboradorLabels.map((_, i) => colors[i % colors.length]);
        const borderColors = backgroundColors;

        // Calcular máximos con margen +3
        const maxTareas = Math.max(...tareasData);
        const maxTareasChart = maxTareas + 3;

        return {
            colaboradorLabels,
            tareasData,
            backgroundColors,
            borderColors,
            maxTareasChart
        };
    }

    function crearGraficos(periodo) {
        const datos = prepararDatos(periodo);

        // Destruir gráfico existente
        if (chartTareas) chartTareas.destroy();

        // Gráfico de Tareas
        const canvasTareas = document.getElementById('chartTareasColaboradores');
        if (canvasTareas) {
            try {
                const ctxTareas = canvasTareas.getContext('2d');
                chartTareas = new Chart(ctxTareas, {
                    type: 'bar',
                    data: {
                        labels: datos.colaboradorLabels,
                        datasets: [{
                            label: 'Total tareas',
                            data: datos.tareasData,
                            backgroundColor: datos.backgroundColors,
                            borderColor: datos.borderColors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: { 
                            legend: { 
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Tareas: ' + context.parsed.x;
                                    }
                                }
                            },
                            datalabels: {
                                display: true,
                                align: 'end',
                                anchor: 'end',
                                color: '#000',
                                font: {
                                    weight: 'bold',
                                    size: 12
                                },
                                formatter: function(value) {
                                    return value;
                                }
                            }
                        },
                        scales: { 
                            x: { 
                                beginAtZero: true,
                                max: datos.maxTareasChart,
                                ticks: {
                                    precision: 0,
                                    stepSize: 1
                                },
                                title: {
                                    display: true,
                                    text: 'Cantidad de tareas'
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'Colaborador'
                                }
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                });
            } catch (error) {
                console.error('Error creando gráfico de tareas:', error);
            }
        }
    }

    // Crear gráficos iniciales
    crearGraficos('acumulativo');

    // Filtro de período eliminado
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
