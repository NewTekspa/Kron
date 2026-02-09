<?php
/** @var string $title */
/** @var array $tasks */
/** @var array $assignableUsers */
/** @var array $statusOptions */
/** @var array $priorityOptions */
/** @var string|null $error */
/** @var int|null $completeTaskId */
/** @var int $authUserId */
/** @var string $authUserName */
/** @var string $roleName */
ob_start();
$statusLabels = [
    'pendiente' => 'Pendiente',
    'en_curso' => 'En curso',
    'atrasada' => 'Atrasada',
    'congelada' => 'Congelada',
    'terminada' => 'Terminada',
];
$priorityLabels = [
    'baja' => 'Baja',
    'media' => 'Media',
    'alta' => 'Alta',
    'critica' => 'Critica',
];
$formatDate = function (?string $value): string {
    if (! $value) {
        return '-';
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('d-m-Y', $timestamp) : $value;
};
$formatHours = function ($value): string {
    $decimal = (float) $value;
    $hours = (int) floor($decimal);
    $minutes = (int) round(($decimal - $hours) * 60);
    if ($minutes === 60) {
        $hours += 1;
        $minutes = 0;
    }
    return sprintf('%02d:%02d', $hours, $minutes);
};
?>
<div class="page-header">
    <h1>Registro rapido de tareas</h1>
    <div class="hero-actions">
        <button type="button" class="btn" data-open-modal="taskModal">Nueva tarea</button>
    </div>
</div>
<?php if (! empty($error)): ?>
    <div class="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="modal" id="taskModal" data-modal>
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="taskModalTitle">
        <div class="modal-header">
            <h2 id="taskModalTitle">Nueva tarea</h2>
            <button type="button" class="btn btn-secondary btn-small" data-close-modal>Cerrar</button>
        </div>
        <form method="post" action="<?= $basePath ?>/tareas/crear" class="form">
            <label>Titulo</label>
            <input type="text" name="titulo" required>

            <div class="autocomplete">
                <label>Actividad</label>
                <input type="text" name="category_name" data-source="task-categories" autocomplete="off" required>
                <input type="hidden" name="category_id" value="">
                <div class="autocomplete-results"></div>
            </div>

            <?php if (in_array($roleName, ['administrador', 'jefe', 'subgerente'], true)): ?>
                <div class="autocomplete">
                    <label>Asignar a</label>
                    <input type="text" name="user_label" data-source="task-users" autocomplete="off" required>
                    <input type="hidden" name="user_id" value="">
                    <div class="autocomplete-results"></div>
                </div>
            <?php else: ?>
                <label>Asignar a</label>
                <input type="text" name="user_label" value="<?= htmlspecialchars($authUserName) ?>" readonly>
                <input type="hidden" name="user_id" value="<?= (int) $authUserId ?>">
            <?php endif; ?>

            <label>Fecha compromiso estimada</label>
            <input type="date" name="fecha_compromiso" required>

            <label>Prioridad</label>
            <select name="prioridad" required>
                <?php foreach ($priorityOptions as $option): ?>
                    <option value="<?= htmlspecialchars($option) ?>">
                        <?= htmlspecialchars($priorityLabels[$option] ?? $option) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Estado</label>
            <select name="estado" required>
                <?php foreach ($statusOptions as $option): ?>
                    <option value="<?= htmlspecialchars($option) ?>" <?= $option === 'pendiente' ? 'selected' : '' ?>>
                        <?= htmlspecialchars($statusLabels[$option] ?? $option) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="form-actions">
                <button type="submit" class="btn">Guardar tarea</button>
            </div>
        </form>
    </div>
</div>

<table class="table">
    <caption class="table-filter">
        <div class="filter-bar">
            <label for="taskFilter">Filtrar tareas</label>
            <input type="text" id="taskFilter" placeholder="Buscar por titulo, actividad, asignado, estado o prioridad" autocomplete="off">
        </div>
    </caption>
    <thead>
        <tr>
            <th>Titulo</th>
                        <th>Actividad</th>
            <th>Asignado</th>
            <th>Prioridad</th>
            <th>Estado</th>
            <th>Compromiso</th>
            <th>Termino real</th>
            <th>Horas</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($tasks)): ?>
            <tr>
                <td colspan="9" class="muted">Sin tareas registradas.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($tasks as $task): ?>
                <tr>
                    <td><?= htmlspecialchars($task['titulo']) ?></td>
                    <td><?= htmlspecialchars($task['categoria_nombre'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($task['asignado_nombre']) ?></td>
                    <td><?= htmlspecialchars($priorityLabels[$task['prioridad']] ?? $task['prioridad']) ?></td>
                    <td>
                        <?php $statusKey = str_replace('_', '-', $task['estado']); ?>
                        <div class="status-row">
                            <span class="status-badge status-<?= htmlspecialchars($statusKey) ?>">
                                <?= htmlspecialchars($statusLabels[$task['estado']] ?? $task['estado']) ?>
                            </span>
                            <?php if ($task['estado'] !== 'terminada'): ?>
                                <?php if ((int) ($task['time_count'] ?? 0) > 0): ?>
                                    <form method="post" action="<?= $basePath ?>/tareas/terminar" class="inline">
                                        <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                        <button type="submit" class="btn btn-small btn-icon" title="Terminar tarea" aria-label="Terminar tarea">
                                            <svg viewBox="0 0 16 16" aria-hidden="true">
                                                <path fill="currentColor" d="M6.5 11.3 3.2 8l1.1-1.1 2.2 2.2 5.1-5.1L12.8 5z"/>
                                            </svg>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="btn btn-small btn-icon" data-open-modal="completeModal" data-task-id="<?= (int) $task['id'] ?>" title="Terminar tarea" aria-label="Terminar tarea">
                                        <svg viewBox="0 0 16 16" aria-hidden="true">
                                            <path fill="currentColor" d="M6.5 11.3 3.2 8l1.1-1.1 2.2 2.2 5.1-5.1L12.8 5z"/>
                                        </svg>
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($formatDate($task['fecha_compromiso'])) ?></td>
                    <td><?= htmlspecialchars($formatDate($task['fecha_termino_real'] ?? null)) ?></td>
                    <td><?= htmlspecialchars($formatHours($task['total_horas'] ?? 0)) ?></td>
                    <td>
                        <div class="table-actions">
                            <a href="<?= $basePath ?>/tareas/detalle?id=<?= (int) $task['id'] ?>" class="btn btn-secondary btn-small">Detalle</a>
                            <a href="<?= $basePath ?>/tareas/editar?id=<?= (int) $task['id'] ?>" class="btn btn-secondary btn-small">Editar</a>
                            <a href="<?= $basePath ?>/horas/registrar?tarea_id=<?= (int) $task['id'] ?>" class="btn btn-small btn-icon" title="Registrar horas" aria-label="Registrar horas">
                                <img src="<?= $basePath ?>/assets/icons/clock.svg" alt="Registrar horas" width="18" height="18" />
                            </a>
                            <form method="post" action="<?= $basePath ?>/tareas/eliminar" class="inline" onsubmit="return confirm('Eliminar tarea?');">
                                <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-small">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
<div class="modal" id="completeModal" data-modal>
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="completeModalTitle">
        <div class="modal-header">
            <h2 id="completeModalTitle">Registrar horas para terminar</h2>
            <button type="button" class="btn btn-secondary btn-small" data-close-modal>Cerrar</button>
        </div>
        <form method="post" action="<?= $basePath ?>/tareas/terminar" class="form">
            <input type="hidden" name="task_id" value="">
            <label>Fecha</label>
            <input type="date" name="fecha" required>
            <label>Horas (HH:MM)</label>
            <input type="time" name="horas" step="60" required>
            <div class="form-actions">
                <button type="submit" class="btn">Registrar y terminar</button>
            </div>
        </form>
    </div>
</div>
<script>
(() => {
    const setupModal = (modalId, onOpen) => {
        const modal = document.getElementById(modalId);
        if (!modal) {
            return null;
        }
        const openButtons = document.querySelectorAll(`[data-open-modal="${modalId}"]`);
        const closeTargets = modal.querySelectorAll('[data-close-modal]');
        const openModal = (event) => {
            modal.classList.add('is-open');
            if (onOpen) {
                onOpen(event, modal);
            }
        };
        const closeModal = () => {
            modal.classList.remove('is-open');
        };

        openButtons.forEach((btn) => btn.addEventListener('click', openModal));
        closeTargets.forEach((btn) => btn.addEventListener('click', closeModal));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        return { openModal, closeModal };
    };

    const taskModal = setupModal('taskModal', (_, modal) => {
        const input = modal.querySelector('input[name="titulo"]');
        if (input) {
            input.focus();
        }
    });

    const completeModal = setupModal('completeModal', (event, modal) => {
        const button = event?.currentTarget;
        const taskId = button?.getAttribute('data-task-id') || '';
        const hidden = modal.querySelector('input[name="task_id"]');
        if (hidden) {
            hidden.value = taskId;
        }
        const dateInput = modal.querySelector('input[name="fecha"]');
        if (dateInput && !dateInput.value) {
            dateInput.value = new Date().toISOString().slice(0, 10);
        }
        const timeInput = modal.querySelector('input[name="horas"]');
        if (timeInput) {
            timeInput.focus();
        }
    });

    const hasError = <?= ! empty($error) ? 'true' : 'false' ?>;
    const completeTaskId = <?= $completeTaskId ? (int) $completeTaskId : 'null' ?>;
    if (completeTaskId && completeModal) {
        completeModal.openModal({ currentTarget: { getAttribute: () => String(completeTaskId) } });
    } else if (hasError && taskModal) {
        taskModal.openModal();
    }

    const debounce = (fn, wait = 300) => {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), wait);
        };
    };

    const initAutocomplete = (wrapper) => {
        const input = wrapper.querySelector('input[type="text"]');
        const hidden = wrapper.querySelector('input[type="hidden"]');
        const results = wrapper.querySelector('.autocomplete-results');
        if (!input || !hidden || !results) {
            return;
        }

        const fetchItems = async (term, source, limit = 15) => {
            const params = new URLSearchParams({ q: term, limit });
        const endpoint = source === 'task-categories' ? '<?= $basePath ?>/tareas/buscar-categorias' : '<?= $basePath ?>/tareas/buscar-usuarios';
                return [];
            }
            return res.json();
        };

        const renderResults = (items) => {
            results.innerHTML = '';
            if (!items.length) {
                return;
            }
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
            if (term.length < 2) {
                results.innerHTML = '';
                return;
            }
            const items = await fetchItems(term, input.dataset.source);
            renderResults(items);
        });

        input.addEventListener('input', handle);
        document.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target)) {
                results.innerHTML = '';
            }
        });
    };

    const guardForm = (form, fields) => {
        if (!form) {
            return;
        }
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

    document.querySelectorAll('.autocomplete').forEach(initAutocomplete);
    guardForm(document.querySelector('form[action="<?= $basePath ?>/tareas/crear"]'), ['user_id']);

    const filterInput = document.getElementById('taskFilter');
    if (filterInput) {
        const rows = Array.from(document.querySelectorAll('table tbody tr'));
        const handleFilter = debounce(() => {
            const term = filterInput.value.trim().toLowerCase();
            rows.forEach((row) => {
                const haystack = row.textContent.toLowerCase();
                row.style.display = haystack.includes(term) ? '' : 'none';
            });
        }, 150);
        filterInput.addEventListener('input', handleFilter);
    }
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
