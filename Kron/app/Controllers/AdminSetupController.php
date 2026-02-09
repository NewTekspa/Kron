<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Core\Database;

class AdminSetupController extends Controller
{
    public function index(): void
    {
        $config = $GLOBALS['config']['db'] ?? require __DIR__ . '/../../config/database.php';
        $pdo = \App\Core\Database::connection($config);
        // Listar todos los administradores
        $sql = "SELECT u.*, r.nombre as rol_nombre 
                FROM kron_users u
                LEFT JOIN kron_user_roles ur ON ur.user_id = u.id
                LEFT JOIN kron_roles r ON r.id = ur.role_id
                WHERE r.nombre = 'administrador'";
        $stmt = $pdo->query($sql);
        $admins = $stmt->fetchAll();
        $this->view('admin/setup', [
            'admins' => $admins,
        ]);
    }

    public function create(): void
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $error = '';
        if ($nombre === '' || $email === '' || $password === '') {
            $error = 'Debes completar todos los campos.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email inválido.';
        }
        if ($error) {
            $this->view('admin/setup', [ 'error' => $error ]);
            return;
        }
        $config = $GLOBALS['config']['db'] ?? require __DIR__ . '/../../config/database.php';
        $pdo = \App\Core\Database::connection($config);
        try {
            $pdo->beginTransaction();
            // Obtener el rol de administrador
            $stmt = $pdo->prepare("SELECT id FROM kron_roles WHERE nombre = 'administrador' LIMIT 1");
            $stmt->execute();
            $role = $stmt->fetch();
            if (!$role) {
                throw new \Exception('No existe el rol de administrador');
            }
            // Verificar si ya existe el email
            $stmt = $pdo->prepare("SELECT id FROM kron_users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if ($user) {
                // Actualizar nombre y contraseña
                $stmt = $pdo->prepare("UPDATE kron_users SET nombre = ?, password_hash = ?, estado = 'activo' WHERE id = ?");
                $stmt->execute([$nombre, $hash, $user['id']]);
                // Asegurar que tenga el rol de administrador
                $stmt = $pdo->prepare("SELECT 1 FROM kron_user_roles WHERE user_id = ? AND role_id = ?");
                $stmt->execute([$user['id'], $role['id']]);
                if (!$stmt->fetch()) {
                    $stmt = $pdo->prepare("INSERT INTO kron_user_roles (user_id, role_id) VALUES (?, ?)");
                    $stmt->execute([$user['id'], $role['id']]);
                }
                $pdo->commit();
                $this->view('admin/setup', [
                    'success' => 'Usuario administrador actualizado correctamente.',
                    'plain_password' => $password,
                    'password_hash' => $hash
                ]);
                return;
            } else {
                // Crear el usuario
                $stmt = $pdo->prepare("INSERT INTO kron_users (nombre, email, password_hash, estado) VALUES (?, ?, ?, 'activo')");
                $stmt->execute([$nombre, $email, $hash]);
                $userId = $pdo->lastInsertId();
                // Asignar el rol
                $stmt = $pdo->prepare("INSERT INTO kron_user_roles (user_id, role_id) VALUES (?, ?)");
                $stmt->execute([$userId, $role['id']]);
                $pdo->commit();
                $this->view('admin/setup', [
                    'success' => 'Usuario administrador creado correctamente.',
                    'plain_password' => $password,
                    'password_hash' => $hash
                ]);
                return;
            }
        } catch (\Exception $e) {
            $pdo->rollBack();
            $this->view('admin/setup', [ 'error' => 'Error: ' . $e->getMessage() ]);
        }
    }
}
