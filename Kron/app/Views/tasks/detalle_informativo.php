<?php
/**
 * Página informativa de detalle de tarea
 * Muestra todos los datos relevantes de la tarea sin botones ni acciones
 */
/** @var string $title */
/** @var array $tarea */
/** @var array $asignado */
/** @var array $categoria */
/** @var array $clasificacion */
/** @var array $equipo */
/** @var array $horas */
/** @var array $logs */
ob_start();
$formatDate = function (?string $value): string {
    if (! $value) return '-';
    $timestamp = strtotime($value);
    return $timestamp ? date('d-m-Y', $timestamp) : $value;
};
$formatHours = function ($value): string {
    $decimal = (float) $value;
    $hours = (int) floor($decimal);
    $minutes = (int) round(($decimal - $hours) * 60);
    if ($minutes === 60) { $hours += 1; $minutes = 0; }
    return sprintf('%02d:%02d', $hours, $minutes);
};
$statusLabels = [
    'pendiente' => 'Pendiente',
    'en_curso' => 'En curso',
    'atrasada' => 'Atrasada',
    'congelada' => 'Congelada',
    'terminada' => 'Terminada',
];
?>
<?php
$returnUrl = $_GET['return'] ?? ($_SERVER['HTTP_REFERER'] ?? '/');
?>
<div style="margin: 16px 0 24px 0; text-align: right;">
    <a href="<?= htmlspecialchars($returnUrl) ?>" class="btn btn-secondary">← Volver</a>
</div>
<div class="page-header">
    <h1>Detalle informativo de tarea</h1>
</div>
<div class="card-block" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: var(--shadow);">
    <h2 style="margin-bottom: 20px; color: var(--blue);">Información general</h2>
    <table class="table">
        <tbody>
            <tr>
                <th style="width: 200px;">Título</th>
                <td><?= htmlspecialchars($tarea['titulo'] ?? '-') ?></td>
            </tr>
            <tr>
                <th>Actividad</th>
                <td><?= htmlspecialchars($categoria['nombre'] ?? '-') ?></td>
            </tr>
            <tr>
                <th>Clasificación</th>
                <td><?= htmlspecialchars($clasificacion['nombre'] ?? '-') ?></td>
            </tr>
            <tr>
                <th>Equipo</th>
                <td><?= htmlspecialchars($equipo['nombre'] ?? '-') ?></td>
            </tr>
            <tr>
                <th>Asignado</th>
                <td><?= htmlspecialchars($asignado['nombre'] ?? '-') ?></td>
            </tr>
            <tr>
                <th>Prioridad</th>
                <td><?= htmlspecialchars(ucfirst($tarea['prioridad'] ?? '-')) ?></td>
            </tr>
            <tr>
                <th>Estado</th>
                <td>
                    <?php $statusKey = str_replace('_', '-', $tarea['estado'] ?? 'pendiente'); ?>
                    <span class="status-badge status-<?= htmlspecialchars($statusKey) ?>">
                        <?= htmlspecialchars($statusLabels[$tarea['estado']] ?? $tarea['estado'] ?? '-') ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Fecha compromiso</th>
                <td><?= htmlspecialchars($formatDate($tarea['fecha_compromiso'] ?? null)) ?></td>
            </tr>
            <tr>
                <th>Fecha término real</th>
                <td><?= htmlspecialchars($formatDate($tarea['fecha_termino_real'] ?? null)) ?></td>
            </tr>
            <tr>
                <th>Total de horas</th>
                <td><strong><?= htmlspecialchars($formatHours($tarea['total_horas'] ?? 0)) ?></strong></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="card-block" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: var(--shadow); margin-top: 24px;">
    <h2 style="margin-bottom: 20px; color: var(--blue);">📊 Historial de horas</h2>
    <?php if (empty($horas)): ?>
        <p class="muted">No hay horas registradas para esta tarea.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Horas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($horas as $h): ?>
                    <tr>
                        <td><?= htmlspecialchars($formatDate($h['fecha'])) ?></td>
                        <td><?= htmlspecialchars($formatHours($h['horas'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card-block" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: var(--shadow); margin-top: 24px;">
    <h2 style="margin-bottom: 20px; color: var(--blue);">📝 Bitácora</h2>
    <?php if (empty($logs)): ?>
        <p class="muted">No hay registros en la bitácora.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Mensaje</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($formatDate($log['created_at'] ?? null)) ?></td>
                        <td><?= htmlspecialchars($log['contenido'] ?? '-') ?></td>
                        <td>
                            <form method="post" action="/tareas/bitacora/eliminar" style="display:inline;" onsubmit="return confirm('¿Eliminar este registro de bitácora?');">
                                <input type="hidden" name="log_id" value="<?= (int)$log['id'] ?>">
                                <input type="hidden" name="task_id" value="<?= (int)$tarea['id'] ?>">
                                <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                                <button type="submit" class="btn btn-danger btn-sm" title="Eliminar registro">
                                    🗑️
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
