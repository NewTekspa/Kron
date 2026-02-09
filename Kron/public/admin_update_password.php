<?php
// admin_update_password.php: Actualiza la contraseña del usuario administrador
$config = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($email === '' || $password === '') {
        $error = 'Email y contraseña son obligatorios.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE kron_users SET password_hash = ? WHERE email = ?");
        $stmt->execute([$hash, $email]);
        if ($stmt->rowCount() > 0) {
            $success = 'Contraseña actualizada correctamente.';
        } else {
            $error = 'No se encontró el usuario con ese email.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar contraseña administrador</title>
    <style>body{font-family:Arial;margin:2em;}form{max-width:400px;}label{display:block;margin-top:1em;}input{width:100%;padding:8px;margin-top:4px;}button{padding:1em 2em;font-size:16px;margin-top:1em;}</style>
</head>
<body>
    <h1>Actualizar contraseña administrador</h1>
    <?php if ($error): ?><div style="color:red;margin-bottom:1em;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div style="color:green;margin-bottom:1em;"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <form method="post">
        <label>Email</label>
        <input type="email" name="email" required value="administrador@kron.cl">
        <label>Nueva contraseña</label>
        <input type="password" name="password" required value="Tony@236974">
        <button type="submit">Actualizar contraseña</button>
    </form>
</body>
</html>
