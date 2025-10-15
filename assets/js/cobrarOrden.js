// =============================================
// MÓDULO PARA COBRAR ORDEN
// =============================================

// Función para abrir el modal de cobro
function abrirModalCobro() {
    const modal = document.getElementById('modalCobrarOrden');
    if (modal) {
        modal.classList.remove('hidden');
        const inputFolio = document.getElementById('folio');
        if (inputFolio) {
            inputFolio.focus();
        }
    }
}

// Función para cerrar el modal de cobro
function cerrarModalCobro() {
    document.getElementById('modalCobrarOrden').classList.add('hidden');
}

// Manejar el envío del formulario de cobro
document.addEventListener('DOMContentLoaded', function() {
    const formCobro = document.getElementById('form-cobro');
    if (formCobro) {
        formCobro.addEventListener('submit', function(e) {
            e.preventDefault();
            confirmarCobroOrden();
        });
    }

    const btnCobrarOrden = document.getElementById('cobrarOrden');
    if (btnCobrarOrden) {
        btnCobrarOrden.addEventListener('click', abrirModalCobro);
        console.log('Botón "Cobrar orden" configurado correctamente');
    }
});

// Hacer funciones disponibles globalmente
window.abrirModalCobro = abrirModalCobro;
window.cerrarModalCobro = cerrarModalCobro;