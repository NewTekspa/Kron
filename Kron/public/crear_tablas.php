<?php
/**
 * Script para crear todas las tablas de KRON en el servidor remoto
 * Ejecutar este archivo en: http://tudominio.com/kron/crear_tablas.php
 * IMPORTANTE: Eliminar este archivo después de ejecutarlo por seguridad
 */

header('Content-Type: text/html; charset=utf-8');

// Cargar configuración de base de datos
$config = require __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Creación de Tablas KRON</h1>";
    echo "<pre>";
    
    // Script SQL completo
    $sql = "
    -- Eliminar tablas si existen (en orden inverso por las foreign keys)
    DROP TABLE IF EXISTS kron_team_task_indicators;
    DROP TABLE IF EXISTS kron_task_indicators;
    DROP TABLE IF EXISTS kron_team_members;
    DROP TABLE IF EXISTS kron_task_logs;
    DROP TABLE IF EXISTS kron_task_times;
    DROP TABLE IF EXISTS kron_tasks;
    DROP TABLE IF EXISTS kron_task_categories;
    DROP TABLE IF EXISTS kron_task_classifications;
    DROP TABLE IF EXISTS kron_teams;
    DROP TABLE IF EXISTS kron_user_relations;
    DROP TABLE IF EXISTS kron_user_roles;
    DROP TABLE IF EXISTS kron_roles;
    DROP TABLE IF EXISTS kron_users;

    -- Crear tabla de usuarios
    CREATE TABLE kron_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) DEFAULT NULL,
        estado VARCHAR(20) NOT NULL DEFAULT 'activo',
        fecha_ingreso DATE DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL
    );

    -- Crear tabla de roles
    CREATE TABLE kron_roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(50) NOT NULL UNIQUE,
        descripcion VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL
    );

    -- Crear tabla de asignación de roles a usuarios
    CREATE TABLE kron_user_roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        role_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_user_role (user_id, role_id),
        FOREIGN KEY (user_id) REFERENCES kron_users(id) ON DELETE CASCADE,
        FOREIGN KEY (role_id) REFERENCES kron_roles(id) ON DELETE CASCADE
    );

    -- Crear tabla de relaciones jerárquicas entre usuarios
    CREATE TABLE kron_user_relations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        supervisor_id INT NOT NULL,
        subordinado_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_supervisor_subordinado (supervisor_id, subordinado_id),
        FOREIGN KEY (supervisor_id) REFERENCES kron_users(id) ON DELETE CASCADE,
        FOREIGN KEY (subordinado_id) REFERENCES kron_users(id) ON DELETE CASCADE
    );

    -- Crear tabla de clasificaciones de tareas
    CREATE TABLE kron_task_classifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL
    );

    -- Crear tabla de equipos
    CREATE TABLE kron_teams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        subgerente_id INT DEFAULT NULL,
        jefe_id INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        FOREIGN KEY (subgerente_id) REFERENCES kron_users(id) ON DELETE SET NULL,
        FOREIGN KEY (jefe_id) REFERENCES kron_users(id) ON DELETE SET NULL
    );

    -- Crear tabla de miembros de equipos
    CREATE TABLE kron_team_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        team_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_team_user (team_id, user_id),
        FOREIGN KEY (team_id) REFERENCES kron_teams(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES kron_users(id) ON DELETE CASCADE
    );

    -- Crear tabla de categorías de tareas
    CREATE TABLE kron_task_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        classification_id INT DEFAULT NULL,
        team_id INT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        FOREIGN KEY (classification_id) REFERENCES kron_task_classifications(id) ON DELETE SET NULL,
        FOREIGN KEY (team_id) REFERENCES kron_teams(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES kron_users(id) ON DELETE SET NULL
    );

    -- Crear tabla de tareas
    CREATE TABLE kron_tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        user_id INT DEFAULT NULL,
        created_by INT NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        descripcion TEXT DEFAULT NULL,
        fecha_compromiso DATE DEFAULT NULL,
        prioridad VARCHAR(20) NOT NULL DEFAULT 'normal',
        estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        FOREIGN KEY (category_id) REFERENCES kron_task_categories(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES kron_users(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES kron_users(id) ON DELETE CASCADE
    );

    -- Crear tabla de tiempos de tareas
    CREATE TABLE kron_task_times (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        fecha DATE NOT NULL,
        horas DECIMAL(5,2) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        UNIQUE KEY uk_kron_task_times (task_id, fecha),
        FOREIGN KEY (task_id) REFERENCES kron_tasks(id) ON DELETE CASCADE
    );

    -- Crear tabla de logs de tareas
    CREATE TABLE kron_task_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        user_id INT NOT NULL,
        contenido TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (task_id) REFERENCES kron_tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES kron_users(id) ON DELETE CASCADE
    );

    -- Crear tabla de indicadores de tareas
    CREATE TABLE kron_task_indicators (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        indicador VARCHAR(100) NOT NULL,
        valor DECIMAL(10,2) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        FOREIGN KEY (task_id) REFERENCES kron_tasks(id) ON DELETE CASCADE
    );

    -- Crear tabla de indicadores de equipos
    CREATE TABLE kron_team_task_indicators (
        id INT AUTO_INCREMENT PRIMARY KEY,
        team_id INT NOT NULL,
        indicador VARCHAR(100) NOT NULL,
        valor DECIMAL(10,2) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL,
        FOREIGN KEY (team_id) REFERENCES kron_teams(id) ON DELETE CASCADE
    );
    ";
    
    // Ejecutar el script
    $pdo->exec($sql);
    echo "✓ Todas las tablas fueron eliminadas y recreadas correctamente\n\n";
    
    // Insertar roles básicos
    $rolesSQL = "
    INSERT INTO kron_roles (nombre, descripcion) VALUES 
    ('administrador', 'Acceso completo al sistema'),
    ('jefe', 'Gestión de equipo y tareas'),
    ('subgerente', 'Supervisión de equipos'),
    ('colaborador', 'Usuario estándar del sistema');
    ";
    
    $pdo->exec($rolesSQL);
    echo "✓ Roles básicos insertados correctamente\n\n";
    
    // Verificar tablas creadas
    $stmt = $pdo->query("SHOW TABLES");
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tablas creadas en la base de datos:\n";
    foreach ($tablas as $tabla) {
        echo "  - $tabla\n";
    }
    
    echo "\n✓✓✓ PROCESO COMPLETADO EXITOSAMENTE ✓✓✓\n";
    echo "\nIMPORTANTE: Por seguridad, elimina este archivo después de ejecutarlo.\n";
    echo "</pre>";
    
    echo "<hr>";
    echo "<h2>Siguiente paso:</h2>";
    echo "<p>Ahora puedes crear el usuario administrador usando: <a href='admin_setup.php'>admin_setup.php</a></p>";
    
} catch (PDOException $e) {
    echo "</pre>";
    echo "<h2 style='color: red;'>Error al crear las tablas:</h2>";
    echo "<pre style='color: red;'>";
    echo $e->getMessage();
    echo "</pre>";
    echo "<p>Por favor, verifica la configuración de la base de datos en config/database.php</p>";
}
?>
