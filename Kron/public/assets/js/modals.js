// Script para abrir y cerrar modales usando data-open-modal y data-close-modal
// Solo se activa si no hay setupModal personalizado en la página

document.addEventListener('DOMContentLoaded', function () {
    // Esperar un poco para ver si hay setupModal personalizado
    setTimeout(function() {
        // Solo procesar modales que no tengan listeners ya configurados
        document.querySelectorAll('[data-open-modal]').forEach(function (btn) {
            // Verificar si ya tiene listener (evitar duplicados)
            if (btn.dataset.modalListenerAttached) {
                return;
            }
            
            btn.addEventListener('click', function () {
                const modalId = btn.getAttribute('data-open-modal');
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.add('is-open');
                }
            });
            
            btn.dataset.modalListenerAttached = 'true';
        });

        // Cerrar modal
        document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const modal = btn.closest('.modal');
                if (modal) {
                    modal.classList.remove('is-open');
                }
            });
        });

        // Cerrar modal con overlay
        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function () {
                const modal = overlay.closest('.modal');
                if (modal) {
                    modal.classList.remove('is-open');
                }
            });
        });

        // Cerrar modal con Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.is-open').forEach(function (modal) {
                    modal.classList.remove('is-open');
                });
            }
        });
    }, 100);
});
