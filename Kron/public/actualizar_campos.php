<?php
/**
 * Script para agregar campos faltantes a las tablas existentes
 * Ejecutar en: http://tudominio.com/kron/actualizar_campos.php
 * IMPORTANTE: Eliminar después de ejecutar
 */

header('Content-Type: text/html; charset=utf-8');

$config = require __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Actualización de Campos KRON</h1>";
    echo "<pre>";
    
    $actualizaciones = 0;
    
    // Función para verificar si una columna existe
    function columnExists($pdo, $table, $column) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return $stmt->rowCount() > 0;
    }
    
    // Actualizar kron_users
    echo "=== Actualizando kron_users ===\n";
    if (!columnExists($pdo, 'kron_users', 'password_hash')) {
        $pdo->exec("ALTER TABLE kron_users ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL AFTER email");
        echo "  ✓ Agregado: password_hash\n";
        $actualizaciones++;
    }
    if (!columnExists($pdo, 'kron_users', 'estado')) {
        $pdo->exec("ALTER TABLE kron_users ADD COLUMN estado VARCHAR(20) NOT NULL DEFAULT 'activo' AFTER password_hash");
        echo "  ✓ Agregado: estado\n";
        $actualizaciones++;
    }
    if (!columnExists($pdo, 'kron_users', 'fecha_ingreso')) {
        $pdo->exec("ALTER TABLE kron_users ADD COLUMN fecha_ingreso DATE DEFAULT NULL AFTER estado");
        echo "  ✓ Agregado: fecha_ingreso\n";
        $actualizaciones++;
    }
    echo "\n";
    
    // Actualizar kron_tasks
    echo "=== Actualizando kron_tasks ===\n";
    if (!columnExists($pdo, 'kron_tasks', 'descripcion')) {
        $pdo->exec("ALTER TABLE kron_tasks ADD COLUMN descripcion TEXT DEFAULT NULL AFTER titulo");
        echo "  ✓ Agregado: descripcion\n";
        $actualizaciones++;
    }
    // Verificar que user_id permita NULL
    $stmt = $pdo->query("SHOW COLUMNS FROM kron_tasks WHERE Field = 'user_id'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($col && $col['Null'] === 'NO') {
        $pdo->exec("ALTER TABLE kron_tasks MODIFY COLUMN user_id INT DEFAULT NULL");
        echo "  ✓ Modificado: user_id ahora permite NULL\n";
        $actualizaciones++;
    }
    // Verificar que fecha_compromiso permita NULL
    $stmt = $pdo->query("SHOW COLUMNS FROM kron_tasks WHERE Field = 'fecha_compromiso'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($col && $col['Null'] === 'NO') {
        $pdo->exec("ALTER TABLE kron_tasks MODIFY COLUMN fecha_compromiso DATE DEFAULT NULL");
        echo "  ✓ Modificado: fecha_compromiso ahora permite NULL\n";
        $actualizaciones++;
    }
    echo "\n";
    
    // Actualizar kron_task_categories
    echo "=== Actualizando kron_task_categories ===\n";
    if (!columnExists($pdo, 'kron_task_categories', 'created_by')) {
        $pdo->exec("ALTER TABLE kron_task_categories ADD COLUMN created_by INT DEFAULT NULL AFTER team_id");
        echo "  ✓ Agregado: created_by\n";
        $actualizaciones++;
    }
    echo "\n";
    
    // Actualizar kron_teams
    echo "=== Actualizando kron_teams ===\n";
    if (!columnExists($pdo, 'kron_teams', 'subgerente_id')) {
        $pdo->exec("ALTER TABLE kron_teams ADD COLUMN subgerente_id INT DEFAULT NULL AFTER nombre");
        echo "  ✓ Agregado: subgerente_id\n";
        $actualizaciones++;
    }
    if (!columnExists($pdo, 'kron_teams', 'jefe_id')) {
        $pdo->exec("ALTER TABLE kron_teams ADD COLUMN jefe_id INT DEFAULT NULL AFTER subgerente_id");
        echo "  ✓ Agregado: jefe_id\n";
        $actualizaciones++;
    }
    echo "\n";
    
    echo "===========================================\n";
    if ($actualizaciones > 0) {
        echo "✓ Total de campos actualizados: $actualizaciones\n";
    } else {
        echo "✓ Todas las columnas ya estaban correctas\n";
    }
    echo "===========================================\n";
    
    // Mostrar estructura de tablas críticas
    echo "\n=== Estructura de kron_task_categories ===\n";
    $stmt = $pdo->query("DESCRIBE kron_task_categories");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Default']}\n";
    }
    
    echo "\n✓✓✓ ACTUALIZACIÓN COMPLETADA ✓✓✓\n";
    echo "\nIMPORTANTE: Elimina este archivo por seguridad.\n";
    echo "</pre>";
    
    echo "<hr>";
    echo "<p><a href='check_tables.php'>→ Verificar tablas</a></p>";
    echo "<p><a href='../'>→ Ir al inicio</a></p>";
    
} catch (PDOException $e) {
    echo "</pre>";
    echo "<h2 style='color: red;'>Error:</h2>";
    echo "<pre style='color: red;'>";
    echo $e->getMessage();
    echo "\n\nStack trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}
?>
