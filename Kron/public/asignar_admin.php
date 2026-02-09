<?php
// Script de emergencia para crear o actualizar el usuario administrador@gmail.com con rol administrador y clave Tony@236974
$config = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$email = 'administrador@gmail.com';
$password = 'Tony@236974';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Buscar usuario
$stmt = $pdo->prepare("SELECT id FROM kron_users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Actualizar clave y activar usuario
    $pdo->prepare("UPDATE kron_users SET password_hash = ?, estado = 'activo' WHERE id = ?")->execute([$hash, $user['id']]);
    $userId = $user['id'];
    $msg = 'Usuario actualizado.';
} else {
    // Crear usuario
    $pdo->prepare("INSERT INTO kron_users (nombre, email, estado, fecha_ingreso, password_hash) VALUES (?, ?, 'activo', CURDATE(), ?)")
        ->execute(['Administrador', $email, $hash]);
    $userId = $pdo->lastInsertId();
    $msg = 'Usuario creado.';
}

// Buscar id del rol administrador
$roleStmt = $pdo->prepare("SELECT id FROM kron_roles WHERE nombre = 'administrador' LIMIT 1");
$roleStmt->execute();
$role = $roleStmt->fetch(PDO::FETCH_ASSOC);
if (!$role) {
    die('No existe el rol administrador en la tabla kron_roles.');
}
$roleId = $role['id'];

// Asignar rol (si no existe ya)
$check = $pdo->prepare("SELECT 1 FROM kron_user_roles WHERE user_id = ? AND role_id = ?");
$check->execute([$userId, $roleId]);
if (!$check->fetch()) {
    $pdo->prepare("INSERT INTO kron_user_roles (user_id, role_id) VALUES (?, ?)")->execute([$userId, $roleId]);
    $msg .= ' Rol asignado.';
} else {
    $msg .= ' Rol ya estaba asignado.';
}

echo '<h2>Listo</h2><p>' . htmlspecialchars($msg) . '</p>';
echo '<p>Usuario: <b>' . htmlspecialchars($email) . '</b><br>Clave: <b>' . htmlspecialchars($password) . '</b></p>';
