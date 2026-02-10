<?php
/** @var array $actividad */
/** @var array $tareas */
/** @var int $authUserId */
ob_start();
?>
<div class="page-header">
    <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, var(--blue) 0%, var(--blue-light) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
        </div>
        <h1 style="margin: 0;">Actividad: <?= htmlspecialchars($actividad['nombre']) ?></h1>
    </div>
    <div class="muted" style="margin-left: 60px; font-size: 15px;">
        Clasificación: <strong><?= htmlspecialchars($actividad['classification_id'] ?? '-') ?></strong>
    </div>
    <div class="hero-actions">
        <a href="<?= $basePath ?>/tareas/gestor" class="btn btn-secondary">Volver</a>
    </div>
</div>
<div class="dashboard-top">
    <div class="dashboard-col" style="flex: 2 1 0; min-width: 900px; max-width: 1200px; margin: 0 auto;">
        <section class="card-block dashboard-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: var(--shadow); min-height: 320px;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0 0 4px;">Tareas de la actividad</h2>
                <button type="button" class="btn" data-open-modal="taskModal">Nueva tarea</button>
            </div>
            <!-- Filtros horizontales -->
            <div style="display:flex;align-items:center;gap:24px;margin-bottom:16px;">
                <form id="filtro-tareas" method="get" action="" style="display:flex;align-items:center;gap:8px;">
                    <input type="hidden" name="category_id" value="<?= (int)$actividad['id'] ?>">
                    <label for="estado" style="font-weight:500;">Estado:</label>
                    <select name="estado" id="estado" style="min-width:120px;">
                        <option value="">Todos</option>
                        <option value="pendiente" <?= (isset($_GET['estado']) && $_GET['estado']==='pendiente')?'selected':'' ?>>Pendiente</option>
                        <option value="en_curso" <?= (isset($_GET['estado']) && $_GET['estado']==='en_curso')?'selected':'' ?>>En curso</option>
                        <option value="congelada" <?= (isset($_GET['estado']) && $_GET['estado']==='congelada')?'selected':'' ?>>Congelada</option>
                        <option value="atrasada" <?= (isset($_GET['estado']) && $_GET['estado']==='atrasada')?'selected':'' ?>>Atrasada</option>
                        <option value="terminada" <?= (isset($_GET['estado']) && $_GET['estado']==='terminada')?'selected':'' ?>>Terminada</option>
                    </select>
                    <label for="buscar" style="font-weight:500;">Buscar:</label>
                    <input type="text" name="buscar" id="buscar" value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>" placeholder="Título de la tarea" style="min-width:180px;">
                    <button type="submit" class="btn btn-small" title="Filtrar">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="4" x2="20" y2="4"/><line x1="4" y1="10" x2="20" y2="10"/><line x1="4" y1="16" x2="14" y2="16"/></svg>
                    </button>
                </form>
            </div>
            <div class="table-wrap" style="margin-top: 24px;">
                <table class="table table-compact">
                    <thead>
                        <tr>
                            <th style="min-width:180px;">Título</th>
                            <th style="min-width:110px;">Prioridad</th>
                            <th style="min-width:140px;">Asignado</th>
                            <th style="min-width:140px;">Fecha compromiso</th>
                            <th style="min-width:110px;">Total horas</th>
                            <th style="min-width:110px;">Estado</th>
                            <th style="min-width:160px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Filtrar por estado y por texto si están seleccionados
                        $estadoFiltro = $_GET['estado'] ?? '';
                        $buscarFiltro = trim($_GET['buscar'] ?? '');
                        $tareasFiltradas = $tareas;
                        if ($estadoFiltro) {
                            $tareasFiltradas = array_filter($tareasFiltradas, function($t) use ($estadoFiltro) {
                                return isset($t['estado']) && $t['estado'] === $estadoFiltro;
                            });
                        }
                        if ($buscarFiltro) {
                            $tareasFiltradas = array_filter($tareasFiltradas, function($t) use ($buscarFiltro) {
                                return isset($t['titulo']) && stripos($t['titulo'], $buscarFiltro) !== false;
                            });
                        }
                        ?>
                        <?php if (!empty($tareasFiltradas)): ?>
                            <?php foreach ($tareasFiltradas as $tarea): ?>
                                <?php
                                // Calcular total de horas para la tarea
                                $totalHoras = 0;
                                if (!empty($tarea['id'])) {
                                    $horasReg = \App\Models\User::getHourEntries(0, $tarea['id']);
                                    foreach ($horasReg as $hr) {
                                        $totalHoras += floatval($hr['horas']);
                                    }
                                }
                                // Formatear a HH:MM
                                $h = floor($totalHoras);
                                $m = round(($totalHoras - $h) * 60);
                                $totalHHMM = sprintf('%02d:%02d', $h, $m);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($tarea['titulo']) ?></td>
                                    <td><?= htmlspecialchars($tarea['prioridad']) ?></td>
                                    <td><?= htmlspecialchars($tarea['asignado_nombre']) ?></td>
                                    <td><?= isset($tarea['fecha_compromiso']) && $tarea['fecha_compromiso'] ? date('d-m-Y', strtotime($tarea['fecha_compromiso'])) : '-' ?></td>
                                    <td><?= $totalHHMM ?> h</td>
                                    <td><?= htmlspecialchars($tarea['estado']) ?></td>
                                    <td style="text-align:center; min-width: 320px; max-width: 500px;">
                                        <div style="display: flex; flex-direction: row; gap: 10px; align-items: center; justify-content: center; flex-wrap: nowrap; width: 100%; white-space: nowrap;">
                                            <a href="<?= $basePath ?>/horas/registrar?tarea_id=<?= (int)$tarea['id'] ?>&return_url=<?= urlencode($basePath . '/tareas/actividad?category_id=' . (int)$actividad['id']) ?>" class="btn btn-small btn-icon" title="Registrar horas" aria-label="Registrar horas">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10"/>
                                                    <polyline points="12 6 12 12 16 14"/>
                                                </svg>
                                            </a>
                                            <a href="<?= $basePath ?>/tareas/editar?id=<?= (int)$tarea['id'] ?>&return=<?= urlencode($basePath . '/tareas/actividad?category_id=' . (int)$actividad['id']) ?>" class="btn btn-small btn-icon" title="Editar tarea">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </a>
                                            <a href="<?= $basePath ?>/tareas/detalle-informativo?id=<?= (int)$tarea['id'] ?>" class="btn btn-small btn-icon" title="Ver detalle informativo" aria-label="Ver detalle informativo">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                </svg>
                                            </a>
                                        </div>
                                        <?php
                                        // Verificar si la tarea tiene horas registradas
                                        $tareaTieneHoras = false;
                                        if (!empty($tarea['id'])) {
                                            $tareaTieneHoras = \App\Models\Task::hasTimeEntries($tarea['id']);
                                        }
                                        ?>
                                        <form method="post" action="<?= $basePath ?>/tareas/estado" class="inline" style="display:inline;"
                                            onsubmit="<?= !$tareaTieneHoras ? "alert('Debes registrar horas antes de cerrar la tarea.'); return false;" : "return confirm('¿Marcar tarea como terminada?');" ?>">
                                            <input type="hidden" name="task_id" value="<?= (int)$tarea['id'] ?>">
                                            <input type="hidden" name="estado" value="terminada">
                                            <input type="hidden" name="return_url" value="<?= $basePath ?>/tareas/actividad?category_id=<?= (int)$actividad['id'] ?>">
                                            <button type="submit" class="btn btn-success btn-small btn-icon" title="Cerrar tarea" <?= !$tareaTieneHoras ? 'disabled' : '' ?>>
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                            </button>
                                        </form>
                                        <form method="post" action="<?= $basePath ?>/tareas/eliminar" class="inline" style="display:inline;" onsubmit="return confirm('¿Eliminar tarea?');">
                                            <input type="hidden" name="task_id" value="<?= (int)$tarea['id'] ?>">
                                            <input type="hidden" name="return_url" value="<?= $basePath ?>/tareas/actividad?category_id=<?= (int)$actividad['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-small btn-icon" title="Eliminar tarea">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="muted">No hay tareas asociadas a esta actividad.</td></tr>
                        <?php endif; ?>
                    </tbody>
                <table style="min-width: 1200px; max-width: 100%; width: 100%;">
            </div>
        </section>
    </div>
</div>

<!-- Modal para nueva tarea -->
<div class="modal" id="taskModal" data-modal>
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="taskModalTitle">
        <div class="modal-header">
            <h2 id="taskModalTitle">Nueva tarea</h2>
            <button type="button" class="btn btn-secondary btn-small" data-close-modal>Cerrar</button>
        </div>
        <form method="post" action="<?= $basePath ?>/tareas/crear" class="form">
            <input type="hidden" name="category_id" value="<?= (int)$actividad['id'] ?>">
            <input type="hidden" name="category_name" value="<?= htmlspecialchars($actividad['nombre']) ?>">
            <input type="hidden" name="return_url" value="<?= $basePath ?>/tareas/actividad?category_id=<?= (int)$actividad['id'] ?>">
            <label>Título</label>
            <input type="text" name="titulo" required>

            <div class="autocomplete">
                <label>Asignar a</label>
                <input type="text" name="user_label" data-source="task-users" autocomplete="off" required>
                <input type="hidden" name="user_id" value="">
                <div class="autocomplete-results"></div>
            </div>

            <label>Fecha compromiso estimada</label>
            <input type="date" name="fecha_compromiso" required>

            <label>Prioridad</label>
            <select name="prioridad" required>
                <option value="baja">Baja</option>
                <option value="media">Media</option>
                <option value="alta">Alta</option>
                <option value="critica">Crítica</option>
            </select>

            <label>Estado</label>
            <select name="estado" required>
                <option value="pendiente" selected>Pendiente</option>
                <option value="en_curso">En curso</option>
                <option value="congelada">Congelada</option>
                <option value="atrasada">Atrasada</option>
                <option value="terminada">Terminada</option>
            </select>

            <div class="form-actions">
                <button type="submit" class="btn">Guardar tarea</button>
            </div>
        </form>
    </div>
</div>
<script>
(() => {
    const setupModal = (modalId, onOpen) => {
        const modal = document.getElementById(modalId);
        if (!modal) return null;
        const openButtons = document.querySelectorAll(`[data-open-modal="${modalId}"]`);
        const closeTargets = modal.querySelectorAll('[data-close-modal]');
        const openModal = (event) => {
            modal.classList.add('is-open');
            if (onOpen) onOpen(event, modal);
        };
        const closeModal = () => { modal.classList.remove('is-open'); };
        openButtons.forEach((btn) => btn.addEventListener('click', openModal));
        closeTargets.forEach((btn) => btn.addEventListener('click', closeModal));
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeModal(); });
        return { openModal, closeModal };
    };
    const taskModal = setupModal('taskModal', (_, modal) => {
        const input = modal.querySelector('input[name="titulo"]');
        if (input) input.focus();
    });
    const debounce = (fn, wait = 300) => {
        let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
    };
    const initAutocomplete = (wrapper) => {
        const input = wrapper.querySelector('input[type="text"]');
        const hidden = wrapper.querySelector('input[type="hidden"]');
        const results = wrapper.querySelector('.autocomplete-results');
        if (!input || !hidden || !results) return;
        const fetchItems = async (term, source, limit = 15) => {
            const params = new URLSearchParams({ q: term, limit });
            const endpoint = source === 'task-categories' ? '<?= $basePath ?>/tareas/buscar-categorias' : '<?= $basePath ?>/tareas/buscar-usuarios';
            const res = await fetch(`${endpoint}?${params.toString()}`);
            if (!res.ok) return [];
            return res.json();
        };
        const renderResults = (items) => {
            results.innerHTML = '';
            if (!items.length) return;
            items.forEach((item) => {
                const row = document.createElement('div');
                row.className = 'autocomplete-item';
                row.textContent = item.email ? `${item.nombre} (${item.email})` : item.nombre;
                row.addEventListener('click', () => {
                    input.value = row.textContent;
                    hidden.value = item.id;
                    results.innerHTML = '';
                });
                results.appendChild(row);
            });
        };
        const handle = debounce(async () => {
            const term = input.value.trim();
            hidden.value = '';
            if (term.length < 2) { results.innerHTML = ''; return; }
            const items = await fetchItems(term, input.dataset.source);
            renderResults(items);
        });
        input.addEventListener('input', handle);
        document.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target)) results.innerHTML = '';
        });
    };
    document.querySelectorAll('.autocomplete').forEach(initAutocomplete);
    const guardForm = (form, fields) => {
        if (!form) return;
        form.addEventListener('submit', (event) => {
            for (const name of fields) {
                if (!form.querySelector(`input[name="${name}"]`)?.value) {
                    event.preventDefault();
                    alert('Selecciona un valor desde la lista.');
                    return;
                }
            }
        });
    };
    guardForm(document.querySelector('form[action$="/tareas/crear"]'), ['user_id']);
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
