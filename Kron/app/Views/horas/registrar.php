<?php
// Página para registrar horas trabajadas
// Muestra formulario a la izquierda y listado a la derecha
ob_start();
?>
<div class="page-header">
    <h1 style="display: flex; align-items: center; gap: 12px;">
        <!-- Icono de reloj -->
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--blue);">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
        </svg>
        Registrar horas trabajadas
    </h1>
    <?php if (!empty($taskTitle)): ?>
        <div class="muted" style="margin-left: 44px; font-size: 17px; margin-top: 4px;">
            <strong>Tarea:</strong> <?= htmlspecialchars($taskTitle) ?>
        </div>
    <?php endif; ?>
    <div class="hero-actions" style="margin-top: 8px; margin-left: 44px;">
        <?php
        // Usar return_url si está presente
        $volverUrl = $basePath . '/tareas/gestor';
        if (!empty($returnUrl)) {
            $volverUrl = htmlspecialchars($returnUrl);
        } elseif (!empty($_SERVER['HTTP_REFERER'])) {
            $volverUrl = htmlspecialchars($_SERVER['HTTP_REFERER']);
        } elseif (!empty($taskId)) {
            $volverUrl = $basePath . '/tareas/actividad?category_id=' . (int)$taskId;
        }
        ?>
        <a href="<?= $volverUrl ?>" class="btn btn-secondary">Volver</a>
    </div>
</div>
<div style="display: flex; gap: 32px; align-items: flex-start; margin-top: 32px;">
    <!-- Columna izquierda: Formulario -->
    <div style="flex: 1; min-width: 320px; max-width: 400px;">
        <h2>Nuevo registro</h2>
        <form method="post" action="<?= $basePath ?>/horas/registrar" class="form">
            <?php if (!empty($taskId)): ?>
                <input type="hidden" name="tarea_id" value="<?= (int)$taskId ?>">
            <?php endif; ?>
            <?php if (!empty($returnUrl)): ?>
                <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>">
            <?php endif; ?>
            <label>Día trabajado</label>
            <input type="date" name="fecha" required value="<?= date('Y-m-d') ?>">
            <label>Horas (hh:mm)</label>
            <input type="time" name="horas" step="60" required>
            <div class="form-actions" style="margin-top:16px;">
                <button type="submit" class="btn">Registrar</button>
            </div>
            <?php if (!empty($error)): ?>
                <div class="form-result" style="color:red; margin-top:12px;"> <?= htmlspecialchars($error) ?> </div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="form-result" style="color:green; margin-top:12px;"> <?= htmlspecialchars($success) ?> </div>
            <?php endif; ?>
        </form>
    </div>
    <!-- Columna derecha: Listado -->
    <div style="flex: 1; min-width: 320px; max-width: 400px;">
        <h2>Registros previos</h2>
        <ul class="horas-list">
            <?php
            function decimalToHHMM($decimal) {
                $h = floor($decimal);
                $m = round(($decimal - $h) * 60);
                return sprintf('%02d:%02d', $h, $m);
            }
            ?>
            <?php if (!empty($horas)): foreach ($horas as $h): ?>
                <li style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <span><?= htmlspecialchars(date('d-m-Y', strtotime($h['fecha']))) ?>: <?= decimalToHHMM($h['horas']) ?> h</span>
                    <a href="<?= $basePath ?>/horas/eliminar?tarea_id=<?= (int)$taskId ?>&fecha=<?= urlencode($h['fecha']) ?><?php if (!empty($returnUrl)): ?>&return_url=<?= urlencode($returnUrl) ?><?php endif; ?>" title="Eliminar registro" onclick="return confirm('¿Eliminar este registro de horas?');" style="color:#c00;display:inline-flex;align-items:center;padding:2px 6px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </a>
                </li>
            <?php endforeach; else: ?>
                <li class="empty-state">Sin registros</li>
            <?php endif; ?>
        </ul>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
