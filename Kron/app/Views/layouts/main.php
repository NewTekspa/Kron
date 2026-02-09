<?php
/** @var string $title */
/** @var array|null $authUser */
/** @var bool $isAdmin */
/** @var string|null $roleName */
/** @var string $content */
$basePath = $GLOBALS['config']['base_path'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KRON</title>
    <link rel="icon" type="image/svg+xml" href="<?= $basePath ?>/assets/icons/clock.svg">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/app.css">
</head>
<body>
    <header class="topbar">
        <div class="container" style="display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 32px;">
                <div class="brand">KRON</div>
                <nav class="nav" style="display: flex; align-items: center; gap: 16px;">
                    <a href="<?= $basePath ?>/">Inicio</a>
                    <?php if ($authUser): ?>
                        <div class="nav-item has-dropdown">
                            <button type="button" class="nav-link">
                                Tareas
                                <span class="nav-caret">▼</span>
                            </button>
                            <div class="dropdown-menu">
                                <?php if (in_array($roleName ?? '', ['jefe', 'subgerente', 'administrador'], true)): ?>
                                    <a href="<?= $basePath ?>/tareas/gestion">Seguimiento de Equipo</a>
                                <?php endif; ?>
                                <a href="<?= $basePath ?>/tareas">Registro rapido</a>
                                <a href="<?= $basePath ?>/tareas/gestor">Gestor de tareas</a>
                            </div>
                        </div>
                        <?php if ($isAdmin): ?>
                            <div class="nav-item has-dropdown">
                                <button type="button" class="nav-link">
                                    Configuracion
                                    <span class="nav-caret">▼</span>
                                </button>
                                <div class="dropdown-menu">
                                    <a href="<?= $basePath ?>/admin/usuarios">Usuarios</a>
                                    <a href="<?= $basePath ?>/admin/roles">Roles</a>
                                    <a href="<?= $basePath ?>/admin/equipos">Equipos</a>
                                    <a href="<?= $basePath ?>/admin/categorias">Actividades</a>
                                </div>
                            </div>
                        <?php endif; ?>
                        <a href="<?= $basePath ?>/salir">Salir</a>
                    <?php else: ?>
                        <a href="<?= $basePath ?>/acceso">Acceso</a>
                    <?php endif; ?>
                </nav>
            </div>
            <?php if ($authUser): ?>
                <span class="user-info" style="color: var(--muted); font-size: 15px; display: flex; align-items: center; gap: 6px;">
                    <svg style="vertical-align: middle;" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a8.38 8.38 0 0 1 13 0"/></svg>
                    <?= htmlspecialchars($authUser['nombre'] ?? $authUser['email'] ?? 'Usuario') ?>
                </span>
            <?php endif; ?>
        </div>
    </header>
    <main class="container">
        <?= $content ?>
    </main>
    <script src="<?= $basePath ?>/assets/js/modals.js"></script>
</body>
</html>
