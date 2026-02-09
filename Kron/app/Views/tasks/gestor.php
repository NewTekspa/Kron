<?php
ob_start();
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
            Gestor de tareas
        </h1>
        <p class="muted" style="margin: 4px 0 0 44px; font-size: 15px;">Bienvenido, <strong style="color: var(--text-medium);"><?= htmlspecialchars($authUser['nombre'] ?? 'Usuario') ?></strong></p>
    </div>
</div>
<div class="hero-actions" style="margin-bottom: 18px;">
    <button type="button" class="btn" data-open-modal="activityModal">Nueva actividad</button>
</div>

<div class="modal" id="activityModal" data-modal>
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="activityModalTitle">
        <div class="modal-header">
            <h2 id="activityModalTitle">Nueva actividad</h2>
            <button type="button" class="btn btn-secondary btn-small" data-close-modal>Cerrar</button>
        </div>
        <form method="post" action="<?= $basePath ?>/tareas/gestor/crear-actividad" class="form">
            <label>Nombre de la actividad</label>
            <input type="text" name="nombre" required>
            <label>Clasificación</label>
            <select name="clasificacion_id" required>
                <option value="">Selecciona una clasificación</option>
                <?php if (!empty($clasificaciones)): ?>
                    <?php foreach ($clasificaciones as $cl): ?>
                        <option value="<?= (int)$cl['id'] ?>"><?= htmlspecialchars($cl['nombre']) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <label>Equipo responsable</label>
            <select name="team_id" required>
                <option value="">Selecciona un equipo</option>
                <?php if (!empty($equipos)): ?>
                    <?php foreach ($equipos as $team): ?>
                        <option value="<?= (int)$team['id'] ?>"<?= (isset($equipoPorDefecto) && $equipoPorDefecto == $team['id'] ? ' selected' : '') ?>><?= htmlspecialchars($team['nombre']) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <div class="form-actions">
                <button type="submit" class="btn">Crear actividad</button>
            </div>
        </form>
    </div>
</div>

<?php
// Indicadores de tareas - calcular totales basados en todas las tareas del usuario
$totalTareas = $totalPendientes = $totalTerminadas = $totalEnCurso = $totalCriticas = $totalAtrasadas = 0;
$totalHoras = 0;
// Obtener total de horas registradas por el usuario
if (class_exists('App\\Models\\Task')) {
    $horasPorUsuario = \App\Models\Task::hoursTotalsByUserIds([$authUserId]);
    $totalHoras = isset($horasPorUsuario[$authUserId]) ? $horasPorUsuario[$authUserId] : 0;
}
$roleName = \App\Core\Auth::roleName() ?? '';
// Obtener todas las tareas del usuario en todas las actividades
if (!empty($activities)) {
    foreach ($activities as $actividad) {
        if (isset($actividad['id'])) {
            $tareas = \App\Models\Task::allForUserByCategory($authUserId, $roleName, $actividad['id']);
            foreach ($tareas as $t) {
                $totalTareas++;
                switch ($t['estado']) {
                    case 'pendiente': $totalPendientes++; break;
                    case 'terminada': $totalTerminadas++; break;
                    case 'en_curso': $totalEnCurso++; break;
                    case 'atrasada': $totalAtrasadas++; break;
                }
                if (isset($t['prioridad']) && $t['prioridad'] === 'critica') {
                    $totalCriticas++;
                }
                if (isset($t['total_horas'])) {
                    $totalHoras += floatval($t['total_horas']);
                }
            }
        }
    }
}
// Formatear horas a HH:MM
$horasMes = 0;
$mesActual = date('Y-m-01');
if (class_exists('App\\Models\\Task')) {
    $horasMesArr = \App\Models\Task::hoursByUserIdsByMonth([$authUserId], $mesActual);
    if (isset($horasMesArr[$authUserId])) {
        // Buscar el mes actual
        $mesKey = date('Y-m-01');
        $horasMes = 0;
        foreach ($horasMesArr[$authUserId] as $mes => $horas) {
            if ($mes === $mesKey) {
                $horasMes = $horas;
                break;
            }
        }
    }
}
$hMes = floor($horasMes);
$mMes = round(($horasMes - $hMes) * 60);
$totalHHMMMMes = sprintf('%02d:%02d', $hMes, $mMes);
$h = floor($totalHoras);
$m = round(($totalHoras - $h) * 60);
$totalHHMM = sprintf('%02d:%02d', $h, $m);
// Calcular porcentaje de cumplimiento
$cumplimiento = ($totalTareas > 0) ? round(($totalTerminadas / $totalTareas) * 100, 1) : 0;
?>

<style>
.summary-cards {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 16px;
    margin-bottom: 24px;
    justify-content: space-between;
    align-items: stretch;
}
.summary-card-mini {
    flex: 1 1 0;
    min-width: 120px;
    max-width: 200px;
}
.summary-card-mini {
    background: white;
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    text-align: center;
}
.summary-card-mini h3 {
    margin: 0 0 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.5px;
}
.summary-card-mini .value {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    color: #1e293b;
}
.summary-card-mini .subtitle {
    margin: 4px 0 0;
    font-size: 11px;
    color: #94a3b8;
}
</style>

<div class="summary-cards">
    <div class="summary-card-mini">
        <h3>Críticas</h3>
        <p class="value"><?= $totalCriticas ?></p>
    </div>
    <div class="summary-card-mini">
        <h3>Tareas</h3>
        <p class="value"><?= $totalTareas ?></p>
    </div>
    <div class="summary-card-mini">
        <h3>Terminadas</h3>
        <p class="value"><?= $totalTerminadas ?></p>
    </div>
    <div class="summary-card-mini">
        <h3>Pendientes</h3>
        <p class="value"><?= $totalPendientes ?></p>
    </div>
    <div class="summary-card-mini">
        <h3>Atrasadas</h3>
        <p class="value"><?= $totalAtrasadas ?></p>
    </div>
    <div class="summary-card-mini">
        <h3>En curso</h3>
        <p class="value"><?= $totalEnCurso ?></p>
    </div>
    <div class="summary-card-mini">
        <h3>Cumplimiento</h3>
        <p class="value"><?= $cumplimiento ?>%</p>
    </div>
    <div class="summary-card-mini">
        <h3>Horas mes</h3>
        <p class="value"><?= $totalHHMMMMes ?> h</p>
    </div>
</div>


</div>

<div class="dashboard-top">
    <div class="dashboard-col" style="flex: 2 1 0; min-width: 1200px; max-width: 1800px; margin: 0 auto;">
        <section class="card-block dashboard-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: var(--shadow); min-height: 420px;">
            <div class="card-header">
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                    <div>
                        <h2 style="margin: 0 0 4px;">Mis actividades</h2>
                        <p class="muted" style="margin: 0; font-size: 13px;">📋 Actividades donde participas</p>
                    </div>
                </div>
            </div>
            <div class="table-wrap" style="margin-top: 24px;">
                <table class="table table-compact">
                    <thead>
                        <tr>
                            <th style="min-width:220px;">Actividad</th>
                            <th style="min-width:90px;">Clasificación</th>
                            <th class="table-center" style="min-width:45px;">Total</th>
                            <th class="table-center" style="min-width:45px;">Pend.</th>
                            <th class="table-center" style="min-width:45px;">Term.</th>
                            <th class="table-center" style="min-width:60px;">%</th>
                            <th class="table-center" style="min-width:45px;">Crít.</th>
                            <th class="table-center" style="min-width:45px;">Atr.</th>
                            <th class="table-center" style="min-width:70px;">Horas</th>
                            <th class="table-center" style="min-width:110px;">Estado</th>
                            <th class="table-center" style="min-width:70px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($activities)): ?>
                            <?php foreach ($activities as $actividad): ?>
                                <?php
                                // Obtener tareas de la actividad
                                $tareas = [];
                                if (isset($actividad['id'])) {
                                    $tareas = \App\Models\Task::allForUserByCategory($authUser['id'], $roleName ?? '', $actividad['id']);
                                }
                                $pendientes = $terminadas = $enCurso = $criticas = $atrasadas = $totalHoras = 0;
                                foreach ($tareas as $t) {
                                    switch ($t['estado']) {
                                        case 'pendiente': $pendientes++; break;
                                        case 'terminada': $terminadas++; break;
                                        case 'en_curso': $enCurso++; break;
                                        case 'atrasada': $atrasadas++; break;
                                    }
                                    if (isset($t['prioridad']) && $t['prioridad'] === 'critica') {
                                        $criticas++;
                                    }
                                    if (isset($t['total_horas'])) {
                                        $totalHoras += floatval($t['total_horas']);
                                    }
                                }
                                $pendientesTotal = $pendientes + $enCurso;
                                $h = floor($totalHoras);
                                $m = round(($totalHoras - $h) * 60);
                                $totalHHMM = sprintf('%02d:%02d', $h, $m);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($actividad['nombre']) ?></td>
                                    <td><?= htmlspecialchars($actividad['clasificacion_nombre'] ?? '-') ?></td>
                                    <td class="table-center"><?= (int)$actividad['total_tareas'] ?></td>
                                    <td class="table-center"><?= $pendientesTotal ?></td>
                                    <td class="table-center"><?= $terminadas ?></td>
                                    <td class="table-center">
                                        <?php
                                        $porcentaje = ($actividad['total_tareas'] > 0) ? round(($terminadas / $actividad['total_tareas']) * 100) : 0;
                                        ?>
                                        <?= $porcentaje ?>%
                                    </td>
                                    <td class="table-center"><?= $criticas ?></td>
                                    <td class="table-center"><?= $atrasadas ?></td>
                                    <td class="table-center"><?= $totalHHMM ?> h</td>
                                    <td class="table-center">
                                        <?= htmlspecialchars($actividad['estado_actividad'] ?? '-') ?>
                                    </td>
                                    <td class="table-center">
                                        <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                            <a href="<?= $basePath ?>/tareas/actividad?category_id=<?= (int)$actividad['id'] ?>" class="btn btn-small btn-icon" title="Ver tareas" aria-label="Ver tareas">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="3" y="4" width="18" height="16" rx="2"/>
                                                    <line x1="8" y1="8" x2="16" y2="8"/>
                                                    <line x1="8" y1="12" x2="16" y2="12"/>
                                                    <line x1="8" y1="16" x2="16" y2="16"/>
                                                </svg>
                                            </a>
                                            <button type="button" class="btn btn-small btn-icon" title="Clonar actividad" aria-label="Clonar actividad" onclick="openCloneModal(<?= (int)$actividad['id'] ?>, '<?= htmlspecialchars(addslashes($actividad['nombre'])) ?>')">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="8" y="8" width="8" height="8" rx="2"/>
                                                    <rect x="4" y="4" width="8" height="8" rx="2"/>
                                                </svg>
                                            </button>
                                            <button type="button" class="btn btn-small btn-icon" title="Eliminar actividad" aria-label="Eliminar actividad" onclick="confirmDelete(<?= (int)$actividad['id'] ?>, '<?= htmlspecialchars(addslashes($actividad['nombre'])) ?>')" style="color: #e74c3c;">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="muted">No tienes actividades asignadas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<!-- Modal para clonar actividad -->
<div class="modal" id="cloneActivityModal" data-modal style="display:none;">
    <div class="modal-overlay" data-close-modal onclick="closeCloneModal()"></div>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="cloneActivityModalTitle">
        <div class="modal-header">
            <h2 id="cloneActivityModalTitle">Clonar actividad</h2>
            <button type="button" class="btn btn-secondary btn-small" data-close-modal onclick="closeCloneModal()">Cerrar</button>
        </div>
        <form method="post" action="<?= $basePath ?>/tareas/gestor/clonar-actividad" class="form">
            <input type="hidden" name="source_category_id" id="clone_origen_id">
            <label>Nuevo nombre para la actividad</label>
            <input type="text" name="new_category_name" id="clone_nuevo_nombre" required>
            <div class="form-actions">
                <button type="submit" class="btn">Clonar</button>
            </div>
        </form>
    </div>
</div>

<form id="deleteActivityForm" method="post" action="<?= $basePath ?>/tareas/gestor/eliminar-actividad" style="display:none;">
    <input type="hidden" name="category_id" id="delete_category_id">
</form>

<script>
function openCloneModal(id, nombre) {
    document.getElementById('clone_origen_id').value = id;
    document.getElementById('clone_nuevo_nombre').value = nombre + ' (Copia)';
    document.getElementById('cloneActivityModal').style.display = 'block';
}
function closeCloneModal() {
    document.getElementById('cloneActivityModal').style.display = 'none';
}
function confirmDelete(id, nombre) {
    if (confirm('¿Estás seguro de eliminar la actividad "' + nombre + '"?\n\nEsto eliminará todas las tareas, registros de tiempo y logs asociados.\n\nEsta acción NO se puede deshacer.')) {
        document.getElementById('delete_category_id').value = id;
        document.getElementById('deleteActivityForm').submit();
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
