<?php
/** @var string $title */
/** @var array $classifications */
/** @var string|null $error */
ob_start();
?>
<div class="page-header">
    <h1>Actividades</h1>
</div>
<?php if (! empty($error)): ?>
    <div class="alert"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card-block">
    <div class="hero-actions">
        <button type="button" class="btn" data-open-modal="classificationModal">Nueva actividad</button>
    </div>
    <?php if (empty($classifications)): ?>
        <p class="muted">No hay actividades registradas.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classifications as $classification): ?>
                    <tr>
                        <td><?= htmlspecialchars($classification['nombre']) ?></td>
                        <td>
                            <div class="table-actions">
                                <button type="button" class="btn btn-secondary btn-small" data-open-modal="classificationModal" data-classification-id="<?= (int) $classification['id'] ?>" data-classification-name="<?= htmlspecialchars($classification['nombre']) ?>">Editar</button>
                                <form method="post" action="<?= $basePath ?>/admin/categorias/eliminar" class="inline" onsubmit="return confirm('Eliminar actividad?');">
                                    <input type="hidden" name="id" value="<?= (int) $classification['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-small">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="modal" id="classificationModal" data-modal>
    <div class="modal-overlay" data-close-modal></div>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="classificationModalTitle">
        <div class="modal-header">
            <h2 id="classificationModalTitle">Nueva actividad</h2>
            <button type="button" class="btn btn-secondary btn-small" data-close-modal>Cerrar</button>
        </div>
        <form method="post" action="<?= $basePath ?>/admin/categorias/crear" class="form" data-classification-form>
            <input type="hidden" name="id" value="">
            <label>Nombre</label>
            <input type="text" name="nombre" required>
            <div class="form-actions">
                <button type="submit" class="btn">Guardar</button>
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

    setupModal('classificationModal', (event, modal) => {
        const form = modal.querySelector('[data-classification-form]');
        const idInput = form?.querySelector('input[name="id"]');
        const input = modal.querySelector('input[name="nombre"]');
        const button = event?.currentTarget;
        const classificationId = button?.getAttribute('data-classification-id') || '';
        const classificationName = button?.getAttribute('data-classification-name') || '';
        if (form && idInput) {
            if (classificationId) {
                form.action = '<?= $basePath ?>/admin/categorias/actualizar';
                idInput.value = classificationId;
            } else {
                form.action = '<?= $basePath ?>/admin/categorias/crear';
                idInput.value = '';
            }
        }
        if (input) {
            input.value = classificationName;
            input.focus();
        }
    });
})();
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
