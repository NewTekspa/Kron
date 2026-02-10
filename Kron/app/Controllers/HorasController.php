<?php
namespace App\Controllers;
use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;

class HorasController extends Controller
{
    public function eliminar()
    {
        $this->requireLogin();
        $user = Auth::user();
        $userId = (int) $user['id'];
        $taskId = isset($_GET['tarea_id']) ? (int)$_GET['tarea_id'] : null;
        $fecha = $_GET['fecha'] ?? null;
        $returnUrl = $_GET['return_url'] ?? null;
        if ($taskId && $fecha) {
            \App\Models\User::deleteHourEntry($taskId, $fecha);
            $success = 'Registro eliminado correctamente.';
        } else {
            $success = '';
        }
        // Redirigir de vuelta a la página de registro con return_url
        $basePath = $GLOBALS['config']['base_path'] ?? '';
        $redirectUrl = $basePath . '/horas/registrar?tarea_id=' . $taskId;
        if ($returnUrl) {
            $redirectUrl .= '&return_url=' . urlencode($returnUrl);
        }
        $redirectUrl .= '&success=1';
        header('Location: ' . $redirectUrl);
        exit;
    }
    public function registrar()
    {
        $this->requireLogin();
        
        $user = Auth::user();
        $userId = (int) $user['id'];
        $roleName = Auth::roleName() ?? '';
        $isAdmin = Auth::isAdmin();
        $error = $success = '';
        $taskId = null;
        $taskTitle = null;
        $returnUrl = null;
        if (isset($_GET['tarea_id'])) {
            $taskId = (int)$_GET['tarea_id'];
        } elseif (isset($_POST['tarea_id'])) {
            $taskId = (int)$_POST['tarea_id'];
        }
        if ($taskId) {
            $taskTitle = \App\Models\Task::getTitleById($taskId);
        }
        if (isset($_GET['return_url'])) {
            $returnUrl = $_GET['return_url'];
        } elseif (isset($_POST['return_url'])) {
            $returnUrl = $_POST['return_url'];
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fecha = $_POST['fecha'] ?? '';
            $horas = $_POST['horas'] ?? '';
            if (!$fecha || !$horas) {
                $error = 'Todos los campos son obligatorios.';
            } else {
                // Validar que no exista registro para ese día y tarea
                $existe = User::hasHourEntry($userId, $fecha, $taskId);
                if ($existe) {
                    $error = 'Ya existe un registro para ese día en esta tarea.';
                } else {
                    User::addHourEntry($userId, $fecha, $horas, $taskId);
                    // Redirigir con los parámetros para mantener el estado
                    $basePath = $GLOBALS['config']['base_path'] ?? '';
                    $redirectUrl = $basePath . '/horas/registrar?tarea_id=' . $taskId;
                    if ($returnUrl) {
                        $redirectUrl .= '&return_url=' . urlencode($returnUrl);
                    }
                    $redirectUrl .= '&success=1';
                    header('Location: ' . $redirectUrl);
                    exit;
                }
            }
        }
        
        // Mostrar mensaje de éxito si viene del redirect
        if (isset($_GET['success']) && $_GET['success'] == '1') {
            $success = 'Registro guardado correctamente.';
        }
        $horas = User::getHourEntries($userId, $taskId);
        $basePath = $GLOBALS['config']['base_path'] ?? '';
        $title = 'Registrar horas trabajadas';
        $authUser = $user;
        // Pasar returnUrl a la vista
        require __DIR__ . '/../Views/horas/registrar.php';
    }
}
