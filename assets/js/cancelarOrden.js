// =============================================
// MÓDULO PARA CANCELAR ORDEN
// =============================================

function abrirModalCancelarOrden() { 
    const modal = document.getElementById('modalCancelarOrden');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function cerrarModalCancelarOrden() {
    const modal = document.getElementById('modalCancelarOrden');
    if (modal) {
        modal.classList.add('hidden');
        // Limpiar campos
        const inputFolio = document.getElementById('folioCancelar');
        const textareaMotivo = document.getElementById('motivoCancelar');
        if (inputFolio) inputFolio.value = '';
        if (textareaMotivo) textareaMotivo.value = '';
    }
}

// Manejar el envío del formulario
document.addEventListener('DOMContentLoaded', function() {
    const formCancelar = document.getElementById('formCancelarOrden');
    if (formCancelar) {
        formCancelar.addEventListener('submit', function(e) {
            e.preventDefault();
            confirmarCancelarOrden();
        });
    }
});

function confirmarCancelarOrden() {
    const inputFolio = document.getElementById('folioCancelar');
    const textareaMotivo = document.getElementById('motivoCancelar');
    
    const folio = inputFolio ? inputFolio.value.trim() : '';
    const motivo = textareaMotivo ? textareaMotivo.value.trim() : '';
    
    if (!folio) {
        alert('Por favor, ingresa un folio válido.');
        return;
    }
    
    if (!motivo) {
        alert('Por favor, ingresa un motivo de cancelación.');
        return;
    }
    
    // Deshabilitar el botón de envío para evitar múltiples envíos
    const btnSubmit = document.querySelector('#formCancelarOrden button[type="submit"]');
    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Cancelando...';
    }
    
    // Enviar la petición al servidor
    fetch('includes/cancelar_orden.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            folio: folio,
            motivo: motivo
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Orden cancelada exitosamente');
            cerrarModalCancelarOrden();
            // Recargar la tabla de órdenes si es necesario
            if (typeof actualizarTablaOrdenes === 'function') {
                actualizarTablaOrdenes();
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al cancelar la orden');
    })
    .finally(() => {
        // Rehabilitar el botón
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Confirmar Cancelación';
        }
    });
}