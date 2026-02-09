<?php
/** @var string $title */
/** @var array $roles */
/** @var string|null $error */
ob_start();
?>
<div class="page-header">
    <h1>Roles</h1>
</div>
<?php if (! empty($error)): ?>
    <div class="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php
$roleOptions = [
    ['nombre' => 'administrador', 'descripcion' => 'Gestion total del sistema'],
    ['nombre' => 'colaborador', 'descripcion' => 'Acceso a sus propios datos'],
    ['nombre' => 'jefe', 'descripcion' => 'Acceso a datos de su equipo'],
    ['nombre' => 'subgerente', 'descripcion' => 'Acceso total a su area'],
];
$roleDescriptions = array_column($roleOptions, 'descripcion');
?>

<table class="table">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Descripcion</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($roles as $role): ?>
            <tr>
                <td><?= htmlspecialchars($role['nombre']) ?></td>
                <td><?= htmlspecialchars($role['descripcion'] ?? '-') ?></td>
                <td>
                    <form method="post" action="<?= $basePath ?>/admin/roles/actualizar" class="inline">
                        <input type="hidden" name="id" value="<?= (int) $role['id'] ?>">
                        <input type="hidden" name="nombre" value="<?= htmlspecialchars($role['nombre']) ?>">
                        <select name="descripcion" class="input-medium">
                            <?php $currentDesc = $role['descripcion'] ?? ''; ?>
                            <?php if ($currentDesc !== '' && ! in_array($currentDesc, $roleDescriptions, true)): ?>
                                <option value="<?= htmlspecialchars($currentDesc) ?>" selected><?= htmlspecialchars($currentDesc) ?></option>
                            <?php endif; ?>
                            <option value="">Sin descripcion</option>
                            <?php foreach ($roleOptions as $option): ?>
                                <option value="<?= htmlspecialchars($option['descripcion']) ?>" <?= $currentDesc === $option['descripcion'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($option['descripcion']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-secondary">Actualizar</button>
                    </form>
                    <form method="post" action="<?= $basePath ?>/admin/roles/eliminar" class="inline">
                        <input type="hidden" name="id" value="<?= (int) $role['id'] ?>">
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
