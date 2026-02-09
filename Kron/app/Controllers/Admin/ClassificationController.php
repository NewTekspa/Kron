<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\TaskClassification;

class ClassificationController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $classifications = TaskClassification::all();
        $error = trim($_GET['error'] ?? '');

        $this->view('admin/classifications/index', [
            'title' => 'Categorias',
            'classifications' => $classifications,
            'error' => $error !== '' ? $error : null,
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $nombre = trim($_POST['nombre'] ?? '');
        if ($nombre === '') {
            $this->redirect('/admin/categorias?error=Nombre+incompleto');
        }

        TaskClassification::create($nombre);

        $this->redirect('/admin/categorias');
    }

    public function update(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        if ($id === 0 || $nombre === '') {
            $this->redirect('/admin/categorias?error=Datos+incompletos');
        }

        TaskClassification::updateName($id, $nombre);

        $this->redirect('/admin/categorias');
    }

    public function delete(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/admin/categorias?error=Categoria+no+valida');
        }

        TaskClassification::delete($id);

        $this->redirect('/admin/categorias');
    }
}
