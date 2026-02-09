<div class="page-header">
    <h1 style="display: flex; align-items: center; gap: 12px;">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--blue);">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
        </svg>
        Gestión de tareas
    </h1>
</div>
<div class="page-header">
    <h1>Gestión de tareas</h1>
</div>
<?php
/** @var string $title */
/** @var array $categories */
/** @var array $classifications */
/** @var string|null $error */
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
$formatPercent = function ($done, $total): string {
    $total = (int) $total;
    if ($total <= 0) {
        return '0%';
    }
    $done = (int) $done;
    $percent = (int) round(($done / $total) * 100);
    return $percent . '%';
};
$missingDateCount = 0;
foreach ($categories as $category) {
    $missingDateCount += (int) ($category['sin_fecha'] ?? 0);
}
// Filtrado backend de categorías
$categoryFilter = isset($_GET['categoryFilter']) ? trim(mb_strtolower($_GET['categoryFilter'])) : '';
$classificationFilter = isset($_GET['classificationFilter']) ? $_GET['classificationFilter'] : '';

$filteredCategories = array_filter($categories, function($cat) use ($categoryFilter, $classificationFilter) {
    $nombre = mb_strtolower($cat['nombre'] ?? '');
    $clasificacion = mb_strtolower($cat['clasificacion_nombre'] ?? '');
    $clasificacionId = (string)($cat['classification_id'] ?? '');
    $matchText = $categoryFilter === '' || strpos($nombre, $categoryFilter) !== false || strpos($clasificacion, $categoryFilter) !== false;
    $matchClasif = $classificationFilter === '' || $classificationFilter === $clasificacionId;
    return $matchText && $matchClasif;
});

$missingDateCount = 0;
foreach ($filteredCategories as $category) {
    $missingDateCount += (int) ($category['sin_fecha'] ?? 0);
}
?>
<div class="page-header">
    <h1>Gestion de tarea</h1>
    <!-- INICIO BLOQUE SCRIPT KRON -->
    <script>
<?php if (! empty($error)): ?>
    <div class="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="hero-actions">
    <button type="button" class="btn" data-open-modal="categoryModal">Nueva actividad</button>
</div>

<div class="stack-grid">
    <div class="card-block">
        <h2>Actividades</h2>
        <div style="background:#f8f9fa; border-radius:8px; padding:12px 16px; margin-bottom:16px; border:1px solid #e5e7eb;">
            <form method="get" style="margin:0;">
                <div style="display:flex; flex-direction:row; align-items:center; gap:12px; flex-wrap:nowrap;">
                    <input type="text" name="categoryFilter" id="categoryFilter" placeholder="Buscar actividad" autocomplete="off" value="<?= htmlspecialchars($_GET['categoryFilter'] ?? '') ?>" style="flex:0 0 auto; width:200px; height:36px; font-size:15px; border:1px solid #d1d5db; border-radius:6px; padding:0 12px;">
                    <select name="classificationFilter" id="classificationFilter" style="flex:0 0 auto; width:150px; height:36px; font-size:15px; border:1px solid #d1d5db; border-radius:6px; padding:0 12px;">
                        <option value="">Todas</option>
                        <option value="normativo"<?= (isset($_GET['classificationFilter']) && $_GET['classificationFilter'] === 'normativo') ? ' selected' : '' ?>>Normativo</option>
                        <?php foreach ($classifications as $classification): ?>
                            <option value="<?= (int) $classification['id'] ?>"<?= (isset($_GET['classificationFilter']) && $_GET['classificationFilter'] == $classification['id']) ? ' selected' : '' ?>><?= htmlspecialchars($classification['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" id="applyFilterBtn" title="Aplicar filtro" aria-label="Aplicar filtro" style="flex:0 0 auto; background:#2563eb; color:#fff; border-radius:6px; border:none; padding:8px 12px; height:36px; min-width:36px; display:flex; align-items:center; justify-content:center; cursor:pointer;">
                        <svg width="18" height="18" viewBox="0 0 16 16" aria-hidden="true"><path fill="currentColor" d="M2 3a1 1 0 0 1 1-1h10a1 1 0 0 1 .8 1.6l-3.6 5.4V13a1 1 0 0 1-2 0V10L2.2 4.6A1 1 0 0 1 2 3z"/></svg>
                    </button>
                </div>
            </form>
        </div>
        <?php if (empty($filteredCategories)): ?>
            <p class="muted">No hay actividades registradas.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Actividad</th>
                        <th>Clasificacion</th>
                        <th style="display:none;">Equipo</th>
                        <th class="table-center">Total</th>
                        <th class="table-center">Pendientes</th>
                        <th class="table-center">En curso</th>
                        <th class="table-center">Atrasadas</th>
                        <th class="table-center">Terminadas</th>
                        <th class="table-center">Horas</th>
                        <th class="table-center">Avance</th>
                        <th class="table-center">Estado</th>
                        <th class="table-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filteredCategories as $category): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($category['nombre']) ?>
                                <?php if ((int) ($category['sin_fecha'] ?? 0) > 0): ?>
                                    <span class="warn-icon" title="Tiene tareas sin fecha de compromiso" aria-label="Tiene tareas sin fecha de compromiso">
                                        <svg viewBox="0 0 16 16" aria-hidden="true">
                                            <path fill="currentColor" d="M8 1.2 15.4 14H.6L8 1.2zm0 3.1L3.1 12.4h9.8L8 4.3zm.9 2.6v3.8H7.1V6.9h1.8zm0 4.6v1.8H7.1v-1.8h1.8z"/>
                                        </svg>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td data-classification-id="<?= (int) ($category['classification_id'] ?? 0) ?>">
                                <?= htmlspecialchars($category['clasificacion_nombre'] ?? '-') ?>
                            </td>
                            <td style="display:none;"><?= htmlspecialchars($category['equipo_nombre'] ?? '-') ?></td>
                            <td class="table-center"><?= htmlspecialchars($category['tareas'] ?? 0) ?></td>
                            <td class="table-center"><?= htmlspecialchars($category['pendientes'] ?? 0) ?></td>
                            <td class="table-center"><?= htmlspecialchars($category['en_curso'] ?? 0) ?></td>
                            <td class="table-center"><?= htmlspecialchars($category['atrasadas'] ?? 0) ?></td>
                            <td class="table-center"><?= htmlspecialchars($category['terminadas'] ?? 0) ?></td>
                            <td class="table-center"><?= htmlspecialchars($formatHours($category['horas_total'] ?? 0)) ?></td>
                            <td class="table-center"><?= htmlspecialchars($formatPercent($category['terminadas'] ?? 0, $category['tareas'] ?? 0)) ?></td>
                            <td class="table-center">
                                <?php
                                $totalTareas = (int)($category['tareas'] ?? 0);
                                $terminadas = (int)($category['terminadas'] ?? 0);
                                // Estado: "Activa" si hay tareas no terminadas o no hay tareas, "Terminada" si todas las tareas están cerradas/terminadas
                                if ($totalTareas === 0 || $terminadas < $totalTareas) {
                                    echo '<span style="color:#2563eb;font-weight:bold;">Activa</span>';
                                } else {
                                    echo '<span style="color:#16a34a;font-weight:bold;">Terminada</span>';
                                }
                                ?>
                            </td>
                            <td class="table-center">
                                <div class="table-actions" style="display:flex;gap:8px;justify-content:center;align-items:center;">
                                    <!-- Icono editar -->
                                    <button type="button" class="btn btn-icon btn-edit-category" title="Editar actividad" aria-label="Editar actividad"
                                        data-category-id="<?= (int) $category['id'] ?>"
                                        data-category-name="<?= htmlspecialchars($category['nombre']) ?>"
                                        data-category-classification="<?= (int) ($category['classification_id'] ?? 0) ?>"
                                        data-category-team="<?= (int) ($category['team_id'] ?? 0) ?>">
                                        <svg width="20" height="20" viewBox="0 0 16 16" aria-hidden="true"><path fill="currentColor" d="M12.3 2.3a1 1 0 0 1 1.4 1.4l-8.6 8.6a1 1 0 0 1-.7.3H2v-2.4a1 1 0 0 1 .3-.7l8.6-8.6zM2 13.5a.5.5 0 0 1-.5-.5V12h2v2H2.5a.5.5 0 0 1-.5-.5z"/></svg>
                                    </button>
                                    <!-- Icono eliminar -->
                                    <form method="post" action="<?= $basePath ?>/gestion-tareas/categorias/eliminar" class="inline" onsubmit="return confirm('¿Eliminar actividad?');" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                                        <button type="submit" class="btn btn-icon btn-danger" title="Eliminar actividad" aria-label="Eliminar actividad">
                                            <svg width="20" height="20" viewBox="0 0 16 16" aria-hidden="true"><path fill="currentColor" d="M6.5 1a1.5 1.5 0 0 1 3 0H14a1 1 0 1 1 0 2h-1v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V3H2a1 1 0 1 1 0-2h3.5zm5.5 2H4v10a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V3z"/>
                                        </button>
                                    </form>
                                    <!-- Icono ver tareas de la actividad -->
                                    <button type="button" class="btn btn-icon" title="Ver tareas de la actividad" aria-label="Ver tareas"
                                        data-open-modal="tasksModal"
                                        data-category-id="<?= (int) $category['id'] ?>"
                                        data-category-name="<?= htmlspecialchars($category['nombre']) ?>">
                                        <svg width="20" height="20" viewBox="0 0 16 16" aria-hidden="true"><path fill="currentColor" d="M8 3.2c3.4 0 6.1 2.3 7.2 4.8-1.1 2.5-3.8 4.8-7.2 4.8S1.9 10.5.8 8C1.9 5.5 4.6 3.2 8 3.2zm0 1.6c-2.4 0-4.5 1.5-5.4 3.2.9 1.7 3 3.2 5.4 3.2s4.5-1.5 5.4-3.2c-.9-1.7-3-3.2-5.4-3.2zm0 1.6a2.4 2.4 0 1 1 0 4.8 2.4 2.4 0 0 1 0-4.8z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>
<div class="modal" id="categoryModal" data-modal>
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="categoryModalTitle">
        <div class="modal-header">
            <h2 id="categoryModalTitle">Nueva actividad</h2>
            <button type="button" class="btn btn-secondary btn-small" data-close-modal>Cerrar</button>
        </div>
        <form method="post" action="<?= $basePath ?>/gestion-tareas/categorias/crear" class="form" data-category-form>
            <input type="hidden" name="id" value="">
            <label>Nombre</label>
            <input type="text" name="nombre" required>
            <label>Tipo/Categoría de actividad</label>
            <select name="classification_id" required>
                <option value="">Selecciona tipo/categoría</option>
                <?php foreach ($classifications as $classification): ?>
                    <option value="<?= (int) $classification['id'] ?>"><?= htmlspecialchars($classification['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Equipo asignado</label>
            <select name="team_id" required>
                <option value="">Selecciona equipo</option>
                <?php foreach ($teams as $team): ?>
                    <option value="<?= (int) $team['id'] ?>"><?= htmlspecialchars($team['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-actions">
                <button type="submit" class="btn">Guardar</button>
            </div>
        </form>
    </div>
</div>
<div class="modal" id="tasksModal" data-modal>
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-card modal-card-xl" role="dialog" aria-modal="true" aria-labelledby="tasksModalTitle">
        <div class="modal-header">
            <h2 id="tasksModalTitle">Tareas de actividad</h2>
            <button type="button" class="btn btn-secondary btn-small" data-close-modal>Cerrar</button>
        </div>
        <div class="hero-actions">
            <button type="button" class="btn" data-open-modal="taskQuickModal" id="openTaskQuick">Nueva tarea</button>
        </div>
        <div class="filter-bar">
            <label for="tasksModalFilter">Filtrar tareas</label>
            <input type="text" id="tasksModalFilter" placeholder="Buscar por titulo, asignado o estado" autocomplete="off">
        </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Titulo</th>
                        <th>Asignado</th>
                        <th>Estado</th>
                        <th>Compromiso</th>
                        <th>Horas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tasksModalBody"></tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal" id="taskQuickModal" data-modal>
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="taskQuickModalTitle">
        <div class="modal-header">
            <h2 id="taskQuickModalTitle">Nueva tarea</h2>
            <button type="button" class="btn btn-secondary btn-small" data-close-modal>Cerrar</button>
        </div>
        <form method="post" action="<?= $basePath ?>/tareas/crear" class="form" id="taskQuickForm" data-auth-user-id="<?= (int) ($authUser['id'] ?? 0) ?>">
            <input type="hidden" name="category_id" value="">
            <input type="hidden" name="return_url" value="">
            <label>Actividad</label>
            <select name="category_id" id="taskCategorySelect" class="combobox" required style="width:100%; max-width:100%;">
                <option value="">Selecciona una actividad</option>
                <?php foreach ($categories as $cat): ?>
                    <?php if (isset($authUser['team_id']) && $cat['team_id'] == $authUser['team_id']): ?>
                        <option value="<?= (int)$cat['id'] ?>" data-category-name="<?= htmlspecialchars($cat['nombre']) ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <label>Titulo</label>
            <input type="text" name="titulo" required>
            <label>Asignar a</label>
            <input type="text" name="user_label" value="<?= htmlspecialchars($authUser['nombre'] ?? '') ?>" readonly>
            <input type="hidden" name="user_id" value="<?= (int) ($authUser['id'] ?? 0) ?>">
            <script>
            // Combobox simple para filtrar actividades por texto
            document.addEventListener('DOMContentLoaded', function() {

    // FIN BLOQUE SCRIPT KRON
                var select = document.getElementById('taskCategorySelect');
                if (select) {
                    select.addEventListener('change', function() {
                        // Opcional: lógica adicional al seleccionar
                    });
                    // Agregar filtro por texto
                    var input = document.createElement('input');
                    input.type = 'text';
                    input.placeholder = 'Buscar actividad...';
                    input.style.marginBottom = '8px';
                    input.style.width = '100%';
                    select.parentNode.insertBefore(input, select);
                    input.addEventListener('keyup', function() {
                        var filter = input.value.toLowerCase();
                        for (var i = 0; i < select.options.length; i++) {
                            var option = select.options[i];
                            if (i === 0) continue; // Saltar placeholder
                            option.style.display = option.text.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
                        }
                    });
                }

                // Bloquear selección de actividad si se abre desde el modal de tareas de una actividad
                const taskQuickModal = document.getElementById('taskQuickModal');
                if (taskQuickModal) {
                    const observer = new MutationObserver(() => {
                        if (taskQuickModal.classList.contains('is-open')) {
                            // Detectar si hay una actividad seleccionada en el modal de tareas
                            const tasksModal = document.getElementById('tasksModal');
                            const catId = tasksModal?.dataset?.categoryId;
                            if (catId && select) {
                                select.value = catId;
                                select.disabled = true;
                            } else {
                                select.value = '';
                                select.disabled = false;
                            }
                        }
                    });
                    observer.observe(taskQuickModal, { attributes: true });
                }
            });
            </script>
            <label>Fecha compromiso estimada</label>
            <input type="date" name="fecha_compromiso" required>
            <label>Prioridad</label>
            <select name="prioridad" required>
                <option value="baja">Baja</option>
                <option value="media" selected>Media</option>
                <option value="alta">Alta</option>
                <option value="critica">Critica</option>
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
<div class="modal" id="copyModal" data-modal>
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="copyModalTitle">
        <div class="modal-header">
            <h2 id="copyModalTitle">Copiar tareas entre actividades</h2>
            <button type="button" class="btn btn-secondary btn-small" data-close-modal>Cerrar</button>
        </div>
        <form method="post" action="<?= $basePath ?>/tareas/categorias/copiar" class="form" data-copy-form>
            <label>Actividad origen</label>
            <input type="text" name="source_category_label" readonly>
            <input type="hidden" name="source_category_id" value="">

            <div class="autocomplete">
                <label>Actividad destino</label>
                <input type="text" name="target_category_name" data-source="task-categories" autocomplete="off" required>
                <input type="hidden" name="target_category_id" value="">
                <div class="autocomplete-results"></div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">Copiar tareas</button>
            </div>
        </form>
    </div>
</div>
<style>
    /* Modal más grande */
    #tasksModal .modal-card {
        max-width: 900px;
                const inputs = form.querySelectorAll('input:not([type="hidden"])');
                inputs.forEach(input => {
                    if (input.type !== 'submit' && input.type !== 'button') {
                        input.value = '';
                    }
                });
                const selects = form.querySelectorAll('select');
                selects.forEach(select => select.selectedIndex = 0);
            });
        };

        openButtons.forEach((btn) => {
            btn.addEventListener('click', openModal);
            btn.dataset.modalListenerAttached = 'true';
        });
        closeTargets.forEach((btn) => btn.addEventListener('click', closeModal));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        return { openModal, closeModal };
    };

    // Variable global para guardar el último botón clickeado
    let lastClickedButton = null;

    // Capturar clics en TODOS los botones que abren el modal de categoría
    document.addEventListener('click', (event) => {
        const target = event.target.closest('[data-open-modal="categoryModal"]');
        if (target) {
            lastClickedButton = target;
            console.log('Botón clickeado para abrir categoryModal:', target);
        }
    }, true); // useCapture = true para capturar antes que otros listeners

    // Observar cuando el modal se abre para configurarlo
    const categoryModal = document.getElementById('categoryModal');
    if (categoryModal) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    if (categoryModal.classList.contains('is-open')) {
                        console.log('Modal categoryModal abierto, configurando...');
                        
                        const form = categoryModal.querySelector('[data-category-form]');
                        const idInput = form?.querySelector('input[name="id"]');
                        const input = categoryModal.querySelector('input[name="nombre"]');
                        const classificationSelect = categoryModal.querySelector('select[name="classification_id"]');
                        const teamSelect = categoryModal.querySelector('select[name="team_id"]');
                        const title = categoryModal.querySelector('#categoryModalTitle');
                        
                        let categoryId = '';
                        let categoryName = '';
                        let classificationId = '';
                        let teamId = '';
                        
                        // Obtener datos del último botón clickeado
                        if (lastClickedButton) {
                            categoryId = lastClickedButton.getAttribute('data-category-id') || '';
                            categoryName = lastClickedButton.getAttribute('data-category-name') || '';
                            classificationId = lastClickedButton.getAttribute('data-category-classification') || '';
                            teamId = lastClickedButton.getAttribute('data-category-team') || '';
                        }
                        
                        console.log('Datos obtenidos:', { categoryId, categoryName, classificationId, teamId });
                        
                        // Configurar formulario para edición o creación
                        if (form && idInput) {
                            if (categoryId) {
                                form.action = '<?= $basePath ?>/gestion-tareas/categorias/actualizar';
                                idInput.value = categoryId;
                                if (title) title.textContent = 'Editar actividad: ' + (categoryName || '');
                            } else {
                                form.action = '<?= $basePath ?>/gestion-tareas/categorias/crear';
                                idInput.value = '';
                                if (title) title.textContent = 'Nueva actividad';
                            }
                        }
                        
                        // Llenar campos
                        if (input) {
                            input.value = categoryName || '';
                            setTimeout(() => input.focus(), 100);
                        }
                        if (classificationSelect) {
                            classificationSelect.value = classificationId || '';
                        }
                        if (teamSelect) {
                            teamSelect.value = teamId || '';
                        }
                    }
                }
            });
        });
        
        observer.observe(categoryModal, { attributes: true });
    }

    setupModal('categoryModal', (event, modal) => {
        // El MutationObserver se encargará de la configuración
    });


    const tasksModal = setupModal('tasksModal', (event, modal) => {
        const button = event?.currentTarget;
        const categoryId = button?.getAttribute('data-category-id') || '';
        const categoryName = button?.getAttribute('data-category-name') || '';
        const title = modal.querySelector('#tasksModalTitle');
        if (title) {
            title.textContent = `Tareas: ${categoryName}`;
        }
        modal.dataset.categoryId = categoryId;
        modal.dataset.returnUrl = `<?= $basePath ?>/gestion-tareas?open_categoria_id=${encodeURIComponent(categoryId)}`;
        const body = modal.querySelector('#tasksModalBody');
        if (body) {
            body.innerHTML = '<tr><td colspan="6" class="muted">Cargando...</td></tr>';
        }
        const formCategory = document.querySelector('#taskQuickForm input[name="category_id"]');
        const formReturn = document.querySelector('#taskQuickForm input[name="return_url"]');
        if (formCategory) {
            formCategory.value = categoryId;
        }
        if (formReturn) {
            formReturn.value = modal.dataset.returnUrl || '';
        }
        const returnUrl = modal.dataset.returnUrl || '';
        const fetchUrl = `<?= $basePath ?>/gestion-tareas/categorias/tareas?categoria_id=${encodeURIComponent(categoryId)}`;
        console.log('Fetching tasks from:', fetchUrl);
        fetch(fetchUrl)
            .then((res) => {
                console.log('Response status:', res.status, res.ok);
                if (!res.ok) {
                    console.error('Response not OK:', res.status, res.statusText);
                    return [];
                }
                return res.json();
            })
            .then((items) => {
                console.log('Tasks received:', items);
                                <button type="button" class="btn btn-secondary btn-small" data-close-modal>Cerrar</button>
                if (!body) {
                    return;
                }
                if (!items.length) {
                    body.innerHTML = '<tr><td colspan="6" class="muted">No hay tareas en esta actividad.</td></tr>';
                    return;
                }
                body.innerHTML = items
                    .map((task) => {
                        return `
                            <tr>
                                <td>${task.titulo ?? ''}</td>
                                <td>${task.asignado_nombre ?? ''}</td>
                                <td>${task.estado ?? ''}</td>
                                <td>${formatDate(task.fecha_compromiso)}</td>
                                <td>${formatHours(task.total_horas)}</td>
                                <td>
                                    <div class="table-actions">
                                        <a href="<?= $basePath ?>/tareas/detalle?id=${task.id}&return=${encodeURIComponent(returnUrl)}" class="btn btn-secondary btn-small" title="Ver detalle de la tarea">Detalle</a>
                                        <a href="<?= $basePath ?>/tareas/editar?id=${task.id}&return=${encodeURIComponent(returnUrl)}" class="btn btn-secondary btn-small" title="Editar tarea">Editar</a>
                                        <form method="post" action="<?= $basePath ?>/tareas/eliminar" class="inline" onsubmit="return confirm('Eliminar tarea?');">
                                            <input type="hidden" name="task_id" value="${task.id}">
                                            <input type="hidden" name="return_url" value="${returnUrl}">
                                            <button type="submit" class="btn btn-danger btn-small" title="Eliminar tarea">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        `;
                    })
                    .join('');
            })
            .catch((error) => {
                console.error('Error fetching tasks:', error);
                if (body) {
                    body.innerHTML = '<tr><td colspan="6" class="muted" style="color: red;">Error al cargar tareas. Revisa la consola.</td></tr>';
                }
            });
    });

    const taskQuickModal = setupModal('taskQuickModal', (_, modal) => {
        const tasksModalEl = document.getElementById('tasksModal');
        const formCategory = document.querySelector('#taskQuickForm input[name="category_id"]');
        const formReturn = document.querySelector('#taskQuickForm input[name="return_url"]');
        const userIdInput = document.querySelector('#taskQuickForm input[name="user_id"]');
        const authUserId = document.querySelector('#taskQuickForm')?.dataset?.authUserId || '';
        if (formCategory && tasksModalEl?.dataset?.categoryId) {
            formCategory.value = tasksModalEl.dataset.categoryId;
        }
        if (formReturn && tasksModalEl?.dataset?.returnUrl) {
            formReturn.value = tasksModalEl.dataset.returnUrl;
        }
        if (userIdInput && authUserId) {
            userIdInput.value = authUserId;
        }
        const input = modal.querySelector('input[name="titulo"]');
        if (input) {
            input.focus();
        }
    });

    const openTaskQuick = document.getElementById('openTaskQuick');
    if (openTaskQuick && taskQuickModal) {
        openTaskQuick.addEventListener('click', () => {
            taskQuickModal.openModal();
        });
    }

    setupModal('copyModal', (event, modal) => {
        const button = event?.currentTarget;
        const sourceId = button?.getAttribute('data-source-category-id') || '';
        const sourceName = button?.getAttribute('data-source-category-name') || '';
        const idInput = modal.querySelector('input[name="source_category_id"]');
        const labelInput = modal.querySelector('input[name="source_category_label"]');
        modal.dataset.sourceCategoryId = sourceId;
        modal.dataset.sourceCategoryName = sourceName;
        if (idInput) {
            idInput.value = sourceId;
        }
        if (labelInput) {
            labelInput.value = sourceName;
            labelInput.readOnly = true;
            labelInput.classList.add('input-disabled');
        }
    });

    const debounce = (fn, wait = 200) => {
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

        const fetchItems = async (term, limit = 15) => {
            const params = new URLSearchParams({ q: term, limit });
        const res = await fetch(`<?= $basePath ?>/tareas/buscar-categorias?${params.toString()}`);
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
                row.textContent = item.nombre;
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
            const items = await fetchItems(term);
            renderResults(items);
        });

        input.addEventListener('input', handle);
        document.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target)) {
                results.innerHTML = '';
            }
        });
    };

    const normalizeText = (value) => {
        return value
            .normalize('NFD')
            .replace(/[\u0300-\u006f]/g, '')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();
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


    document.querySelectorAll('#copyModal .autocomplete').forEach(initAutocomplete);

    const copyForm = document.querySelector('form[data-copy-form]');
    if (copyForm) {
        copyForm.addEventListener('submit', async (event) => {
            const copyModal = document.getElementById('copyModal');
            const sourceIdInput = copyForm.querySelector('input[name="source_category_id"]');
            const sourceLabelInput = copyForm.querySelector('input[name="source_category_label"]');
            let sourceId = sourceIdInput?.value;
            if (!sourceId && copyModal?.dataset.sourceCategoryId) {
                sourceId = copyModal.dataset.sourceCategoryId;
                if (sourceIdInput) {
                    sourceIdInput.value = sourceId;
                }
            }
            if (!sourceId && sourceLabelInput?.value && copyModal?.dataset.sourceCategoryName) {
                sourceId = copyModal.dataset.sourceCategoryId || '';
            }
            if (!sourceId) {
                event.preventDefault();
                alert('Selecciona la actividad de origen desde la lista de actividades.');
                return;
            }
            const targetIdInput = copyForm.querySelector('input[name="target_category_id"]');
            const targetNameInput = copyForm.querySelector('input[name="target_category_name"]');
            const targetName = targetNameInput?.value?.trim() ?? '';
            if (!targetName) {
                alert('Selecciona la actividad destino desde la lista.');
                return;
            }
            if (targetIdInput?.value) {
                return;
            }
        });
    }

    const formatDate = (value) => {
        if (!value) {
            return '-';
        }
        const parts = value.split('-');
        if (parts.length === 3) {
            return `${parts[2]}-${parts[1]}-${parts[0]}`;
        }
        return value;
    };

    const formatHours = (value) => {
        const decimal = parseFloat(value || 0);
        let hours = Math.floor(decimal);
        let minutes = Math.round((decimal - hours) * 60);
        if (minutes === 60) {
            hours += 1;
            minutes = 0;
        }
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    };


    const openCategoryId = new URLSearchParams(window.location.search).get('open_categoria_id');
    if (openCategoryId) {
        const opener = document.querySelector(`[data-open-modal="tasksModal"][data-category-id="${openCategoryId}"]`);
        if (opener && tasksModal) {
            tasksModal.openModal({ currentTarget: opener });
        }
    }

    const tasksFilter = document.getElementById('tasksModalFilter');
    if (tasksFilter) {
        const handle = debounce(() => {
            const term = tasksFilter.value.trim().toLowerCase();
            document.querySelectorAll('#tasksModalBody tr').forEach((row) => {
                const haystack = row.textContent.toLowerCase();
                row.style.display = haystack.includes(term) ? '' : 'none';
            });
        });
        tasksFilter.addEventListener('input', handle);
    }
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

<div class="modal" id="editCategoryModal" data-modal>
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="editCategoryModalTitle">
        <div class="modal-header">
            <h2 id="editCategoryModalTitle">Editar actividad</h2>
            <button type="button" class="btn btn-secondary btn-small" data-close-modal>Cerrar</button>
        </div>
        <form method="post" action="<?= $basePath ?>/gestion-tareas/categorias/actualizar" class="form" id="editCategoryForm">
            <input type="hidden" name="id" value="">
            <label>Nombre</label>
            <input type="text" name="nombre" required>
            <label>Tipo/Categoría de actividad</label>
            <select name="classification_id" required>
                <option value="">Selecciona tipo/categoría</option>
                <?php foreach ($classifications as $classification): ?>
                    <option value="<?= (int) $classification['id'] ?>"><?= htmlspecialchars($classification['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Equipo asignado</label>
            <select name="team_id" required>
                <option value="">Selecciona equipo</option>
                <?php foreach ($teams as $team): ?>
                    <option value="<?= (int) $team['id'] ?>"><?= htmlspecialchars($team['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-actions">
                <button type="submit" class="btn">Actualizar</button>
            </div>
        </form>
    </div>
</div>
<script>
// Abrir modal de edición y precargar datos
function openEditCategoryModal(category) {
    const modal = document.getElementById('editCategoryModal');
    if (!modal) return;
    const form = modal.querySelector('#editCategoryForm');
    if (!form) return;
    form.action = '<?= $basePath ?>/gestion-tareas/categorias/actualizar';
    form.querySelector('input[name="id"]').value = category.id || '';
    form.querySelector('input[name="nombre"]').value = category.nombre || '';
    form.querySelector('select[name="classification_id"]').value = category.classification_id || '';
    form.querySelector('select[name="team_id"]').value = category.team_id || '';
    modal.classList.add('is-open');
    const title = modal.querySelector('#editCategoryModalTitle');
    if (title) title.textContent = 'Editar actividad: ' + (category.nombre || '');
}
// Asignar evento a los botones de editar
 document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-edit-category').forEach(btn => {
        btn.addEventListener('click', function() {
            const category = {
                id: btn.getAttribute('data-category-id'),
                nombre: btn.getAttribute('data-category-name'),
                classification_id: btn.getAttribute('data-category-classification'),
                team_id: btn.getAttribute('data-category-team')
            };
            openEditCategoryModal(category);
        });
    });
 });
</script>
