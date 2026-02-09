<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\Team;
use App\Models\User;

class TaskController extends Controller
{
    /**
     * Registrar horas trabajadas en una tarea
     */
    public function registrarHoras(): void
    {
        $this->requireLogin();
        $user = Auth::user();
        $taskId = (int) ($_POST['task_id'] ?? 0);
        $fecha = trim($_POST['fecha'] ?? '');
        $horasInput = trim($_POST['horas'] ?? '');
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        // Validar datos
        if ($taskId <= 0 || $fecha === '' || $horasInput === '') {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
                return;
            }
            $returnUrl = $this->safeReturnUrl($_POST['return_url'] ?? null);
            $this->redirect(($returnUrl ?: '/tareas') . '?error=Datos+incompletos');
        }

        // Normalizar horas a decimal
        $horas = 0.0;
        if (strpos($horasInput, ':') !== false) {
            [$h, $m] = array_pad(explode(':', $horasInput, 2), 2, '0');
            $horas = (int)$h + ((int)$m / 60);
        } else {
            $horas = (float) $horasInput;
        }
        if ($horas <= 0) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Horas inválidas']);
                return;
            }
            $returnUrl = $this->safeReturnUrl($_POST['return_url'] ?? null);
            $this->redirect(($returnUrl ?: '/tareas') . '?error=Horas+inválidas');
        }

        // Registrar
        \App\Models\Task::addTimeEntry($taskId, $fecha, $horas);

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            return;
        }
        $returnUrl = $this->safeReturnUrl($_POST['return_url'] ?? null);
        $this->redirect($returnUrl ?: '/tareas');
    }
    /**
     * Vista de detalle de actividad (categoría) y sus tareas
     */
    public function showActividad(): void
    {
        $this->requireLogin();
        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);
        $roleName = \App\Core\Auth::roleName() ?? '';
        $categoryId = (int)($_GET['category_id'] ?? 0);
        if ($categoryId <= 0) {
            $this->redirect('/tareas/gestor?error=Actividad+inválida');
        }
        $actividad = \App\Models\TaskCategory::findById($categoryId);
        if (!$actividad) {
            $this->redirect('/tareas/gestor?error=Actividad+no+encontrada');
        }
        // Obtener tareas asociadas a la actividad
        $todasTareas = \App\Models\Task::allForUserByCategory($userId, $roleName, $categoryId);
        if ($roleName === 'administrador') {
            $tareas = $todasTareas; // Administrador ve todas las tareas de la actividad
        } else {
            // Filtrar solo las tareas donde el usuario es responsable
            $tareas = array_filter($todasTareas, function($tarea) use ($userId) {
                return isset($tarea['user_id']) && (int)$tarea['user_id'] === $userId;
            });
        }
        $this->view('tasks/show_actividad', [
            'actividad' => $actividad,
            'tareas' => $tareas,
            'authUserId' => $userId,
        ]);
    }
    /**
     * Crear una nueva actividad (categoría) desde el gestor
     */
    public function crearActividad(): void
    {
        $this->requireLogin();
        $user = Auth::user();
        $nombre = trim($_POST['nombre'] ?? '');
        $clasificacionId = (int)($_POST['clasificacion_id'] ?? 0);
        $teamId = (int)($_POST['team_id'] ?? 0);
        if ($nombre === '' || $clasificacionId <= 0 || $teamId <= 0) {
            $this->redirect('/tareas/gestor?error=El+nombre,+la+clasificación+y+el+equipo+son+obligatorios');
        }
        // Validar que la clasificación existe
        $clasificacion = \App\Models\TaskClassification::findById($clasificacionId);
        if (!$clasificacion) {
            $this->redirect('/tareas/gestor?error=Clasificación+inválida');
        }
        // Validar que el equipo existe
        if (!\App\Models\Team::exists($teamId)) {
            $this->redirect('/tareas/gestor?error=Equipo+inválido');
        }
        // Crear la actividad (categoría) asociada a la clasificación y equipo
        $GLOBALS['team_id'] = $teamId;
        \App\Models\TaskCategory::createWithClassification($nombre, (int)$user['id'], $clasificacionId);
        unset($GLOBALS['team_id']);
        $this->redirect('/tareas/gestor');
    }

    /**
     * Editar una actividad existente
     */
    public function editarActividad(): void
    {
        $this->requireLogin();
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $clasificacionId = (int)($_POST['clasificacion_id'] ?? 0);
        $teamId = (int)($_POST['team_id'] ?? 0);
        
        if ($categoryId === 0 || $nombre === '' || $clasificacionId <= 0 || $teamId <= 0) {
            $this->redirect('/tareas/gestor?error=Todos+los+campos+son+obligatorios');
        }
        
        // Verificar que la categoría existe
        $category = \App\Models\TaskCategory::findById($categoryId);
        if (!$category) {
            $this->redirect('/tareas/gestor?error=Actividad+no+encontrada');
        }
        
        // Validar que la clasificación existe
        $clasificacion = \App\Models\TaskClassification::findById($clasificacionId);
        if (!$clasificacion) {
            $this->redirect('/tareas/gestor?error=Clasificación+inválida');
        }
        
        // Validar que el equipo existe
        if (!\App\Models\Team::exists($teamId)) {
            $this->redirect('/tareas/gestor?error=Equipo+inválido');
        }
        
        // Actualizar la actividad
        $GLOBALS['team_id'] = $teamId;
        \App\Models\TaskCategory::updateDetails($categoryId, $nombre, $clasificacionId);
        unset($GLOBALS['team_id']);
        
        $this->redirect('/tareas/gestor?success=Actividad+actualizada');
    }

    /**
     * Clonar una actividad con sus tareas y colaboradores (sin fechas ni horas)
     */
    public function clonarActividad(): void
    {
        $this->requireLogin();
        $user = Auth::user();
        $fromId = (int) ($_POST['source_category_id'] ?? 0);
        $newName = trim($_POST['new_category_name'] ?? '');
        if ($fromId === 0 || $newName === '') {
            $this->redirect('/tareas/gestor?error=Datos+inválidos+para+clonar');
        }
        $newId = TaskCategory::cloneWithTasksAndMembers($fromId, $newName, (int)$user['id']);
        if (!$newId) {
            $this->redirect('/tareas/gestor?error=No+se+pudo+clonar+la+actividad');
        }
        $this->redirect('/tareas/gestor?success=Actividad+clonada');
    }

    /**
     * Eliminar una actividad y todo lo relacionado (tareas, tiempos, logs)
     */
    public function eliminarActividad(): void
    {
        $this->requireLogin();
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        if ($categoryId === 0) {
            $this->redirect('/tareas/gestor?error=Actividad+inválida');
        }
        
        // Verificar que la categoría existe
        $category = TaskCategory::findById($categoryId);
        if (!$category) {
            $this->redirect('/tareas/gestor?error=Actividad+no+encontrada');
        }
        
        // Eliminar la categoría y todo lo relacionado
        TaskCategory::delete($categoryId);
        
        $this->redirect('/tareas/gestor?success=Actividad+eliminada');
    }

    /**
     * Vista de solo lectura de tareas asignadas a un colaborador
     */
    public function tareasColaborador(): void
    {
        $this->requireLogin();
        $colaboradorId = isset($_GET['colaborador_id']) ? (int)$_GET['colaborador_id'] : 0;
        if (!$colaboradorId) {
            http_response_code(400);
            echo 'Colaborador no especificado.';
            return;
        }
        $colaborador = \App\Models\User::findById($colaboradorId);
        if (!$colaborador) {
            http_response_code(404);
            echo 'Colaborador no encontrado.';
            return;
        }
        $tareas = \App\Models\Task::allForUserIds([$colaboradorId]);
        $basePath = $_ENV['BASE_PATH'] ?? '';
        $returnUrl = $_GET['return_url'] ?? ($basePath . '/tareas/gestion');
        $this->view('tasks/colaborador_tareas', [
            'title' => 'Tareas de ' . $colaborador['nombre'],
            'colaborador' => $colaborador,
            'tareas' => $tareas,
            'basePath' => $basePath,
            'returnUrl' => $returnUrl,
        ]);
    }

    private array $statusOptions = ['pendiente', 'en_curso', 'congelada', 'atrasada', 'terminada'];
    private array $priorityOptions = ['baja', 'media', 'alta', 'critica'];

    /**
     * Vista de solo lectura para gestión/revisión de tareas de un colaborador
     */
    public function revision(): void
    {
        $this->requireLogin();
        $userId = (int)($_GET['user_id'] ?? 0);
        // Filtro de mes: si es vacío o "todos", mostrar todo el tiempo
        $filtroMes = $_GET['filtro_mes'] ?? date('Y-m');
        $roleName = Auth::roleName() ?? '';
        $statusLabels = [
            'pendiente' => 'Pendiente',
            'en_curso' => 'En curso',
            'atrasada' => 'Atrasada',
            'congelada' => 'Congelada',
            'terminada' => 'Terminada',
        ];
        $formatDate = function (?string $value): string {
            if (! $value) return '-';
            $timestamp = strtotime($value);
            return $timestamp ? date('d-m-Y', $timestamp) : $value;
        };
        $formatHours = function ($value): string {
            $decimal = (float) $value;
            $hours = (int) floor($decimal);
            $minutes = (int) round(($decimal - $hours) * 60);
            if ($minutes === 60) { $hours += 1; $minutes = 0; }
            return sprintf('%02d:%02d', $hours, $minutes);
        };
        $tasks = [];
        $mesesDisponibles = [];
        $hoursByMonth = [];
        $tasksByMonth = [];
        $months = [];
        if ($userId > 0) {
            $allTasks = Task::allForUserIds([$userId]);
            // Extraer meses únicos de las tareas para el selector
            foreach ($allTasks as $t) {
                if (!empty($t['fecha_compromiso'])) {
                    $mesKey = substr($t['fecha_compromiso'], 0, 7);
                    if (!in_array($mesKey, $mesesDisponibles)) {
                        $mesesDisponibles[] = $mesKey;
                    }
                }
            }
            rsort($mesesDisponibles); // Ordenar descendente
            
            // Filtrar tareas según el mes seleccionado
            if ($filtroMes && $filtroMes !== 'todos') {
                $tasks = array_filter($allTasks, function($t) use ($filtroMes) {
                    return isset($t['fecha_compromiso']) && strpos($t['fecha_compromiso'], $filtroMes) === 0;
                });
            } else {
                $tasks = $allTasks;
            }
            
            // Calcular horas y tareas de los últimos 6 meses móviles (para el evolutivo)
            // Usamos las horas de las tareas creadas en cada mes, no KRON_TASK_TIMES
            $now = new \DateTimeImmutable();
            for ($i = 5; $i >= 0; $i--) {
                $monthKey = $now->modify("-{$i} months")->format('Y-m');
                $months[] = $monthKey;
                $hoursByMonth[$monthKey] = 0.0;
                $tasksByMonth[$monthKey] = 0;
            }
            
            // Calcular horas y tareas sumando desde las tareas del usuario
            foreach ($allTasks as $task) {
                if (!empty($task['fecha_compromiso'])) {
                    $taskMonth = substr($task['fecha_compromiso'], 0, 7);
                    if (in_array($taskMonth, $months)) {
                        $horas = isset($task['total_horas']) ? (float)$task['total_horas'] : (isset($task['horas']) ? (float)$task['horas'] : 0);
                        $hoursByMonth[$taskMonth] += $horas;
                        $tasksByMonth[$taskMonth]++;
                    }
                }
            }
        }
        $this->view('tasks/revision', [
            'title' => 'Revisión de tareas',
            'tasks' => $tasks,
            'statusLabels' => $statusLabels,
            'formatDate' => $formatDate,
            'formatHours' => $formatHours,
            'mesesDisponibles' => $mesesDisponibles,
            'filtroMes' => $filtroMes,
            'months' => $months,
            'hoursByMonth' => $hoursByMonth,
            'tasksByMonth' => $tasksByMonth,
        ]);
    }

    public function index(): void
    {
        $this->requireLogin();

        $user = Auth::user();
        $roleName = Auth::roleName() ?? '';
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        if ($categoryId > 0) {
            $tasks = Task::allForUserByCategory((int) $user['id'], $roleName, $categoryId);
        } else {
            $tasks = Task::allForUserIds([(int) $user['id']]);
        }
        $assignableUsers = $this->assignableUsers((int) $user['id'], $roleName);
        $error = trim($_GET['error'] ?? '');
        $completeTaskId = (int) ($_GET['complete'] ?? 0);

        $this->view('tasks/index', [
            'title' => 'Tareas',
            'tasks' => $tasks,
            'assignableUsers' => $assignableUsers,
            'statusOptions' => $this->statusOptions,
            'priorityOptions' => $this->priorityOptions,
            'error' => $error !== '' ? $error : null,
            'completeTaskId' => $completeTaskId > 0 ? $completeTaskId : null,
            'authUserId' => (int) $user['id'],
            'authUserName' => (string) ($user['nombre'] ?? ''),
            'roleName' => $roleName,
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();

        $user = Auth::user();
        $roleName = Auth::roleName() ?? '';

        $titulo = trim($_POST['titulo'] ?? '');
        $fechaCompromiso = trim($_POST['fecha_compromiso'] ?? '');
        // Normalizar fecha a Y-m-d si viene en formato dd-mm-yyyy
        if ($fechaCompromiso && preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $fechaCompromiso, $m)) {
            $fechaCompromiso = "$m[3]-$m[2]-$m[1]";
        }
        $prioridad = trim($_POST['prioridad'] ?? '');
        $estado = trim($_POST['estado'] ?? '');
        $asignadoId = (int) ($_POST['user_id'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $categoryName = trim($_POST['category_name'] ?? '');
        $categoryId = $this->resolveCategoryId($categoryId, $categoryName, (int) $user['id']);
        $returnUrl = $this->safeReturnUrl($_POST['return_url'] ?? null);

        if ($titulo === '' || $fechaCompromiso === '' || $prioridad === '' || $estado === '' || $asignadoId === 0 || $categoryId === 0) {
            $this->redirect('/tareas?error=Datos+incompletos');
        }

        if (! in_array($prioridad, $this->priorityOptions, true) || ! in_array($estado, $this->statusOptions, true)) {
            $this->redirect('/tareas?error=Valores+no+validos');
        }

        if (! $this->canAssignTo($asignadoId, (int) $user['id'], $roleName)) {
            http_response_code(403);
            echo 'Acceso denegado.';
            return;
        }

        Task::create([
            'category_id' => $categoryId,
            'user_id' => $asignadoId,
            'created_by' => (int) $user['id'],
            'titulo' => $titulo,
            'fecha_compromiso' => $fechaCompromiso,
            'prioridad' => $prioridad,
            'estado' => $estado,
        ]);

        if ($returnUrl) {
            $this->redirect($returnUrl);
        } else {
            $this->redirect('/tareas');
        }
    }

    public function show(): void
    {
        $this->requireLogin();

        $user = Auth::user();
        $roleName = Auth::roleName() ?? '';
        $id = (int) ($_GET['id'] ?? 0);
        $returnUrl = $this->safeReturnUrl($_GET['return'] ?? null);

        $task = Task::findWithDetails($id);
        if (! $task || ! $this->canViewTask($task, (int) $user['id'], $roleName)) {
            http_response_code(404);
            echo 'Tarea no encontrada.';
            return;
        }

        $logs = Task::logs($id);
        $times = Task::timeEntries($id);
        $error = trim($_GET['error'] ?? '');

        $this->view('tasks/show', [
            'title' => 'Detalle de tarea',
            'task' => $task,
            'logs' => $logs,
            'times' => $times,
            'error' => $error !== '' ? $error : null,
            'returnUrl' => $returnUrl,
            'authUserId' => (int) $user['id'],
            'roleName' => $roleName,
        ]);
    }

    public function edit(): void
    {
        $this->requireLogin();

        $user = Auth::user();
        $roleName = Auth::roleName() ?? '';
        $id = (int) ($_GET['id'] ?? 0);
        $returnUrl = $this->safeReturnUrl($_GET['return'] ?? null);
        if (! $returnUrl) {
            $returnUrl = $this->inferReturnUrl();
        }

        $task = Task::findWithDetails($id);
        if (! $task || ! $this->canViewTask($task, (int) $user['id'], $roleName)) {
            http_response_code(404);
            echo 'Tarea no encontrada.';
            return;
        }

        $assignableUsers = $this->assignableUsers((int) $user['id'], $roleName);
        $error = trim($_GET['error'] ?? '');

        $this->view('tasks/edit', [
            'title' => 'Editar tarea',
            'task' => $task,
            'assignableUsers' => $assignableUsers,
            'statusOptions' => $this->statusOptions,
            'priorityOptions' => $this->priorityOptions,
            'error' => $error !== '' ? $error : null,
            'returnUrl' => $returnUrl,
            'authUserId' => (int) $user['id'],
            'roleName' => $roleName,
        ]);
    }

    public function update(): void
    {
        $this->requireLogin();

        $user = Auth::user();
        $roleName = Auth::roleName() ?? '';
        $id = (int) ($_POST['id'] ?? 0);

        $task = Task::findWithDetails($id);
        if (! $task || ! $this->canViewTask($task, (int) $user['id'], $roleName)) {
            http_response_code(404);
            echo 'Tarea no encontrada.';
            return;
        }

        $titulo = trim($_POST['titulo'] ?? '');
        $fechaCompromiso = trim($_POST['fecha_compromiso'] ?? '');
        // Normalizar fecha a Y-m-d si viene en otro formato
        if ($fechaCompromiso && preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $fechaCompromiso, $m)) {
            $fechaCompromiso = "$m[3]-$m[2]-$m[1]";
        }
        $prioridad = trim($_POST['prioridad'] ?? '');
        $estado = trim($_POST['estado'] ?? '');
        $asignadoId = (int) ($_POST['user_id'] ?? $task['user_id']);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $categoryName = trim($_POST['category_name'] ?? '');
        $categoryId = $this->resolveCategoryId($categoryId, $categoryName, (int) $user['id']);
        $returnUrl = $this->safeReturnUrl($_POST['return_url'] ?? null);

        $returnParam = $returnUrl ? '&return=' . urlencode($returnUrl) : '';
        if ($titulo === '' || $fechaCompromiso === '' || $prioridad === '' || $estado === '' || $asignadoId === 0 || $categoryId === 0) {
            $this->redirect('/tareas/editar?id=' . $id . '&error=Datos+incompletos' . $returnParam);
        }

        if (! in_array($prioridad, $this->priorityOptions, true) || ! in_array($estado, $this->statusOptions, true)) {
            $this->redirect('/tareas/editar?id=' . $id . '&error=Valores+no+validos' . $returnParam);
        }

        if (! $this->canAssignTo($asignadoId, (int) $user['id'], $roleName)) {
            http_response_code(403);
            echo 'Acceso denegado.';
            return;
        }

        Task::update($id, [
            'category_id' => $categoryId,
            'user_id' => $asignadoId,
            'titulo' => $titulo,
            'fecha_compromiso' => $fechaCompromiso,
            'prioridad' => $prioridad,
            'estado' => $estado,
            'fecha_termino_real' => $task['fecha_termino_real'] ?? null,
        ]);

        if ($returnUrl) {
            $this->redirect($returnUrl);
        } else {
            $this->redirect('/tareas/detalle?id=' . $id);
        }
    }

    public function addLog(): void
    {
        $this->requireLogin();

        $user = Auth::user();
        $roleName = Auth::roleName() ?? '';
        $taskId = (int) ($_POST['task_id'] ?? 0);
        $contenido = trim($_POST['contenido'] ?? '');

        $task = Task::findWithDetails($taskId);
        if (! $task || ! $this->canViewTask($task, (int) $user['id'], $roleName)) {
            http_response_code(404);
            echo 'Tarea no encontrada.';
            return;
        }

        if ($contenido === '') {
            $this->redirect('/tareas/detalle?id=' . $taskId . '&error=Escribe+una+observacion');
        }

        Task::addLog($taskId, (int) $user['id'], $contenido);

        $this->redirect('/tareas/detalle?id=' . $taskId);
    }

    public function addTime(): void
    {
        $this->requireLogin();

        $user = Auth::user();
        $roleName = Auth::roleName() ?? '';
        $taskId = (int) ($_POST['task_id'] ?? 0);
        $fecha = trim($_POST['fecha'] ?? '');
        $horasInput = trim($_POST['horas'] ?? '');
        $horas = $this->parseTimeToHours($horasInput);

        $task = Task::findWithDetails($taskId);
        if (! $task || ! $this->canViewTask($task, (int) $user['id'], $roleName)) {
            http_response_code(404);
            echo 'Tarea no encontrada.';
            return;
        }

        $isAdmin = Auth::isAdmin();
        if (! $isAdmin && (int) $task['user_id'] !== (int) $user['id']) {
            http_response_code(403);
            echo 'Acceso denegado.';
            return;
        }

        if ($fecha === '' || $horas <= 0 || $horas > 24) {
            $this->redirect('/tareas/detalle?id=' . $taskId . '&error=Horas+no+validas');
        }

        if (Task::timeEntryExists($taskId, $fecha)) {
            $this->redirect('/tareas/detalle?id=' . $taskId . '&error=Ya+hay+horas+para+esa+fecha');
        }

        Task::addTimeEntry($taskId, $fecha, $horas);

        $this->redirect('/tareas/detalle?id=' . $taskId);
    }

    public function complete(): void
    {
        $this->requireLogin();

        $user = Auth::user();
        $roleName = Auth::roleName() ?? '';
        $taskId = (int) ($_POST['task_id'] ?? 0);

        $task = Task::findWithDetails($taskId);
        if (! $task || ! $this->canViewTask($task, (int) $user['id'], $roleName)) {
            http_response_code(404);
            echo 'Tarea no encontrada.';
            return;
        }

        if ($task['estado'] === 'terminada') {
            $this->redirect('/tareas');
        }

        $hasHours = Task::hasTimeEntries($taskId);
        if (! $hasHours) {
            $fecha = trim($_POST['fecha'] ?? '');
            $horasInput = trim($_POST['horas'] ?? '');
            $horas = $this->parseTimeToHours($horasInput);

            if ($fecha === '' || $horas <= 0 || $horas > 24) {
                $this->redirect('/tareas?error=Debe+registrar+horas&complete=' . $taskId);
            }

            if (Task::timeEntryExists($taskId, $fecha)) {
                $this->redirect('/tareas?error=Ya+hay+horas+para+esa+fecha&complete=' . $taskId);
            }

            Task::addTimeEntry($taskId, $fecha, $horas);
        }

        Task::updateStatus($taskId, 'terminada', date('Y-m-d'));

        $this->redirect('/tareas');
    }

    public function advanceStatus(): void
    {
        $this->requireLogin();

        $user = Auth::user();
        $roleName = Auth::roleName() ?? '';
        $taskId = (int) ($_POST['task_id'] ?? 0);
        $requestedStatus = trim($_POST['estado'] ?? '');
        $returnUrl = $this->safeReturnUrl($_POST['return_url'] ?? null);
        $fecha = trim($_POST['fecha'] ?? '');
        $horasInput = trim($_POST['horas'] ?? '');

        $task = Task::findWithDetails($taskId);
        if (! $task || ! $this->canViewTask($task, (int) $user['id'], $roleName)) {
            $this->respondStatusError('Tarea no encontrada.', 404, $returnUrl);
            return;
        }

        $current = $task['estado'];
        if ($requestedStatus !== '' && in_array($requestedStatus, $this->statusOptions, true)) {
            $next = $requestedStatus;
        } else {
            $map = [
                'pendiente' => 'en_curso',
                'en_curso' => 'terminada',
                'atrasada' => 'en_curso',
            ];
            if (! isset($map[$current])) {
                $this->respondStatusError('No se puede avanzar.', 400, $returnUrl);
                return;
            }
            $next = $map[$current];
        }

        if ($current === 'pendiente' && $next !== 'pendiente' && empty($task['fecha_compromiso'])) {
            $this->respondStatusError('Debes definir fecha de compromiso.', 400, $returnUrl);
            return;
        }

        if ($next === 'terminada' && ! Task::hasTimeEntries($taskId)) {
            $horas = $this->parseTimeToHours($horasInput);
            if ($fecha === '' || $horas <= 0 || $horas > 24) {
                $this->respondStatusError('Debes registrar horas para terminar.', 400, $returnUrl);
                return;
            }
            if (Task::timeEntryExists($taskId, $fecha)) {
                $this->respondStatusError('Ya hay horas para esa fecha.', 400, $returnUrl);
                return;
            }
            Task::addTimeEntry($taskId, $fecha, $horas);
        }

        $fechaTermino = $next === 'terminada' ? date('Y-m-d') : null;

        Task::updateStatus((int) $task['id'], $next, $fechaTermino);

        $payload = ['status' => $next];
        if ($this->expectsJson()) {
            header('Content-Type: application/json');
            echo json_encode($payload);
            return;
        }

        if ($returnUrl) {
            $this->redirect($returnUrl);
        }
        $this->redirect('/tareas');
    }

    private function expectsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return stripos($accept, 'application/json') !== false || $requestedWith === 'fetch';
    }

    private function respondStatusError(string $message, int $code, ?string $returnUrl): void
    {
        if ($this->expectsJson()) {
            http_response_code($code);
            header('Content-Type: application/json');
            echo json_encode(['error' => $message]);
            return;
        }

        if ($returnUrl) {
            $this->redirect($returnUrl . '?error=' . urlencode($message));
        }
        $this->redirect('/tareas?error=' . urlencode($message));
    }

    public function deleteTime(): void
    {
        $this->requireLogin();

        $user = Auth::user();
        $roleName = Auth::roleName() ?? '';
        $taskId = (int) ($_POST['task_id'] ?? 0);
        $timeId = (int) ($_POST['time_id'] ?? 0);

        $task = Task::findWithDetails($taskId);
        if (! $task || ! $this->canViewTask($task, (int) $user['id'], $roleName)) {
            http_response_code(404);
            echo 'Tarea no encontrada.';
            return;
        }

        $isAdmin = Auth::isAdmin();
        if (! $isAdmin && (int) $task['user_id'] !== (int) $user['id']) {
            http_response_code(403);
            echo 'Acceso denegado.';
            return;
        }

        $entry = Task::timeEntryById($timeId);
        if (! $entry || (int) $entry['task_id'] !== $taskId) {
            http_response_code(404);
            echo 'Registro no encontrado.';
            return;
        }

        Task::deleteTimeEntry($timeId);

        $this->redirect('/tareas/detalle?id=' . $taskId);
    }

    public function delete(): void
    {
        $this->requireLogin();

        $user = Auth::user();
        $roleName = Auth::roleName() ?? '';
        $taskId = (int) ($_POST['task_id'] ?? 0);
        $returnUrl = $this->safeReturnUrl($_POST['return_url'] ?? null);

        $task = Task::findWithDetails($taskId);
        if (! $task || ! $this->canViewTask($task, (int) $user['id'], $roleName)) {
            http_response_code(404);
            echo 'Tarea no encontrada.';
            return;
        }

        $isAdmin = Auth::isAdmin();
        if (! $isAdmin && (int) $task['user_id'] !== (int) $user['id']) {
            http_response_code(403);
            echo 'Acceso denegado.';
            return;
        }

        Task::deleteById($taskId);

        if ($returnUrl) {
            $this->redirect($returnUrl);
        }
        $this->redirect('/tareas');
    }

    public function searchUsers(): void
    {
        $this->requireLogin();

        $user = Auth::user();
        $roleName = Auth::roleName() ?? '';
        $term = trim($_GET['q'] ?? '');
        $limit = (int) ($_GET['limit'] ?? 15);

        if (strlen($term) < 2) {
            header('Content-Type: application/json');
            echo json_encode([]);
            return;
        }

        if (Auth::isAdmin()) {
            $results = User::searchForTeam($term, [], $limit);
        } else {
            $visibleIds = Team::visibleUserIdsForRole((int) $user['id'], $roleName);
            $results = User::searchByIds($term, $visibleIds, $limit);
        }

        header('Content-Type: application/json');
        echo json_encode($results);
    }

    public function searchCategories(): void
    {
        $this->requireLogin();

        $term = trim($_GET['q'] ?? '');
        $limit = (int) ($_GET['limit'] ?? 15);

        if (strlen($term) < 2) {
            header('Content-Type: application/json');
            echo json_encode([]);
            return;
        }

        $results = TaskCategory::searchByName($term, $limit);

        header('Content-Type: application/json');
        echo json_encode($results);
    }

    public function copyCategoryTasks(): void
    {
        $this->requireLogin();

        $user = Auth::user();
        $roleName = Auth::roleName() ?? '';
        $fromId = (int) ($_POST['source_category_id'] ?? 0);
        $targetId = (int) ($_POST['target_category_id'] ?? 0);
        $targetName = trim($_POST['target_category_name'] ?? '');

        if ($fromId === 0) {
            $this->redirect('/gestion-tareas?error=Selecciona+una+categoria+origen');
        }

        $targetId = $this->resolveCategoryId($targetId, $targetName, (int) $user['id']);
        if ($targetId === 0) {
            $this->redirect('/gestion-tareas?error=Selecciona+una+categoria+destino');
        }

        $visibleIds = null;
        if (! Auth::isAdmin()) {
            $visibleIds = Team::visibleUserIdsForRole((int) $user['id'], $roleName);
        }
        $count = Task::copyFromCategory($fromId, $targetId, (int) $user['id'], $visibleIds);
        if ($count === 0) {
            $this->redirect('/gestion-tareas?error=No+hay+tareas+para+copiar');
        }

        $this->redirect('/gestion-tareas');
    }

    private function canAssignTo(int $assigneeId, int $actorId, string $roleName): bool
    {
        if (Auth::isAdmin()) {
            return true;
        }

        if ($assigneeId === $actorId) {
            return true;
        }

        if (! in_array($roleName, ['jefe', 'subgerente'], true)) {
            return false;
        }

        $visibleIds = Team::visibleUserIdsForRole($actorId, $roleName);
        return in_array($assigneeId, $visibleIds, true);
    }

    private function canViewTask(array $task, int $actorId, string $roleName): bool
    {
        if (Auth::isAdmin()) {
            return true;
        }

        if (empty($task['user_id']) || (int) $task['user_id'] === 0) {
            return true;
        }

        if ((int) $task['user_id'] === $actorId) {
            return true;
        }

        $visibleIds = Team::visibleUserIdsForRole($actorId, $roleName);
        return in_array((int) $task['user_id'], $visibleIds, true);
    }

    private function assignableUsers(int $userId, string $roleName): array
    {
        if (Auth::isAdmin()) {
            return User::allWithRole();
        }

        if (in_array($roleName, ['jefe', 'subgerente'], true)) {
            $ids = Team::visibleUserIdsForRole($userId, $roleName);
            return User::allWithRoleByIds($ids);
        }

        return User::allWithRoleByIds([$userId]);
    }

    private function parseTimeToHours(string $value): float
    {
        if ($value === '') {
            return 0.0;
        }

        if (strpos($value, ':') !== false) {
            [$hours, $minutes] = array_pad(explode(':', $value, 2), 2, '0');
            $hours = (int) $hours;
            $minutes = (int) $minutes;
            if ($hours < 0 || $minutes < 0 || $minutes > 59) {
                return 0.0;
            }
            return $hours + ($minutes / 60);
        }

        $numeric = (float) $value;
        return $numeric > 0 ? $numeric : 0.0;
    }

    private function safeReturnUrl($value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }
        if (strpos($value, '/') !== 0) {
            return null;
        }
        if (strpos($value, '//') === 0) {
            return null;
        }
        if (strpos($value, '://') !== false) {
            return null;
        }
        if (strpos($value, '\\') !== false) {
            return null;
        }
        return $value;
    }

    private function inferReturnUrl(): ?string
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer === '') {
            return null;
        }
        $parts = parse_url($referer);
        if (! is_array($parts)) {
            return null;
        }
        $path = $parts['path'] ?? '';
        if (! is_string($path) || $path === '') {
            return null;
        }
        $query = $parts['query'] ?? '';
        $value = $path . ($query ? ('?' . $query) : '');
        return $this->safeReturnUrl($value);
    }

    private function resolveCategoryId(int $categoryId, string $categoryName, int $userId): int
    {
        if ($categoryId > 0) {
            return $categoryId;
        }

        if ($categoryName === '') {
            return 0;
        }

        return TaskCategory::findOrCreate($categoryName, $userId);
    }

    /**
     * Vista de gestor de tareas: actividades donde el usuario participa
     */
    public function gestor(): void
    {
        // Soporte temporal para iniciar sesión en entorno local mediante ?as=email para pruebas
        if (isset($_GET['as']) && filter_var($_GET['as'], FILTER_VALIDATE_EMAIL)) {
            $devUser = \App\Models\User::findByEmail($_GET['as']);
            if ($devUser) {
                $_SESSION['user_id'] = $devUser['id'];
            }
        }

        $this->requireLogin();
        $user = Auth::user();
        $userId = (int)($user['id'] ?? 0);
        // Obtener actividades donde el usuario participa
        $activities = TaskCategory::allForUser($userId);
        // Obtener todas las categorías existentes para el dropdown
        $categorias = TaskCategory::allWithCounts();
        $clasificaciones = \App\Models\TaskClassification::all();
        // Obtener rol y admin para equipos
        $roleName = \App\Core\Auth::roleName() ?? '';
        $isAdmin = \App\Core\Auth::isAdmin();
        $equipos = \App\Models\Team::visibleTeamsForRole($userId, $roleName, $isAdmin);
        // Equipo por defecto: primero de la lista o ninguno
        $equipoPorDefecto = !empty($equipos) ? $equipos[0]['id'] : null;
        $this->view('tasks/gestor', [
            'title' => 'Gestor de tareas',
            'activities' => $activities,
            'authUserId' => $userId,
            'clasificaciones' => $clasificaciones,
            'equipos' => $equipos,
            'equipoPorDefecto' => $equipoPorDefecto,
        ]);
    }
}
