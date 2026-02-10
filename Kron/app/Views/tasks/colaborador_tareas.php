<?php
/**
 * Vista: Panel de tareas asignadas a un colaborador (solo lectura para jefes)
 * Variables esperadas:
 * - $colaborador: array con datos del usuario
 * - $tareas: array de tareas asignadas
 * - $basePath: ruta base
 * - $returnUrl: url para volver
 */
ob_start();

$estados = [
    'todos' => 'Todos',
    'pendiente' => 'Pendiente',
    'en_curso' => 'En curso',
    'atrasada' => 'Atrasada',
    'congelada' => 'Congelada',
    'terminada' => 'Terminada',
];
$estadoFiltro = $_GET['estado'] ?? 'todos';

$countTerminadas = 0;
$countCriticas = 0;
$countPendientes = 0;
$countEnCurso = 0;
foreach ($tareas as $t) {
    switch ($t['estado'] ?? '') {
        case 'terminada': $countTerminadas++; break;
        case 'pendiente': $countPendientes++; break;
        case 'en_curso': $countEnCurso++; break;
        case 'atrasada': $countPendientes++; break;
        case 'congelada': break;
    }
    if (($t['prioridad'] ?? '') === 'critica') {
        $countCriticas++;
    }
}
?>
<div class="page-header">
    <h1>Tareas de <?= htmlspecialchars($colaborador['nombre']) ?></h1>
    <a href="<?= htmlspecialchars($returnUrl) ?>" class="btn btn-secondary">Volver</a>
</div>

<div class="summary-grid" style="display:flex;gap:1em;flex-wrap:nowrap;justify-content:flex-start;margin-bottom:1.5em;">
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;background:#e6f7e6;border:1px solid #2ecc40;color:#2ecc40;padding:0.5em 0;">
        <h3 style="font-size:0.85em;margin:0;line-height:1;">TERMINADAS</h3>
        <p style="font-size:1.1em;margin:0;line-height:1.2;white-space:nowrap; font-weight:bold;"><?= $countTerminadas ?></p>
    </div>
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;background:#ffeaea;border:1px solid #c00;color:#c00;padding:0.5em 0;">
        <h3 style="font-size:0.85em;margin:0;line-height:1;">CRÍTICAS</h3>
        <p style="font-size:1.1em;margin:0;line-height:1.2;white-space:nowrap; font-weight:bold;"><?= $countCriticas ?></p>
    </div>
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;background:#fffbe6;border:1px solid #f1c40f;color:#f1c40f;padding:0.5em 0;">
        <h3 style="font-size:0.85em;margin:0;line-height:1;">PENDIENTES</h3>
        <p style="font-size:1.1em;margin:0;line-height:1.2;white-space:nowrap; font-weight:bold;"><?= $countPendientes ?></p>
    </div>
    <div class="summary-card" style="flex:1 1 0;min-width:120px;text-align:center;background:#e6f0fa;border:1px solid #3498db;color:#3498db;padding:0.5em 0;">
        <h3 style="font-size:0.85em;margin:0;line-height:1;">EN CURSO</h3>
        <p style="font-size:1.1em;margin:0;line-height:1.2;white-space:nowrap; font-weight:bold;"><?= $countEnCurso ?></p>
    </div>
</div>
<form method="get" style="margin-bottom:1em;display:flex;gap:1em;align-items:end;">
    <input type="hidden" name="colaborador_id" value="<?= (int)$colaborador['id'] ?>">
    <?php if (!empty($returnUrl)): ?>
        <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>">
    <?php endif; ?>
    <label for="estado" style="font-weight:bold;">Estado:</label>
    <select name="estado" id="estado" style="padding:0.4em; border-radius:4px;">
        <?php foreach ($estados as $key => $label): ?>
            <option value="<?= $key ?>"<?= $estadoFiltro === $key ? ' selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Filtrar</button>
</form>

<div class="table-wrap">
    <table class="table table-compact">
        <thead>
            <tr>
                <th>Título</th>
                <th>Estado</th>
                <th>Prioridad</th>
                <th>Fecha compromiso</th>
                <th>Horas registradas</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $tareasFiltradas = $estadoFiltro === 'todos'
            ? $tareas
            : array_filter($tareas, fn($t) => ($t['estado'] ?? '') === $estadoFiltro);
        ?>
        <?php if (empty($tareasFiltradas)): ?>
            <tr><td colspan="5" class="muted">No hay tareas asignadas para este estado.</td></tr>
        <?php else: ?>
            <?php foreach ($tareasFiltradas as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['titulo']) ?></td>
                    <td><?= htmlspecialchars($t['estado']) ?></td>
                    <td><?= htmlspecialchars($t['prioridad']) ?></td>
                    <td><?= htmlspecialchars($t['fecha_compromiso']) ?></td>
                    <td style="display:flex;align-items:center;gap:18px;">
                        <?= htmlspecialchars(number_format((float)($t['total_horas'] ?? 0), 1)) ?> h
                        <a href="<?= $basePath ?>/tareas/detalle-informativo?id=<?= (int)$t['id'] ?>" class="btn btn-small btn-icon" title="Ver detalle informativo" aria-label="Ver detalle informativo" style="padding:4px;display:inline-flex;align-items:center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
