<?php /** @var array|null $admin */ /** @var string|null $error */ /** @var string|null $success */ $basePath = $basePath ?? ($GLOBALS['config']['base_path'] ?? ''); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>KRON</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css">
    <style>body{background:#f8fafc;}</style>
</head>
<body>
<div style="max-width:400px;margin:40px auto;padding:32px;background:#fff;border-radius:12px;box-shadow:0 2px 16px #0001;">
    <h2 style="margin-bottom:24px;">Setup Administrador</h2>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">✅ <?= htmlspecialchars($success) ?></div>
        <?php if (!empty($plain_password)): ?>
            <div class="alert alert-warning" style="margin-bottom:20px;">
                <b>Contraseña en texto plano:</b> <span style="font-family:monospace; color:#c00; background:#fff3cd; padding:2px 6px; border-radius:4px;"><?= htmlspecialchars($plain_password) ?></span><br>
                <b>Hash generado:</b> <span style="font-family:monospace; color:#333; background:#e9ecef; padding:2px 6px; border-radius:4px;"><?= htmlspecialchars($password_hash) ?></span>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-bottom:20px;">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= $basePath ?>/admin-setup">
        <div style="margin-bottom:16px;">
            <label>Nombre completo:</label>
            <input type="text" name="nombre" class="form-control" required autofocus>
        </div>
        <div style="margin-bottom:16px;">
            <label>Email:</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div style="margin-bottom:16px;">
            <label>Contraseña:</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Crear o Actualizar Administrador</button>
    </form>
    <?php if (!empty($admins)): ?>
        <div style="margin-top:32px;">
            <h4>Administradores existentes:</h4>
            <ul>
                <?php foreach ($admins as $a): ?>
                    <li><b><?= htmlspecialchars($a['nombre']) ?></b> (<?= htmlspecialchars($a['email']) ?>) - <?= $a['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?><br>
                    <span style="font-size:12px; color:#888;">Hash: <?= htmlspecialchars($a['password_hash']) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
