<?php
/** @var string $title */
/** @var array $roles */
/** @var array $user */
/** @var string|null $error */
/** @var bool $isAdmin */
ob_start();
?>
<div class="page-header">
    <h1>Editar usuario</h1>
</div>
<?php if (! empty($error)): ?>
    <div class="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="post" action="<?= $basePath ?>/admin/usuarios/actualizar" class="form">
    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">

    <label>Nombre</label>
    <input type="text" name="nombre" value="<?= htmlspecialchars($user['nombre']) ?>" required>

    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

    <?php if ($isAdmin): ?>
        <label>Estado</label>
        <select name="estado">
            <option value="activo" <?= $user['estado'] === 'activo' ? 'selected' : '' ?>>activo</option>
            <option value="inactivo" <?= $user['estado'] === 'inactivo' ? 'selected' : '' ?>>inactivo</option>
        </select>
    <?php endif; ?>

    <label>Fecha ingreso</label>
    <input type="date" name="fecha_ingreso" value="<?= htmlspecialchars($user['fecha_ingreso'] ?? '') ?>">

    <?php if ($isAdmin): ?>
        <label>Rol</label>
        <select name="role_id" required>
            <option value="">Seleccione un rol</option>
            <?php foreach ($roles as $role): ?>
                <option value="<?= (int) $role['id'] ?>" <?= (int) ($user['rol_id'] ?? 0) === (int) $role['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($role['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>

    <label>Nueva contrasena (opcional)</label>
    <input type="password" name="password">

    <div class="form-actions">
        <a class="btn btn-secondary" href="<?= $basePath ?>/admin/usuarios">Cancelar</a>
        <button type="submit" class="btn">Guardar</button>
    </div>
</form>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
