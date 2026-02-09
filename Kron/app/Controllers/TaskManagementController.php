<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskClassification;

class TaskManagementController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();
        // Permitir acceso a todos los roles autenticados

        $user = Auth::user();
        $visibleIds = [(int) $user['id']];
        $roleName = $user['rol_nombre'] ?? '';
        $isAdmin = ($roleName === 'administrador');
        // Equipos donde el usuario participa, lidera o administra
        $teams = [];
        $teamsParticipa = \App\Models\Team::visibleTeamsForRole((int)$user['id'], '', false);
        $teamsLidera = \App\Models\Team::visibleTeamsForRole((int)$user['id'], 'jefe', false);
        $teamsSubgerente = \App\Models\Team::visibleTeamsForRole((int)$user['id'], 'subgerente', false);
        $teamsAdmin = $isAdmin ? \App\Models\Team::visibleTeamsForRole((int)$user['id'], 'administrador', true) : [];
        // Unir y eliminar duplicados
        $teamsAll = array_merge($teamsParticipa, $teamsLidera, $teamsSubgerente, $teamsAdmin);
        $teams = array_values(array_reduce($teamsAll, function($carry, $item) {
            $carry[$item['id']] = $item;
            return $carry;
        }, []));

        $categories = TaskCategory::allWithCounts($visibleIds);
        $classifications = TaskClassification::all();
        $error = trim($_GET['error'] ?? '');

        $this->view('tasks/manage', [
            'title' => 'Gestion de tarea',
            'categories' => $categories,
            'classifications' => $classifications,
            'teams' => $teams,
            'isAdmin' => $isAdmin,
            'error' => $error !== '' ? $error : null,
        ]);
    }

    public function storeCategory(): void
    {
        $this->requireLogin();

        $nombre = trim($_POST['nombre'] ?? '');
        $classificationId = (int) ($_POST['classification_id'] ?? 0);
        $teamId = (int) ($_POST['team_id'] ?? 0);
        if ($nombre === '') {
            $this->redirect('/gestion-tareas?error=Nombre+incompleto');
        }

        $user = Auth::user();
        $GLOBALS['team_id'] = $teamId > 0 ? $teamId : null;
        TaskCategory::createWithClassification($nombre, (int) $user['id'], $classificationId > 0 ? $classificationId : null);

        $this->redirect('/gestion-tareas');
    }

    public function updateCategory(): void
    {
        $this->requireLogin();

        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $classificationId = (int) ($_POST['classification_id'] ?? 0);
        if ($id === 0 || $nombre === '') {
            $this->redirect('/gestion-tareas?error=Datos+incompletos');
        }

        TaskCategory::updateDetails($id, $nombre, $classificationId > 0 ? $classificationId : null);

        $this->redirect('/gestion-tareas?categoria_id=' . $id);
    }

    public function deleteCategory(): void
    {
        $this->requireLogin();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/gestion-tareas?error=Categoria+no+valida');
        }

        TaskCategory::delete($id);

        $this->redirect('/gestion-tareas');
    }

    public function categoryTasks(): void
    {
        $this->requireLogin();

        $user = Auth::user();
        $categoryId = (int) ($_GET['categoria_id'] ?? 0);
        $userId = (int) $user['id'];

        if ($categoryId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid category_id', 'received' => $categoryId]);
            return;
        }

        $tasks = Task::allForUserIdsByCategory([$userId], $categoryId);

        header('Content-Type: application/json');
        echo json_encode($tasks);
    }

    public function storeClassification(): void
    {
        $this->requireLogin();

        $nombre = trim($_POST['nombre'] ?? '');
        if ($nombre === '') {
            $this->redirect('/gestion-tareas?error=Nombre+incompleto');
        }

        TaskClassification::create($nombre);

        $this->redirect('/gestion-tareas');
    }

    public function updateClassification(): void
    {
        $this->requireLogin();

        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        if ($id === 0 || $nombre === '') {
            $this->redirect('/gestion-tareas?error=Datos+incompletos');
        }

        TaskClassification::updateName($id, $nombre);

        $this->redirect('/gestion-tareas');
    }

    public function deleteClassification(): void
    {
        $this->requireLogin();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/gestion-tareas?error=Clasificacion+no+valida');
        }

        TaskClassification::delete($id);

        $this->redirect('/gestion-tareas');
    }

    public function toggleCategoryStatus(): void
    {
        $this->requireLogin();
        $id = (int) ($_POST['id'] ?? 0);
        $estadoActual = $_POST['estado_actual'] ?? '';
        if ($id === 0 || ($estadoActual !== 'activa' && $estadoActual !== 'terminada')) {
            $this->redirect('/gestion-tareas?error=Estado+no+válido');
        }
        TaskCategory::toggleStatus($id, $estadoActual);
        $this->redirect('/gestion-tareas');
    }
}
