<?php
/** @var string $title */
/** @var array $task */
/** @var array $logs */
/** @var array $times */
/** @var string|null $error */
/** @var int $authUserId */
/** @var string $roleName */
/** @var bool $isAdmin */
/** @var string|null $returnUrl */
ob_start();
$statusLabels = [
    'pendiente' => 'Pendiente',
    'en_curso' => 'En curso',
    'atrasada' => 'Atrasada',
    'congelada' => 'Congelada',
    'terminada' => 'Terminada',
];
$priorityLabels = [
    'baja' => 'Baja',
    'media' => 'Media',
    'alta' => 'Alta',
    'critica' => 'Critica',
];
$formatDate = function (?string $value): string {
    if (! $value) {
        return '-';
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('d-m-Y', $timestamp) : $value;
};
$formatDateTime = function (?string $value): string {
    if (! $value) {
        return '-';
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('d-m-Y H:i', $timestamp) : $value;
};
?>
<div class="page-header">
    <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, var(--blue) 0%, var(--blue-light) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
        </div>
        <h1 style="margin: 0;">Detalle de tarea</h1>
    </div>
    <div class="hero-actions">
        <a href="<?= htmlspecialchars($returnUrl ?? ($basePath . '/tareas')) ?>" class="btn btn-secondary">Volver</a>
        <a href="<?= $basePath ?>/tareas/editar?id=<?= (int) $task['id'] ?><?= $returnUrl ? '&return=' . urlencode($returnUrl) : '' ?>" class="btn btn-secondary">Editar</a>
        <?php if ($isAdmin || (int) ($task['user_id'] ?? 0) === (int) $authUserId): ?>
            <form method="post" action="<?= $basePath ?>/tareas/eliminar" class="inline" onsubmit="return confirm('Eliminar tarea?');">
                <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                <?php if ($returnUrl): ?>
                    <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php if (! empty($error)): ?>
    <div class="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="summary-grid">
    <div class="summary-card" style="border-left: 4px solid var(--blue);">
        <h3 style="display: flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
            Título
        </h3>
        <p style="font-weight: 600; color: var(--text); font-size: 15px;"><?= htmlspecialchars($task['titulo']) ?></p>
    </div>
    <div class="summary-card" style="border-left: 4px solid #10b981;">
        <h3 style="display: flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Asignado
        </h3>
        <p style="font-size: 14px;"><strong><?= htmlspecialchars($task['asignado_nombre'] ?? '-') ?></strong><br>
        <span style="color: var(--muted); font-size: 13px;"><?= htmlspecialchars($task['asignado_email'] ?? '-') ?></span></p>
    </div>
    <div class="summary-card" style="border-left: 4px solid #8b5cf6;">
        <h3 style="display: flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 11-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            Actividad
        </h3>
        <p style="font-weight: 500;"><?= htmlspecialchars($task['categoria_nombre'] ?? '-') ?></p>
    </div>
    <div class="summary-card" style="border-left: 4px solid <?= $task['prioridad'] === 'critica' ? '#ef4444' : ($task['prioridad'] === 'alta' ? '#f59e0b' : '#64748b') ?>;">
        <h3 style="display: flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            Prioridad
        </h3>
        <p>
            <?php
            $prioColors = [
                'critica' => 'background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #b91c1c; border: 1px solid #fca5a5;',
                'alta' => 'background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; border: 1px solid #fcd34d;',
                'media' => 'background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af; border: 1px solid #93c5fd;',
                'baja' => 'background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); color: #475569; border: 1px solid #cbd5e1;'
            ];
            $prioStyle = $prioColors[$task['prioridad']] ?? $prioColors['baja'];
            ?>
            <span style="<?= $prioStyle ?> padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; display: inline-block;">
                <?= htmlspecialchars($priorityLabels[$task['prioridad']] ?? $task['prioridad']) ?>
            </span>
        </p>
    </div>
    <div class="summary-card" style="border-left: 4px solid <?= $task['estado'] === 'terminada' ? '#10b981' : ($task['estado'] === 'atrasada' ? '#ef4444' : '#2563eb') ?>;">
        <h3 style="display: flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 11 12 14 22 4"></polyline>
                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
            </svg>
            Estado
        </h3>
        <p>
            <?php
            $statusColors = [
                'terminada' => 'background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #166534; border: 1px solid #86efac;',
                'en_curso' => 'background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af; border: 1px solid #93c5fd;',
                'atrasada' => 'background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #b91c1c; border: 1px solid #fca5a5;',
                'congelada' => 'background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; border: 1px solid #fcd34d;',
                'pendiente' => 'background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); color: #475569; border: 1px solid #cbd5e1;'
            ];
            $statusStyle = $statusColors[$task['estado']] ?? $statusColors['pendiente'];
            ?>
            <span style="<?= $statusStyle ?> padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; display: inline-block;">
                <?= htmlspecialchars($statusLabels[$task['estado']] ?? $task['estado']) ?>
            </span>
        </p>
    </div>
    <div class="summary-card" style="border-left: 4px solid #f59e0b;">
        <h3 style="display: flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            Compromiso
        </h3>
        <p style="font-weight: 600; color: var(--text);"><?= htmlspecialchars($formatDate($task['fecha_compromiso'])) ?></p>
    </div>
    <div class="summary-card" style="border-left: 4px solid #10b981;">
        <h3 style="display: flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 11 12 14 22 4"></polyline>
                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
            </svg>
            Término real
        </h3>
        <p style="font-weight: 600; color: var(--text);"><?= htmlspecialchars($formatDate($task['fecha_termino_real'] ?? null)) ?></p>
    </div>
    <div class="summary-card" style="border-left: 4px solid #64748b;">
        <h3 style="display: flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Creada por
        </h3>
        <p style="font-weight: 500;"><?= htmlspecialchars($task['creador_nombre']) ?></p>
    </div>
</div>

<div class="form-grid">
    <form method="post" action="<?= $basePath ?>/tareas/bitacora" class="form" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: var(--shadow);">
        <h2 style="display: flex; align-items: center; gap: 10px; margin: 0 0 16px; padding-bottom: 12px; border-bottom: 2px solid var(--line);">
            <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"></path>
                </svg>
            </div>
            Agregar observación
        </h2>
        <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
        <label style="display: flex; align-items: center; gap: 6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Observación
        </label>
        <textarea name="contenido" rows="4" required placeholder="Escribe tu observación aquí..."></textarea>
        <div class="form-actions">
            <button type="submit" class="btn">Agregar</button>
        </div>
    </form>

    <?php if ($isAdmin || (int) $task['user_id'] === (int) $authUserId): ?>
        <form method="post" action="<?= $basePath ?>/tareas/tiempo" class="form" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: var(--shadow);">
            <h2 style="display: flex; align-items: center; gap: 10px; margin: 0 0 16px; padding-bottom: 12px; border-bottom: 2px solid var(--line);">
                <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                Registrar horas
            </h2>
            <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
            <label style="display: flex; align-items: center; gap: 6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Fecha
            </label>
            <input type="date" name="fecha" required>
            <label style="display: flex; align-items: center; gap: 6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Horas (HH:MM)
            </label>
            <input type="time" name="horas" step="60" required>
            <div class="form-actions">
                <button type="submit" class="btn">Registrar</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<div class="summary-grid">
    <div class="summary-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: var(--shadow);">
        <h3 style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid var(--line);">
            <span style="display: flex; align-items: center; gap: 8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                Bitácora
            </span>
            <span style="background: var(--blue); color: white; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700;"><?= count($logs) ?></span>
        </h3>
        <?php if (empty($logs)): ?>
            <p class="muted" style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 8px; display: block; opacity: 0.5;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                Sin observaciones
            </p>
        <?php else: ?>
            <div class="log-list">
                <?php foreach ($logs as $log): ?>
                    <div class="log-item">
                        <div class="log-meta"><?= htmlspecialchars($log['autor_nombre']) ?> - <?= htmlspecialchars($formatDateTime($log['created_at'] ?? null)) ?></div>
                        <div><?= nl2br(htmlspecialchars($log['contenido'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="summary-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: var(--shadow);">
        <h3 style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid var(--line);">
            <span style="display: flex; align-items: center; gap: 8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Horas registradas
            </span>
            <span style="background: #f59e0b; color: white; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700;"><?= count($times) ?></span>
        </h3>
        <?php if (empty($times)): ?>
            <p class="muted" style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 8px; display: block; opacity: 0.5;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Sin horas registradas
            </p>
        <?php else: ?>
            <div class="time-list">
                <?php foreach ($times as $time): ?>
                    <?php
                    $decimal = (float) $time['horas'];
                    $hours = (int) floor($decimal);
                    $minutes = (int) round(($decimal - $hours) * 60);
                    if ($minutes === 60) {
                        $hours += 1;
                        $minutes = 0;
                    }
                    $timeLabel = sprintf('%02d:%02d', $hours, $minutes);
                    ?>
                    <div class="time-item">
                        <div class="time-meta">
                            <span><?= htmlspecialchars($formatDate($time['fecha'] ?? null)) ?></span>
                            <strong><?= htmlspecialchars($timeLabel) ?></strong>
                        </div>
                        <?php if ($isAdmin || (int) $task['user_id'] === (int) $authUserId): ?>
                            <div class="time-actions">
                                <form method="post" action="<?= $basePath ?>/tareas/tiempo/eliminar" class="inline" onsubmit="return confirm('Eliminar registro de horas?');">
                                    <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                    <input type="hidden" name="time_id" value="<?= (int) $time['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-small">Eliminar</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
