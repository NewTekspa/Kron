<?php
/**
 * Script para actualizar/crear las tablas de roles en el host remoto
 * Este script:
 * 1. Crea las tablas kron_roles y kron_user_roles si no existen
 * 2. Ajusta las columnas si faltan
 * 3. Inserta los roles básicos
 * 4. Muestra el estado actual de roles y asignaciones
 */

header('Content-Type: text/html; charset=utf-8');

// Configuración de base de datos
require_once __DIR__ . '/../config/database.php';

$config = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Actualizar Roles</title>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 4px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 4px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 12px; border-radius: 4px; margin: 10px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 12px; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; font-weight: bold; }
        tr:hover { background: #f8f9fa; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #007bff; }
    </style></head><body><div class='container'>";
    
    echo "<h1>🔧 Actualización de Tablas de Roles - Sistema KRON</h1>";
    echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>";
    
    // PASO 1: Verificar y crear tabla kron_roles
    echo "<div class='step'>";
    echo "<h2>Paso 1: Verificar/Crear tabla kron_roles</h2>";
    
    $tableExists = $pdo->query("SHOW TABLES LIKE 'kron_roles'")->fetch();
    
    if (!$tableExists) {
        echo "<div class='warning'>⚠️ La tabla <code>kron_roles</code> no existe. Creándola...</div>";
        $pdo->exec("
            CREATE TABLE kron_roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(50) NOT NULL UNIQUE,
                descripcion VARCHAR(255) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<div class='success'>✅ Tabla <code>kron_roles</code> creada exitosamente</div>";
    } else {
        echo "<div class='info'>ℹ️ La tabla <code>kron_roles</code> ya existe</div>";
        
        // Verificar columnas
        $columns = $pdo->query("SHOW COLUMNS FROM kron_roles")->fetchAll(PDO::FETCH_COLUMN);
        $requiredColumns = ['id', 'nombre', 'descripcion', 'created_at', 'updated_at'];
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (!empty($missingColumns)) {
            echo "<div class='warning'>⚠️ Faltan columnas: " . implode(', ', $missingColumns) . "</div>";
            
            foreach ($missingColumns as $col) {
                switch ($col) {
                    case 'descripcion':
                        $pdo->exec("ALTER TABLE kron_roles ADD COLUMN descripcion VARCHAR(255) DEFAULT NULL");
                        echo "<div class='success'>✅ Columna <code>descripcion</code> agregada</div>";
                        break;
                    case 'created_at':
                        $pdo->exec("ALTER TABLE kron_roles ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
                        echo "<div class='success'>✅ Columna <code>created_at</code> agregada</div>";
                        break;
                    case 'updated_at':
                        $pdo->exec("ALTER TABLE kron_roles ADD COLUMN updated_at DATETIME NULL");
                        echo "<div class='success'>✅ Columna <code>updated_at</code> agregada</div>";
                        break;
                }
            }
        } else {
            echo "<div class='success'>✅ Todas las columnas requeridas están presentes</div>";
        }
    }
    echo "</div>";
    
    // PASO 2: Verificar y crear tabla kron_user_roles
    echo "<div class='step'>";
    echo "<h2>Paso 2: Verificar/Crear tabla kron_user_roles</h2>";
    
    $tableExists = $pdo->query("SHOW TABLES LIKE 'kron_user_roles'")->fetch();
    
    if (!$tableExists) {
        echo "<div class='warning'>⚠️ La tabla <code>kron_user_roles</code> no existe. Creándola...</div>";
        $pdo->exec("
            CREATE TABLE kron_user_roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                role_id INT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_user_role (user_id, role_id),
                FOREIGN KEY (user_id) REFERENCES kron_users(id) ON DELETE CASCADE,
                FOREIGN KEY (role_id) REFERENCES kron_roles(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<div class='success'>✅ Tabla <code>kron_user_roles</code> creada exitosamente</div>";
    } else {
        echo "<div class='info'>ℹ️ La tabla <code>kron_user_roles</code> ya existe</div>";
        
        // Verificar columnas
        $columns = $pdo->query("SHOW COLUMNS FROM kron_user_roles")->fetchAll(PDO::FETCH_COLUMN);
        $requiredColumns = ['id', 'user_id', 'role_id', 'created_at'];
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (!empty($missingColumns)) {
            echo "<div class='warning'>⚠️ Faltan columnas: " . implode(', ', $missingColumns) . "</div>";
            
            foreach ($missingColumns as $col) {
                if ($col === 'created_at') {
                    $pdo->exec("ALTER TABLE kron_user_roles ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
                    echo "<div class='success'>✅ Columna <code>created_at</code> agregada</div>";
                }
            }
        } else {
            echo "<div class='success'>✅ Todas las columnas requeridas están presentes</div>";
        }
    }
    echo "</div>";
    
    // PASO 3: Eliminar roles incorrectos (Usuario y Supervisor)
    echo "<div class='step'>";
    echo "<h2>Paso 3: Eliminar roles no válidos</h2>";
    
    $rolesToDelete = ['Usuario', 'usuario', 'Supervisor', 'supervisor'];
    $deletedCount = 0;
    
    foreach ($rolesToDelete as $roleName) {
        // Verificar si el rol existe
        $stmt = $pdo->prepare("SELECT id FROM kron_roles WHERE nombre = :nombre");
        $stmt->execute(['nombre' => $roleName]);
        $roleToDelete = $stmt->fetch();
        
        if ($roleToDelete) {
            try {
                // Eliminar asignaciones del rol primero
                $deleteAssignments = $pdo->prepare("DELETE FROM kron_user_roles WHERE role_id = :role_id");
                $deleteAssignments->execute(['role_id' => $roleToDelete['id']]);
                
                // Eliminar el rol
                $deleteRole = $pdo->prepare("DELETE FROM kron_roles WHERE id = :id");
                $deleteRole->execute(['id' => $roleToDelete['id']]);
                
                echo "<div class='success'>✅ Rol <code>{$roleName}</code> eliminado (ID: {$roleToDelete['id']})</div>";
                $deletedCount++;
            } catch (PDOException $e) {
                echo "<div class='warning'>⚠️ No se pudo eliminar el rol <code>{$roleName}</code>: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            echo "<div class='info'>ℹ️ Rol <code>{$roleName}</code> no existe (correcto)</div>";
        }
    }
    
    if ($deletedCount > 0) {
        echo "<div class='warning'><strong>⚠️ Se eliminaron $deletedCount roles no válidos.</strong> Los usuarios que tenían estos roles ya no tendrán rol asignado y deberán ser reasignados.</div>";
    } else {
        echo "<div class='success'>✅ No se encontraron roles no válidos</div>";
    }
    echo "</div>";
    
    // PASO 4: Insertar roles básicos
    echo "<div class='step'>";
    echo "<h2>Paso 4: Insertar roles válidos</h2>";
    
    $roles = [
        ['nombre' => 'administrador', 'descripcion' => 'Acceso completo al sistema'],
        ['nombre' => 'jefe', 'descripcion' => 'Gestión de equipo y tareas'],
        ['nombre' => 'subgerente', 'descripcion' => 'Supervisión de equipos'],
        ['nombre' => 'colaborador', 'descripcion' => 'Usuario estándar del sistema']
    ];
    
    $insertCount = 0;
    $updateCount = 0;
    
    foreach ($roles as $role) {
        $existing = $pdo->prepare("SELECT id, descripcion FROM kron_roles WHERE nombre = :nombre");
        $existing->execute(['nombre' => $role['nombre']]);
        $existingRole = $existing->fetch();
        
        if (!$existingRole) {
            $stmt = $pdo->prepare("INSERT INTO kron_roles (nombre, descripcion, created_at) VALUES (:nombre, :descripcion, NOW())");
            $stmt->execute($role);
            echo "<div class='success'>✅ Rol <code>{$role['nombre']}</code> insertado</div>";
            $insertCount++;
        } else {
            // Actualizar descripción si cambió
            if ($existingRole['descripcion'] !== $role['descripcion']) {
                $stmt = $pdo->prepare("UPDATE kron_roles SET descripcion = :descripcion, updated_at = NOW() WHERE nombre = :nombre");
                $stmt->execute($role);
                echo "<div class='info'>ℹ️ Rol <code>{$role['nombre']}</code> actualizado</div>";
                $updateCount++;
            } else {
                echo "<div class='info'>ℹ️ Rol <code>{$role['nombre']}</code> ya existe y está actualizado</div>";
            }
        }
    }
    
    echo "<div class='success'><strong>Resumen:</strong> $insertCount roles insertados, $updateCount actualizados</div>";
    echo "</div>";
    
    // PASO 5: Mostrar estado actual
    echo "<div class='step'>";
    echo "<h2>Paso 5: Estado actual del sistema</h2>";
    
    // Mostrar roles
    echo "<h3>Roles disponibles</h3>";
    $rolesResult = $pdo->query("SELECT * FROM kron_roles ORDER BY id")->fetchAll();
    
    if ($rolesResult) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Creado</th></tr>";
        foreach ($rolesResult as $role) {
            echo "<tr>";
            echo "<td>{$role['id']}</td>";
            echo "<td><strong>{$role['nombre']}</strong></td>";
            echo "<td>{$role['descripcion']}</td>";
            echo "<td>{$role['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Mostrar asignaciones de roles a usuarios
    echo "<h3>Usuarios y sus roles asignados</h3>";
    $usersWithRoles = $pdo->query("
        SELECT u.id, u.nombre, u.email, r.nombre as rol, ur.created_at as asignado_el
        FROM kron_users u
        LEFT JOIN kron_user_roles ur ON u.id = ur.user_id
        LEFT JOIN kron_roles r ON ur.role_id = r.id
        ORDER BY u.id
    ")->fetchAll();
    
    if ($usersWithRoles) {
        echo "<table>";
        echo "<tr><th>ID Usuario</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Asignado</th></tr>";
        foreach ($usersWithRoles as $user) {
            $rolClass = $user['rol'] ? '' : ' style="color: #dc3545; font-weight: bold;"';
            $rolText = $user['rol'] ?: '⚠️ SIN ROL';
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['nombre']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td{$rolClass}>{$rolText}</td>";
            echo "<td>" . ($user['asignado_el'] ?: '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Contar usuarios sin rol
        $sinRol = array_filter($usersWithRoles, fn($u) => !$u['rol']);
        if (!empty($sinRol)) {
            echo "<div class='warning'>";
            echo "<strong>⚠️ Hay " . count($sinRol) . " usuario(s) sin rol asignado.</strong><br>";
            echo "Para asignar un rol manualmente, usa este SQL:<br>";
            echo "<code>INSERT INTO kron_user_roles (user_id, role_id, created_at) VALUES ([user_id], [role_id], NOW());</code>";
            echo "</div>";
        }
    }
    
    echo "</div>";
    
    // PASO 6: Instrucciones finales
    echo "<div class='step'>";
    echo "<h2>Paso 6: Próximos pasos</h2>";
    echo "<div class='info'>";
    echo "<strong>✅ Actualización completada</strong><br><br>";
    echo "<strong>Para asignar roles a usuarios específicos:</strong><br>";
    echo "1. Identifica el <code>user_id</code> del usuario en la tabla de arriba<br>";
    echo "2. Identifica el <code>role_id</code> del rol deseado (1=administrador, 2=jefe, 3=subgerente, 4=colaborador)<br>";
    echo "3. Ejecuta este SQL en tu base de datos:<br><br>";
    echo "<code>INSERT INTO kron_user_roles (user_id, role_id, created_at) VALUES (1, 1, NOW());</code><br><br>";
    echo "<strong>Ejemplo:</strong> Para hacer al usuario ID=1 un administrador (role_id=1)<br>";
    echo "<code>INSERT INTO kron_user_roles (user_id, role_id, created_at) VALUES (1, 1, NOW());</code>";
    echo "</div>";
    
    // Jerarquía de permisos
    echo "<div class='info'>";
    echo "<strong>📋 Jerarquía de permisos en el sistema:</strong><br><br>";
    echo "🔹 <strong>Administrador:</strong> Ve y gestiona TODO el sistema sin restricciones<br>";
    echo "🔹 <strong>Subgerente:</strong> Ve y gestiona todos los usuarios de los equipos que supervisa<br>";
    echo "🔹 <strong>Jefe:</strong> Ve y gestiona a su equipo (él mismo y sus colaboradores)<br>";
    echo "🔹 <strong>Colaborador:</strong> Solo ve y gestiona sus propias tareas<br>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='success' style='margin-top: 30px;'>";
    echo "<strong>🎉 Proceso completado exitosamente</strong><br>";
    echo "Puedes cerrar esta página y verificar que el sistema funcione correctamente.";
    echo "</div>";
    
    echo "</div></body></html>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Error de base de datos:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
    echo "</div></body></html>";
}
