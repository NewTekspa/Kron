<?php
/** @var string $title */
/** @var string|null $error */
ob_start();
?>
<div style="min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div class="form-card" style="max-width: 440px; background: var(--card); box-shadow: var(--shadow-xl); position: relative; overflow: hidden;">
        <!-- Decorative gradient -->
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 6px; background: linear-gradient(90deg, var(--blue) 0%, var(--blue-light) 50%, var(--success) 100%);"></div>
        
        <!-- Logo/Brand -->
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="width: 80px; height: 80px; margin: 0 auto 16px; background: linear-gradient(135deg, var(--blue) 0%, var(--blue-light) 100%); border-radius: 20px; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-lg);">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
            </div>
            <h1 style="margin: 0 0 8px; font-size: 28px; font-weight: 700; background: linear-gradient(135deg, var(--text) 0%, var(--text-medium) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">KRON</h1>
            <p style="margin: 0; color: var(--muted); font-size: 15px;">Sistema de Gestión de Tareas</p>
        </div>
        
        <?php if (! empty($error)): ?>
            <div class="alert" style="margin-bottom: 24px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post" action="<?= $basePath ?>/acceso" class="form" style="border: none; padding: 0;">
            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    Email
                </label>
                <input type="email" name="email" required placeholder="tu@email.com" style="width: 100%;">
            </div>
            
            <div style="margin-bottom: 24px;">
                <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0110 0v4"></path>
                    </svg>
                    Contraseña
                </label>
                <input type="password" name="password" required placeholder="••••••••" style="width: 100%;">
            </div>
            
            <button type="submit" class="btn" style="width: 100%; justify-content: center; padding: 14px 20px; font-size: 15px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"></path>
                    <polyline points="10 17 15 12 10 7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
                Ingresar al Sistema
            </button>
        </form>
        
        <!-- Footer info -->
        <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--line); text-align: center;">
            <p style="margin: 0; font-size: 13px; color: var(--muted);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                Ingresa con tus credenciales corporativas
            </p>
            <a href="<?= $basePath ?>/registro" style="display:block;margin-top:12px;font-size:14px;color:var(--blue);text-decoration:underline;">¿No tienes cuenta? Registrarme</a>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
