<?php
/** @var string $title */
/** @var string|null $error */
ob_start();
?>
<div style="min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div class="form-card" style="max-width: 440px; background: var(--card); box-shadow: var(--shadow-xl); position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 6px; background: linear-gradient(90deg, var(--blue) 0%, var(--blue-light) 50%, var(--success) 100%);"></div>
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
            <p style="margin: 0; color: var(--muted); font-size: 15px;">Registro de nuevo usuario</p>
        </div>
        <?php if (! empty($error)): ?>
            <div class="alert" style="margin-bottom: 24px; color: #c00; background: #ffeaea; padding: 10px; border-radius: 6px; text-align: center;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if (! empty($success)): ?>
            <div class="alert" style="margin-bottom: 24px; color: #080; background: #eaffea; padding: 10px; border-radius: 6px; text-align: center;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= $basePath ?>/registro" class="form" style="border: none; padding: 0;">
            <div style="margin-bottom: 20px;">
                <label>Nombre completo</label>
                <input type="text" name="nombre" required placeholder="Tu nombre" style="width: 100%;">
            </div>
            <div style="margin-bottom: 20px;">
                <label>Email</label>
                <input type="email" name="email" required placeholder="tu@email.com" style="width: 100%;">
            </div>
            <div style="margin-bottom: 24px;">
                <label>Contraseña</label>
                <input type="password" name="password" required placeholder="••••••••" style="width: 100%;">
            </div>
            <button type="submit" class="btn" style="width: 100%; justify-content: center; padding: 14px 20px; font-size: 15px;">
                Registrarme
            </button>
        </form>
        <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--line); text-align: center;">
            <a href="<?= $basePath ?>/acceso" style="font-size: 13px; color: var(--blue); text-decoration: underline;">Volver al login</a>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
