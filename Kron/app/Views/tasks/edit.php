<?php
/** @var string $title */
/** @var array $task */
/** @var array $assignableUsers */
/** @var array $statusOptions */
/** @var array $priorityOptions */
/** @var string|null $error */
/** @var int $authUserId */
/** @var string $roleName */
/** @var string|null $returnUrl */
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
?>
<div class="page-header">
    <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
        </div>
        <h1 style="margin: 0;">Editar tarea</h1>
    </div>
    <div class="hero-actions">
        <a href="<?= htmlspecialchars($returnUrl ?? ($basePath . '/tareas/detalle?id=' . (int) $task['id'])) ?>" class="btn btn-secondary">Volver</a>
    </div>
</div>
<?php if (! empty($error)): ?>
    <div class="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= $basePath ?>/tareas/actualizar" class="form form-card" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: var(--shadow-lg); max-width: 600px;">
    <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
    <?php if ($returnUrl): ?>
        <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>">
    <?php endif; ?>
    <label style="display: flex; align-items: center; gap: 8px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
        Título
    </label>
    <input type="text" name="titulo" value="<?= htmlspecialchars($task['titulo']) ?>" required placeholder="Ingresa el título de la tarea">

    <!-- Campo de actividad oculto: se requiere para la actualización pero no es editable -->
    <input type="hidden" name="category_id" value="<?= (int)($task['category_id'] ?? 0) ?>">

    <div class="autocomplete">
        <label style="display: flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Asignar a
        </label>
        <input type="text" name="user_label" data-source="task-users" autocomplete="off" value="<?= htmlspecialchars($task['asignado_nombre']) ?> (<?= htmlspecialchars($task['asignado_email']) ?>)" required placeholder="Buscar colaborador...">
        <input type="hidden" name="user_id" value="<?= (int) $task['user_id'] ?>">
        <div class="autocomplete-results"></div>
    </div>

    <label style="display: flex; align-items: center; gap: 8px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
        Fecha compromiso estimada
    </label>
    <?php
    // Normalizar formato de fecha para input type="date" (espera yyyy-mm-dd)
    $fechaCompromiso = $task['fecha_compromiso'] ?? '';
    if ($fechaCompromiso && preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $fechaCompromiso, $m)) {
        $fechaCompromiso = "$m[3]-$m[2]-$m[1]";
    }
    ?>
    <input type="date" name="fecha_compromiso" value="<?= htmlspecialchars($fechaCompromiso) ?>" required>

    <label style="display: flex; align-items: center; gap: 8px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        Prioridad
    </label>
    <select name="prioridad" required>
        <?php foreach ($priorityOptions as $option): ?>
            <option value="<?= htmlspecialchars($option) ?>" <?= $task['prioridad'] === $option ? 'selected' : '' ?>>
                <?= htmlspecialchars($priorityLabels[$option] ?? $option) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label style="display: flex; align-items: center; gap: 8px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
            <polyline points="9 11 12 14 22 4"></polyline>
            <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
        </svg>
        Estado
    </label>
    <select name="estado" required>
        <?php foreach ($statusOptions as $option): ?>
            <option value="<?= htmlspecialchars($option) ?>" <?= $task['estado'] === $option ? 'selected' : '' ?>>
                <?= htmlspecialchars($statusLabels[$option] ?? $option) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <div class="form-actions" style="margin-top: 24px;">
        <button type="submit" class="btn" style="width: 100%; justify-content: center;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            Guardar cambios
        </button>
    </div>
</form>
<script>
(() => {
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
        const res = await fetch(`${endpoint}?${params.toString()}`);
        if (!res.ok) {
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
    guardForm(document.querySelector('form[action="<?= $basePath ?>/tareas/actualizar"]'), ['user_id']);
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
