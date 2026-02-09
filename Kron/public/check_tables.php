<?php
// check_tables.php: Verifica existencia de tablas y muestra resultado
$config = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tablas = [
    'kron_users',
    'kron_roles',
    'kron_user_roles',
    'kron_user_relations',
    'kron_tasks',
    'kron_task_times',
    'kron_task_logs',
    'kron_task_categories',
    'kron_task_classifications',
    'kron_teams',
    'kron_team_members',
    'kron_task_indicators',
    'kron_team_task_indicators'
];

$resultados = [];
foreach ($tablas as $tabla) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabla'");
        $existe = $stmt->fetch() ? '✔️' : '❌';
        $resultados[$tabla] = $existe;
    } catch (Exception $e) {
        $resultados[$tabla] = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación de tablas KRON</title>
    <style>body{font-family:Arial;margin:2em;}table{border-collapse:collapse;}td,th{border:1px solid #ccc;padding:8px;}</style>
</head>
<body>
    <h1>Verificación de tablas KRON</h1>
    <table>
        <tr><th>Tabla</th><th>Estado</th></tr>
        <?php foreach ($resultados as $tabla => $estado): ?>
            <tr><td><?= htmlspecialchars($tabla) ?></td><td><?= htmlspecialchars($estado) ?></td></tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
