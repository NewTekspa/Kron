<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;

class UserController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $authUser = Auth::user();
        $roleName = Auth::roleName() ?? '';
        if (! $authUser || $roleName === '') {
            http_response_code(403);
            echo 'Acceso denegado.';
            return;
        }

        $users = User::allWithRoleVisibleTo((int) $authUser['id'], $roleName);

        $this->view('admin/users/index', [
            'title' => 'Usuarios',
            'users' => $users,
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $roles = Role::all();

        $this->view('admin/users/create', [
            'title' => 'Crear usuario',
            'roles' => $roles,
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'estado' => $_POST['estado'] ?? 'activo',
            'fecha_ingreso' => $_POST['fecha_ingreso'] ?: null,
            'password' => $_POST['password'] ?? '',
            'role_id' => (int) ($_POST['role_id'] ?? 0),
        ];

        if ($data['nombre'] === '' || $data['email'] === '' || $data['password'] === '' || $data['role_id'] === 0) {
            $this->view('admin/users/create', [
                'title' => 'Crear usuario',
                'roles' => Role::all(),
                'error' => 'Completa todos los campos obligatorios.',
                'form' => $data,
            ]);
            return;
        }

        if (User::emailExists($data['email'])) {
            $this->view('admin/users/create', [
                'title' => 'Crear usuario',
                'roles' => Role::all(),
                'error' => 'El email ya esta registrado.',
                'form' => $data,
            ]);
            return;
        }

        $userId = User::create([
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'estado' => $data['estado'],
            'fecha_ingreso' => $data['fecha_ingreso'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
        ]);

        Role::assignToUser($userId, $data['role_id']);

        $this->redirect('/admin/usuarios');
    }

    public function edit(): void
    {
        $this->requireLogin();

        $authUser = Auth::user();
        $roleName = Auth::roleName() ?? '';
        if (! $authUser || $roleName === '') {
            http_response_code(403);
            echo 'Acceso denegado.';
            return;
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id === 0) {
            http_response_code(403);
            echo 'Acceso denegado.';
            return;
        }

        if ($roleName !== 'administrador') {
            $visibleIds = Team::visibleUserIdsForRole((int) $authUser['id'], $roleName);
            if (! in_array($id, $visibleIds, true)) {
                http_response_code(403);
                echo 'Acceso denegado.';
                return;
            }
        }
        $user = User::findWithRole($id);

        if (! $user) {
            $this->redirect('/admin/usuarios');
        }

        $this->view('admin/users/edit', [
            'title' => 'Editar usuario',
            'user' => $user,
            'roles' => Role::all(),
        ]);
    }

    public function update(): void
    {
        $this->requireLogin();

        $authUser = Auth::user();
        $roleName = Auth::roleName() ?? '';
        if (! $authUser || $roleName === '') {
            http_response_code(403);
            echo 'Acceso denegado.';
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id === 0) {
            http_response_code(403);
            echo 'Acceso denegado.';
            return;
        }

        if ($roleName !== 'administrador') {
            $visibleIds = Team::visibleUserIdsForRole((int) $authUser['id'], $roleName);
            if (! in_array($id, $visibleIds, true)) {
                http_response_code(403);
                echo 'Acceso denegado.';
                return;
            }
        }
        $user = User::findById($id);

        if (! $user) {
            $this->redirect('/admin/usuarios');
        }

        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'estado' => $_POST['estado'] ?? 'activo',
            'fecha_ingreso' => $_POST['fecha_ingreso'] ?: null,
            'password' => $_POST['password'] ?? '',
            'role_id' => (int) ($_POST['role_id'] ?? 0),
        ];

        if ($data['nombre'] === '' || $data['email'] === '' || ($roleName === 'administrador' && $data['role_id'] === 0)) {
            $this->view('admin/users/edit', [
                'title' => 'Editar usuario',
                'user' => $user,
                'roles' => Role::all(),
                'error' => 'Completa los campos obligatorios.',
            ]);
            return;
        }

        if (User::emailExists($data['email'], $id)) {
            $this->view('admin/users/edit', [
                'title' => 'Editar usuario',
                'user' => $user,
                'roles' => Role::all(),
                'error' => 'El email ya esta registrado.',
            ]);
            return;
        }

        $payload = [
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'estado' => $roleName === 'administrador' ? $data['estado'] : $user['estado'],
            'fecha_ingreso' => $data['fecha_ingreso'],
        ];

        if ($data['password'] !== '') {
            $payload['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            User::update($id, $payload);
        } else {
            User::updateWithoutPassword($id, $payload);
        }

        if ($roleName === 'administrador') {
            Role::assignToUser($id, $data['role_id']);
        }

        $this->redirect('/admin/usuarios');
    }

    public function deactivate(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        $current = Auth::user();
        if ($id > 0 && (! $current || (int) $current['id'] !== $id)) {
            User::deactivate($id);
        }

        $this->redirect('/admin/usuarios');
    }

    public function activate(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            User::activate($id);
        }

        $this->redirect('/admin/usuarios');
    }

    public function delete(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        $current = Auth::user();
        if ($id > 0 && (! $current || (int) $current['id'] !== $id)) {
            User::delete($id);
        }

        $this->redirect('/admin/usuarios');
    }

    public function search(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $term = trim($_GET['q'] ?? '');
        $role = trim($_GET['role'] ?? '');
        $limit = (int) ($_GET['limit'] ?? 15);

        if (strlen($term) < 2) {
            header('Content-Type: application/json');
            echo json_encode([]);
            return;
        }

        $roleNames = [];
        if (in_array($role, ['subgerente', 'jefe', 'colaborador', 'administrador'], true)) {
            $roleNames = [$role];
        } elseif ($role === 'supervisor') {
            $roleNames = ['jefe', 'subgerente'];
        } elseif ($role === 'subordinado') {
            $roleNames = ['colaborador', 'jefe'];
        }

        $results = User::searchForTeam($term, $roleNames, $limit);

        header('Content-Type: application/json');
        echo json_encode($results);
    }
}
