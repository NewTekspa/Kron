<?php
/** @var string $title */
/** @var array $teams */
/** @var array $membersByTeam */
/** @var string|null $error */
ob_start();
?>
<div class="page-header">
    <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"></path>
            </svg>
        </div>
        <h1 style="margin: 0;">Equipos</h1>
    </div>
</div>
<?php if (! empty($error)): ?>
    <div class="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-grid">
    <form method="post" action="<?= $basePath ?>/admin/equipos/crear" class="form" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: var(--shadow);">
        <h2 style="display: flex; align-items: center; gap: 10px; margin: 0 0 16px; padding-bottom: 12px; border-bottom: 2px solid var(--line);">
            <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </div>
            Crear equipo
        </h2>
        <label style="display: flex; align-items: center; gap: 8px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"></path>
            </svg>
            Nombre del equipo
        </label>
        <input type="text" name="nombre" required placeholder="Ingresa el nombre del equipo">

        <div class="autocomplete">
            <label style="display: flex; align-items: center; gap: 8px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Subgerente
            </label>
            <input type="text" name="subgerente_label" data-source="users" data-role="subgerente" autocomplete="off" required placeholder="Buscar subgerente...">
            <input type="hidden" name="subgerente_id" value="">
            <div class="autocomplete-results"></div>
        </div>

        <div class="autocomplete">
            <label style="display: flex; align-items: center; gap: 8px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Jefe
            </label>
            <input type="text" name="jefe_label" data-source="users" data-role="jefe" autocomplete="off" required placeholder="Buscar jefe...">
            <input type="hidden" name="jefe_id" value="">
            <div class="autocomplete-results"></div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
                </svg>
                Guardar equipo
            </button>
        </div>
    </form>

    <form method="post" action="<?= $basePath ?>/admin/equipos/asignar-colaborador" class="form" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); box-shadow: var(--shadow);">
        <h2 style="display: flex; align-items: center; gap: 10px; margin: 0 0 16px; padding-bottom: 12px; border-bottom: 2px solid var(--line);">
            <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #10b981 0%, #34d399 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
            </div>
            Asignar colaborador
        </h2>
        <div class="autocomplete">
            <label style="display: flex; align-items: center; gap: 8px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"></path>
                </svg>
                Equipo
            </label>
            <input type="text" name="team_label" data-source="teams" autocomplete="off" required placeholder="Buscar equipo...">
            <input type="hidden" name="team_id" value="">
            <div class="autocomplete-results"></div>
        </div>

        <div class="autocomplete">
            <label style="display: flex; align-items: center; gap: 8px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Colaborador
            </label>
            <input type="text" name="colaborador_label" data-source="users" data-role="colaborador" autocomplete="off" required placeholder="Buscar colaborador...">
            <input type="hidden" name="colaborador_id" value="">
            <div class="autocomplete-results"></div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Asignar
            </button>
        </div>
    </form>
</div>

<table class="table" style="box-shadow: var(--shadow);">
    <caption class="table-filter">
        <div class="filter-bar">
            <label for="teamFilter" style="display: flex; align-items: center; gap: 8px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="M21 21l-4.35-4.35"></path>
                </svg>
                Filtrar equipos
            </label>
            <input type="text" id="teamFilter" placeholder="Buscar por equipo, jefe, subgerente o colaborador" autocomplete="off">
        </div>
    </caption>
    <thead>
        <tr>
            <th>Equipo</th>
            <th>Subgerente</th>
            <th>Jefe</th>
            <th>Colaboradores</th>
            <th>Acciones</th>
            <th style="text-align: center;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    Críticas
                </div>
            </th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($teams as $team): ?>
            <?php $members = $membersByTeam[(int) $team['id']] ?? []; ?>
            <tr>
                <td style="font-weight: 600;"><?= htmlspecialchars($team['nombre']) ?></td>
                <td><?= htmlspecialchars($team['subgerente_nombre']) ?></td>
                <td><?= htmlspecialchars($team['jefe_nombre']) ?></td>
                <td>
                    <?php if (! empty($members)): ?>
                        <div class="team-members">
                            <?php foreach ($members as $member): ?>
                                <div class="team-member">
                                    <span><?= htmlspecialchars($member['nombre']) ?> (<?= htmlspecialchars($member['email']) ?>)</span>
                                    <form method="post" action="<?= $basePath ?>/admin/equipos/remover-colaborador" class="inline">
                                        <input type="hidden" name="team_id" value="<?= (int) $team['id'] ?>">
                                        <input type="hidden" name="colaborador_id" value="<?= (int) $member['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-small">Quitar</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <span class="muted">Sin colaboradores</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: center;">
                    <?php $criticas = isset($criticalByTeam[(int)$team['id']]) ? $criticalByTeam[(int)$team['id']] : 0; ?>
                    <?php if ($criticas > 0): ?>
                        <span style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #b91c1c; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; border: 1px solid #fca5a5; display: inline-block;">
                            🔥 <?= $criticas ?>
                        </span>
                    <?php else: ?>
                        <span style="color: #94a3b8; font-size: 13px;">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="table-actions">
                        <a href="<?= $basePath ?>/admin/equipos/detalle?id=<?= (int) $team['id'] ?>" class="btn btn-secondary btn-small">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </a>
                        <form method="post" action="<?= $basePath ?>/admin/equipos/eliminar" class="inline" onsubmit="return confirm('Eliminar equipo y sus miembros?');">
                            <input type="hidden" name="id" value="<?= (int) $team['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-small">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<script>
const teamSearch = (() => {
    const debounce = (fn, wait = 300) => {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), wait);
        };
    };

    const renderResults = (container, items, input, hidden, formatter) => {
        container.innerHTML = '';
        if (!items.length) {
            const emptyMsg = document.createElement('div');
            emptyMsg.className = 'autocomplete-item autocomplete-empty';
            emptyMsg.style.color = '#888';
            emptyMsg.style.fontStyle = 'italic';
            emptyMsg.textContent = 'No se encontraron resultados.';
            container.appendChild(emptyMsg);
            return;
        }
        items.forEach((item) => {
            const row = document.createElement('div');
            row.className = 'autocomplete-item';
            row.textContent = formatter(item);
            row.addEventListener('click', () => {
                input.value = formatter(item);
                hidden.value = item.id;
                container.innerHTML = '';
            });
            container.appendChild(row);
        });
    };

    const fetchUsers = async (term, role, limit = 15) => {
        const params = new URLSearchParams({ q: term, role, limit });
        const res = await fetch(`<?= $basePath ?>/admin/usuarios/buscar?${params.toString()}`);
        if (!res.ok) {
            return [];
        }
        return res.json();
    };

    const fetchTeams = async (term, limit = 15) => {
        const params = new URLSearchParams({ q: term, limit });
        const res = await fetch(`<?= $basePath ?>/admin/equipos/buscar?${params.toString()}`);
        if (!res.ok) {
            return [];
        }
        return res.json();
    };

    const initField = (wrapper) => {
        const input = wrapper.querySelector('input[type="text"]');
        const hidden = wrapper.querySelector('input[type="hidden"]');
        const results = wrapper.querySelector('.autocomplete-results');
        const source = input.dataset.source;
        const role = input.dataset.role || '';

        const handle = debounce(async () => {
            const term = input.value.trim();
            hidden.value = '';
            if (term.length < 2) {
                results.innerHTML = '';
                return;
            }

            if (source === 'teams') {
                const items = await fetchTeams(term);
                renderResults(results, items, input, hidden, (item) => item.nombre);
                return;
            }

            const items = await fetchUsers(term, role);
            renderResults(results, items, input, hidden, (item) => `${item.nombre} (${item.email})`);
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
                    alert('Selecciona valores desde la lista.');
                    return;
                }
            }
        });
    };

    const initFilter = () => {
        const input = document.getElementById('teamFilter');
        if (!input) {
            return;
        }
        const rows = Array.from(document.querySelectorAll('table tbody tr'));
        const handle = debounce(() => {
            const term = input.value.trim().toLowerCase();
            rows.forEach((row) => {
                const haystack = row.textContent.toLowerCase();
                row.style.display = haystack.includes(term) ? '' : 'none';
            });
        }, 150);
        input.addEventListener('input', handle);
    };

    return {
        init() {
            document.querySelectorAll('.autocomplete').forEach(initField);
            guardForm(document.querySelector('form[action="<?= $basePath ?>/admin/equipos/crear"]'), ['subgerente_id', 'jefe_id']);
            guardForm(document.querySelector('form[action="<?= $basePath ?>/admin/equipos/asignar-colaborador"]'), ['team_id', 'colaborador_id']);
            initFilter();
        },
    };
})();

teamSearch.init();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
