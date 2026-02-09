<?php
/** @var string $title */
/** @var array $team */
/** @var array $members */
ob_start();
?>
<div class="page-header">
    <h1>Detalle de equipo</h1>
    <div class="hero-actions">
        <a href="<?= $basePath ?>/admin/equipos" class="btn btn-secondary">Volver a equipos</a>
    </div>
</div>

<div class="summary-grid">
    <div class="summary-card">
        <h3>Equipo</h3>
        <p><?= htmlspecialchars($team['nombre']) ?></p>
    </div>
    <div class="summary-card">
        <h3>Subgerente</h3>
        <p><?= htmlspecialchars($team['subgerente_nombre']) ?> (<?= htmlspecialchars($team['subgerente_email']) ?>)</p>
    </div>
    <div class="summary-card">
        <h3>Jefe</h3>
        <p><?= htmlspecialchars($team['jefe_nombre']) ?> (<?= htmlspecialchars($team['jefe_email']) ?>)</p>
    </div>
</div>

<table class="table">
    <caption class="table-filter">
        <div class="filter-bar">
            <label>Colaboradores (<?= count($members) ?>)</label>
        </div>
    </caption>
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Email</th>
            <th>Rol</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($members)): ?>
            <tr>
                <td colspan="4" class="muted">Sin colaboradores asignados.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($members as $member): ?>
                <tr>
                    <td><?= htmlspecialchars($member['nombre']) ?></td>
                    <td><?= htmlspecialchars($member['email']) ?></td>
                    <td><?= htmlspecialchars($member['rol_nombre'] ?? 'sin rol') ?></td>
                    <td>
                        <form method="post" action="<?= $basePath ?>/admin/equipos/remover-colaborador" class="inline" onsubmit="return confirm('Quitar colaborador del equipo?');">
                            <input type="hidden" name="team_id" value="<?= (int) $team['id'] ?>">
                            <input type="hidden" name="colaborador_id" value="<?= (int) $member['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-small">Quitar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
