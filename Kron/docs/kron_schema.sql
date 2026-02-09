-- Script de creación de tablas KRON

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

CREATE TABLE kron_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL
);

CREATE TABLE kron_user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_role (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES kron_users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES kron_roles(id) ON DELETE CASCADE
);

CREATE TABLE kron_user_relations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supervisor_id INT NOT NULL,
    subordinado_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_supervisor_subordinado (supervisor_id, subordinado_id),
    FOREIGN KEY (supervisor_id) REFERENCES kron_users(id) ON DELETE CASCADE,
    FOREIGN KEY (subordinado_id) REFERENCES kron_users(id) ON DELETE CASCADE
);

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
    updated_at DATETIME NULL
);

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

CREATE TABLE kron_task_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    contenido TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES kron_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES kron_users(id) ON DELETE CASCADE
);

CREATE TABLE kron_task_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    classification_id INT DEFAULT NULL,
    team_id INT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL
);

CREATE TABLE kron_task_classifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL
);

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

CREATE TABLE kron_team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_team_user (team_id, user_id),
    FOREIGN KEY (team_id) REFERENCES kron_teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES kron_users(id) ON DELETE CASCADE
);

CREATE TABLE kron_task_indicators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    indicador VARCHAR(100) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (task_id) REFERENCES kron_tasks(id) ON DELETE CASCADE
);

CREATE TABLE kron_team_task_indicators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    indicador VARCHAR(100) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (team_id) REFERENCES kron_teams(id) ON DELETE CASCADE
);

-- Insertar roles básicos
INSERT INTO kron_roles (nombre, descripcion) VALUES 
('administrador', 'Acceso completo al sistema'),
('jefe', 'Gestión de equipo y tareas'),
('subgerente', 'Supervisión de equipos'),
('colaborador', 'Usuario estándar del sistema');



