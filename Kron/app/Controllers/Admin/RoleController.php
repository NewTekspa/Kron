<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Role;

class RoleController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $roles = Role::all();

        $this->view('admin/roles/index', [
            'title' => 'Roles',
            'roles' => $roles,
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($nombre === '') {
            $this->view('admin/roles/index', [
                'title' => 'Roles',
                'roles' => Role::all(),
                'error' => 'Debes indicar un nombre de rol.',
            ]);
            return;
        }

        Role::create([
            'nombre' => $nombre,
            'descripcion' => $descripcion ?: null,
        ]);

        $this->redirect('/admin/roles');
    }

    public function update(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($id === 0 || $nombre === '') {
            $this->redirect('/admin/roles');
        }

        Role::update($id, [
            'nombre' => $nombre,
            'descripcion' => $descripcion ?: null,
        ]);

        $this->redirect('/admin/roles');
    }

    public function delete(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/admin/roles');
        }

        if (Role::isRoleInUse($id)) {
            $this->redirect('/admin/roles');
        }

        Role::delete($id);

        $this->redirect('/admin/roles');
    }
}
