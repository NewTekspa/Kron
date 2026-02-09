<?php
/**
 * Script para crear las tablas faltantes de KRON
 * Ejecutar en: http://tudominio.com/kron/crear_tablas_faltantes.php
 * IMPORTANTE: Eliminar después de ejecutar
 */

header('Content-Type: text/html; charset=utf-8');

$config = require __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Creación de Tablas Faltantes KRON</h1>";
    echo "<pre>";
    
    // Verificar qué tablas existen
    $stmt = $pdo->query("SHOW TABLES");
    $tablasExistentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tablas existentes:\n";
    foreach ($tablasExistentes as $tabla) {
        echo "  ✓ $tabla\n";
    }
    echo "\n";
    
    // Crear kron_user_roles si no existe
    if (!in_array('kron_user_roles', $tablasExistentes)) {
        echo "Creando kron_user_roles...\n";
        $pdo->exec("
            CREATE TABLE kron_user_roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                role_id INT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_user_role (user_id, role_id),
                FOREIGN KEY (user_id) REFERENCES kron_users(id) ON DELETE CASCADE,
                FOREIGN KEY (role_id) REFERENCES kron_roles(id) ON DELETE CASCADE
            )
        ");
        echo "  ✓ kron_user_roles creada\n\n";
    } else {
        echo "  → kron_user_roles ya existe\n\n";
    }
    
    // Crear kron_user_relations si no existe
    if (!in_array('kron_user_relations', $tablasExistentes)) {
        echo "Creando kron_user_relations...\n";
        $pdo->exec("
            CREATE TABLE kron_user_relations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                supervisor_id INT NOT NULL,
                subordinado_id INT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_supervisor_subordinado (supervisor_id, subordinado_id),
                FOREIGN KEY (supervisor_id) REFERENCES kron_users(id) ON DELETE CASCADE,
                FOREIGN KEY (subordinado_id) REFERENCES kron_users(id) ON DELETE CASCADE
            )
        ");
        echo "  ✓ kron_user_relations creada\n\n";
    } else {
        echo "  → kron_user_relations ya existe\n\n";
    }
    
    // Crear kron_team_members si no existe
    if (!in_array('kron_team_members', $tablasExistentes)) {
        echo "Creando kron_team_members...\n";
        $pdo->exec("
            CREATE TABLE kron_team_members (
                id INT AUTO_INCREMENT PRIMARY KEY,
                team_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_team_user (team_id, user_id),
                FOREIGN KEY (team_id) REFERENCES kron_teams(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES kron_users(id) ON DELETE CASCADE
            )
        ");
        echo "  ✓ kron_team_members creada\n\n";
    } else {
        echo "  → kron_team_members ya existe\n\n";
    }
    
    // Crear kron_task_logs si no existe
    if (!in_array('kron_task_logs', $tablasExistentes)) {
        echo "Creando kron_task_logs...\n";
        $pdo->exec("
            CREATE TABLE kron_task_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                task_id INT NOT NULL,
                user_id INT NOT NULL,
                contenido TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (task_id) REFERENCES kron_tasks(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES kron_users(id) ON DELETE CASCADE
            )
        ");
        echo "  ✓ kron_task_logs creada\n\n";
    } else {
        echo "  → kron_task_logs ya existe\n\n";
    }
    
    // Verificar tablas finales
    $stmt = $pdo->query("SHOW TABLES");
    $tablasFinales = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n===========================================\n";
    echo "TABLAS FINALES EN LA BASE DE DATOS:\n";
    echo "===========================================\n";
    foreach ($tablasFinales as $tabla) {
        echo "  ✓ $tabla\n";
    }
    
    echo "\n✓✓✓ PROCESO COMPLETADO ✓✓✓\n";
    echo "\nIMPORTANTE: Elimina este archivo por seguridad.\n";
    echo "</pre>";
    
    echo "<hr>";
    echo "<p><a href='check_tables.php'>→ Verificar todas las tablas</a></p>";
    echo "<p><a href='../'>→ Ir al inicio de la aplicación</a></p>";
    
} catch (PDOException $e) {
    echo "</pre>";
    echo "<h2 style='color: red;'>Error:</h2>";
    echo "<pre style='color: red;'>";
    echo $e->getMessage();
    echo "</pre>";
}
?>
