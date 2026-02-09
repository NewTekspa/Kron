<?php
/** @var string $title */
/** @var array $users */
/** @var array|null $authUser */
/** @var bool $isAdmin */
ob_start();
$formatDate = function (?string $value): string {
    if (! $value) {
        return '-';
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('d-m-Y', $timestamp) : $value;
};
?>
<div class="page-header">
    <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #10b981 0%, #34d399 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"></path>
            </svg>
        </div>
        <h1 style="margin: 0;">Usuarios</h1>
    </div>
    <?php if ($isAdmin): ?>
        <a class="btn" href="<?= $basePath ?>/admin/usuarios/nuevo">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Nuevo usuario
        </a>
    <?php endif; ?>
</div>
<?php if (! empty($users)): ?>
<table class="table" style="box-shadow: var(--shadow);">
    <thead>
        <tr>
            <th>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Nombre
                </div>
            </th>
            <th>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    Email
                </div>
            </th>
            <th>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    Estado
                </div>
            </th>
            <th>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    Fecha ingreso
                </div>
            </th>
            <th>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2a5 5 0 015 5v3a5 5 0 01-5 5 5 5 0 01-5-5V7a5 5 0 015-5z"></path>
                        <path d="M12 15c4 0 7 2 7 5v2H5v-2c0-3 3-5 7-5z"></path>
                    </svg>
                    Rol
                </div>
            </th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td style="font-weight: 500;"><?= htmlspecialchars($user['nombre']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td>
                    <?php
                    $estadoStyle = $user['estado'] === 'activo' 
                        ? 'background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #166534; border: 1px solid #86efac;'
                        : 'background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #b91c1c; border: 1px solid #fca5a5;';
                    ?>
                    <span style="<?= $estadoStyle ?> padding: 4px 12px; border-radius: 8px; font-weight: 700; font-size: 12px; display: inline-block;">
                        <?= htmlspecialchars($user['estado']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($formatDate($user['fecha_ingreso'] ?? null)) ?></td>
                <td>
                    <span style="background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 12px;">
                        <?= htmlspecialchars($user['rol_nombre'] ?? '-') ?>
                    </span>
                </td>
                <td>
                    <div class="table-actions">
                    <div class="table-actions">
                        <?php if ($isAdmin || $authUser): ?>
                            <a class="btn btn-secondary btn-small" href="<?= $basePath ?>/admin/usuarios/editar?id=<?= (int) $user['id'] ?>">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </a>
                        <?php endif; ?>
                        <?php if ($isAdmin && $user['estado'] === 'activo'): ?>
                            <form method="post" action="<?= $basePath ?>/admin/usuarios/desactivar" class="inline">
                                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-small">Desactivar</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($isAdmin && $user['estado'] === 'inactivo'): ?>
                            <form method="post" action="<?= $basePath ?>/admin/usuarios/activar" class="inline">
                                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                <button type="submit" class="btn btn-success btn-small">Activar</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($isAdmin): ?>
                            <form method="post" action="<?= $basePath ?>/admin/usuarios/eliminar" class="inline" onsubmit="return confirm('Esta accion elimina al usuario. ¿Continuar?');">
                                <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-small">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                                    </svg>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p>No hay usuarios registrados.</p>
<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
