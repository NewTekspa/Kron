
<?php
/** @var string $title */
/** @var array $tasks */
/** @var array $statusLabels */
/** @var callable $formatDate */
/** @var callable $formatHours */
ob_start();

$total = count($tasks);
$terminadas = 0;
$atrasadas = 0;
$pendientes = 0;
$en_curso = 0;
$criticas = 0;
$horas = 0;
$sin_fecha = 0;
foreach ($tasks as $t) {
    if (($t['estado'] ?? '') === 'terminada') $terminadas++;
    if (($t['estado'] ?? '') === 'atrasada') $atrasadas++;
    if (($t['estado'] ?? '') === 'pendiente') $pendientes++;
    if (($t['estado'] ?? '') === 'en_curso') $en_curso++;
    if (($t['prioridad'] ?? '') === 'critica') $criticas++;
    $horas += isset($t['total_horas']) ? (float)$t['total_horas'] : (isset($t['horas']) ? (float)$t['horas'] : 0);
    if (empty($t['fecha_compromiso'])) $sin_fecha++;
}
?>
<div class="page-header">
    <h1>Listado de tareas del colaborador</h1>
    <a href="<?= $basePath ?>/tareas/gestion?vista=teams" class="btn btn-secondary">Volver</a>
</div>

<div class="summary-grid" style="display:flex;gap:1.5em;flex-wrap:wrap;justify-content:space-between;align-items:stretch;margin-bottom:2em;">
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;">
        <h3>Total tareas</h3>
        <p><?= $total ?></p>
    </div>
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;">
        <h3>Terminadas</h3>
        <p><?= $terminadas ?></p>
    </div>
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;">
        <h3>En curso</h3>
        <p><?= $en_curso ?></p>
    </div>
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;">
        <h3>Atrasadas</h3>
        <p><?= $atrasadas ?></p>
    </div>
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;">
        <h3>Pendientes</h3>
        <p><?= $pendientes ?></p>
    </div>
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;">
        <h3>Críticas</h3>
        <p style="color:#e74a3b;font-weight:bold;"><?= $criticas ?></p>
    </div>
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;">
        <h3>Total horas</h3>
        <p><?= number_format($horas, 1) ?> h</p>
    </div>
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;">
        <h3>Sin fecha compromiso</h3>
        <p><?= $sin_fecha ?></p>
    </div>
</div>


<form method="get" style="margin-bottom:1em;display:flex;gap:1em;align-items:end;flex-wrap:wrap;">
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($_GET['user_id'] ?? '') ?>">
    <input type="hidden" name="mes" value="<?= htmlspecialchars($_GET['mes'] ?? '') ?>">
    <div>
        <label for="filtro_mes">Mes:</label>
        <select id="filtro_mes" name="filtro_mes">
            <option value="todos"<?= (($filtroMes ?? '') === 'todos' ? ' selected' : '') ?>>Todo el tiempo</option>
            <?php foreach ($mesesDisponibles as $mesOpt): ?>
                <option value="<?= htmlspecialchars($mesOpt) ?>"
                    <?= (($filtroMes ?? '') === $mesOpt ? ' selected' : '') ?>>
                    <?= htmlspecialchars($mesOpt) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="buscar">Buscar:</label>
        <input type="text" id="buscar" name="buscar" value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>" placeholder="Título, categoría, estado...">
    </div>
    <div>
        <label for="filtro_estado">Ver:</label>
        <select id="filtro_estado" name="filtro_estado">
            <option value="todas"<?= (($_GET['filtro_estado'] ?? 'todas') === 'todas' ? ' selected' : '') ?>>Todas</option>
            <option value="no_terminadas"<?= (($_GET['filtro_estado'] ?? '') === 'no_terminadas' ? ' selected' : '') ?>>No terminadas</option>
        </select>
    </div>
    <div>
        <button type="submit" class="btn">Filtrar</button>
    </div>
</form>

<?php
$buscar = strtolower(trim($_GET['buscar'] ?? ''));
// --- Ordenamiento y filtrado con íconos ---
$ordenar = $_GET['ordenar'] ?? 'fecha_compromiso';
$dir = $_GET['dir'] ?? 'asc';
$toggleDir = function($col) use ($ordenar, $dir) {
    return ($ordenar === $col && $dir === 'asc') ? 'desc' : 'asc';
};
$icono = function($col) use ($ordenar, $dir) {
    if ($ordenar !== $col) return '';
    return $dir === 'asc' ? '▲' : '▼';
};
// Filtro de estado: todas o no terminadas
$filtroEstado = $_GET['filtro_estado'] ?? 'todas';
$filtered = $tasks;
if ($buscar !== '') {
    $filtered = array_filter($filtered, function($t) use ($buscar, $statusLabels) {
        $text = strtolower(
            ($t['titulo'] ?? '') . ' ' .
            ($t['categoria_nombre'] ?? '') . ' ' .
            ($t['clasificacion_nombre'] ?? '') . ' ' .
            ($t['estado'] ?? '') . ' ' .
            ($statusLabels[$t['estado']] ?? '')
        );
        return strpos($text, $buscar) !== false;
    });
}
if ($filtroEstado === 'no_terminadas') {
    $filtered = array_filter($filtered, function($t) {
        return $t['estado'] !== 'terminada';
    });
}
usort($filtered, function($a, $b) use ($ordenar, $dir) {
    $av = $a[$ordenar] ?? '';
    $bv = $b[$ordenar] ?? '';
    if ($ordenar === 'horas' || $ordenar === 'total_horas') {
        $av = (float)($a['total_horas'] ?? $a['horas'] ?? 0);
        $bv = (float)($b['total_horas'] ?? $b['horas'] ?? 0);
    }
    if ($dir === 'desc') {
        return $bv <=> $av;
    }
    return $av <=> $bv;
});
?>

<table class="table table-compact">
    <thead>
        <tr>
            <th>
                <a href="?<?= http_build_query(array_merge($_GET, ['ordenar'=>'titulo','dir'=>$toggleDir('titulo')])) ?>">Título <?= $icono('titulo') ?></a>
            </th>
            <th>
                <a href="?<?= http_build_query(array_merge($_GET, ['ordenar'=>'estado','dir'=>$toggleDir('estado')])) ?>">Estado <?= $icono('estado') ?></a>
            </th>
            <th>
                <a href="?<?= http_build_query(array_merge($_GET, ['ordenar'=>'categoria_nombre','dir'=>$toggleDir('categoria_nombre')])) ?>">Categoría <?= $icono('categoria_nombre') ?></a>
            </th>
            <th>
                <a href="?<?= http_build_query(array_merge($_GET, ['ordenar'=>'clasificacion_nombre','dir'=>$toggleDir('clasificacion_nombre')])) ?>">Clasificación <?= $icono('clasificacion_nombre') ?></a>
            </th>
            <th>
                <a href="?<?= http_build_query(array_merge($_GET, ['ordenar'=>'fecha_compromiso','dir'=>$toggleDir('fecha_compromiso')])) ?>">Fecha compromiso <?= $icono('fecha_compromiso') ?></a>
            </th>
            <th>
                <a href="?<?= http_build_query(array_merge($_GET, ['ordenar'=>'fecha_termino_real','dir'=>$toggleDir('fecha_termino_real')])) ?>">Fecha término <?= $icono('fecha_termino_real') ?></a>
            </th>
            <th>
                <a href="?<?= http_build_query(array_merge($_GET, ['ordenar'=>'total_horas','dir'=>$toggleDir('total_horas')])) ?>">Horas <?= $icono('total_horas') ?></a>
            </th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($filtered)): ?>
            <tr><td colspan="7" class="muted">No hay tareas para mostrar.</td></tr>
        <?php else: ?>
            <?php foreach ($filtered as $task): ?>
                <tr>
                    <td><?= htmlspecialchars($task['titulo']) ?></td>
                    <td><?= htmlspecialchars($statusLabels[$task['estado']] ?? $task['estado']) ?></td>
                    <td><?= htmlspecialchars($task['categoria_nombre'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($task['clasificacion_nombre'] ?? '-') ?></td>
                    <td><?= $formatDate($task['fecha_compromiso'] ?? null) ?></td>
                    <td><?= $formatDate($task['fecha_termino_real'] ?? null) ?></td>
                    <td><?= $formatHours($task['total_horas'] ?? ($task['horas'] ?? 0)) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<div style="margin-top:3em;">
    <h2 style="font-size:1.2em; margin-bottom:1em; text-align:center;">Evolutivo (últimos 6 meses)</h2>
    <div style="display: flex; gap: 2em; flex-wrap: wrap; justify-content: center;">
        <div style="flex: 1 1 45%; min-width: 400px; max-width: 550px;">
            <h3 style="font-size:1em; margin-bottom:0.5em; text-align:center;">Tareas</h3>
            <div style="position: relative; height: 300px;">
                <canvas id="chartTareas"></canvas>
            </div>
        </div>
        <div style="flex: 1 1 45%; min-width: 400px; max-width: 550px;">
            <h3 style="font-size:1em; margin-bottom:0.5em; text-align:center;">Horas</h3>
            <div style="position: relative; height: 300px;">
                <canvas id="chartHoras"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const meses = <?= json_encode($months ?? []) ?>;
    const horas = <?= json_encode($hoursByMonth ?? []) ?>;
    const tareas = <?= json_encode($tasksByMonth ?? []) ?>;

    if (typeof Chart === 'undefined') {
        return;
    }

    let horasData = [];
    let tareasData = [];
    if (Array.isArray(meses) && meses.length > 0) {
        meses.forEach(mes => {
            horasData.push(Number(horas[mes]) || 0);
            tareasData.push(Number(tareas[mes]) || 0);
        });
    } else {
        return;
    }

    // Calcular máximo de horas + 3
    const maxHoras = Math.max(...horasData);
    const maxHorasChart = maxHoras + 3;
    
    // Calcular máximo de tareas + margen
    const maxTareas = Math.max(...tareasData);
    const maxTareasChart = Math.ceil(maxTareas * 1.2); // 20% más

    // Gráfico de Tareas
    const canvasTareas = document.getElementById('chartTareas');
    if (canvasTareas) {
        try {
            const ctxTareas = canvasTareas.getContext('2d');
            new Chart(ctxTareas, {
                type: 'line',
                data: {
                    labels: meses,
                    datasets: [{
                        label: 'Tareas',
                        data: tareasData,
                        backgroundColor: 'rgba(78, 115, 223, 0.2)',
                        borderColor: '#4e73df',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#4e73df',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        datalabels: {
                            align: 'top',
                            anchor: 'end',
                            backgroundColor: 'rgba(78, 115, 223, 0.8)',
                            borderRadius: 4,
                            color: 'white',
                            font: {
                                weight: 'bold',
                                size: 11
                            },
                            padding: 4
                        }
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { 
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Tareas: ' + context.parsed.y;
                                }
                            }
                        },
                        datalabels: {
                            display: true,
                            formatter: function(value) {
                                return value;
                            }
                        }
                    },
                    scales: { 
                        y: { 
                            beginAtZero: true,
                            max: maxTareasChart,
                            ticks: {
                                precision: 0,
                                stepSize: 1
                            },
                            title: {
                                display: true,
                                text: 'Cantidad'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Mes'
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        } catch (error) {
            // Error silencioso
        }
    }

    // Gráfico de Horas
    const canvasHoras = document.getElementById('chartHoras');
    if (canvasHoras) {
        try {
            const ctxHoras = canvasHoras.getContext('2d');
            new Chart(ctxHoras, {
                type: 'line',
                data: {
                    labels: meses,
                    datasets: [{
                        label: 'Horas registradas',
                        data: horasData,
                        backgroundColor: 'rgba(28, 200, 138, 0.2)',
                        borderColor: '#1cc88a',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#1cc88a',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        datalabels: {
                            align: 'top',
                            anchor: 'end',
                            backgroundColor: 'rgba(28, 200, 138, 0.8)',
                            borderRadius: 4,
                            color: 'white',
                            font: {
                                weight: 'bold',
                                size: 11
                            },
                            padding: 4
                        }
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { 
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Horas: ' + context.parsed.y.toFixed(1);
                                }
                            }
                        },
                        datalabels: {
                            display: true,
                            formatter: function(value) {
                                return value.toFixed(1);
                            }
                        }
                    },
                    scales: { 
                        y: { 
                            beginAtZero: true,
                            max: maxHorasChart,
                            ticks: {
                                precision: 1
                            },
                            title: {
                                display: true,
                                text: 'Horas'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Mes'
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        } catch (error) {
            // Error silencioso
        }
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
