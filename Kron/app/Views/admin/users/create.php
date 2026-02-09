<?php
/** @var string $title */
/** @var array $roles */
/** @var array|null $form */
/** @var string|null $error */
ob_start();
?>
<div class="page-header">
    <h1>Crear usuario</h1>
</div>
<?php if (! empty($error)): ?>
    <div class="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="post" action="<?= $basePath ?>/admin/usuarios/guardar" class="form">
    <label>Nombre</label>
    <input type="text" name="nombre" value="<?= htmlspecialchars($form['nombre'] ?? '') ?>" required>

    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($form['email'] ?? '') ?>" required>

    <label>Estado</label>
    <select name="estado">
        <option value="activo" <?= ($form['estado'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>activo</option>
        <option value="inactivo" <?= ($form['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>inactivo</option>
    </select>

    <label>Fecha ingreso</label>
    <input type="date" name="fecha_ingreso" value="<?= htmlspecialchars($form['fecha_ingreso'] ?? '') ?>">

    <label>Rol</label>
    <select name="role_id" required>
        <option value="">Seleccione un rol</option>
        <?php foreach ($roles as $role): ?>
            <option value="<?= (int) $role['id'] ?>" <?= (int) ($form['role_id'] ?? 0) === (int) $role['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($role['nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Contrasena</label>
    <input type="password" name="password" required>

    <div class="form-actions">
        <a class="btn btn-secondary" href="<?= $basePath ?>/admin/usuarios">Cancelar</a>
        <button type="submit" class="btn">Guardar</button>
    </div>
</form>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
