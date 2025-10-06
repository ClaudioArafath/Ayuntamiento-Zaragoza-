// =============================================
// MÓDULO PARA CANCELAR ORDEN
// =============================================

function abrirModalCancelarOrden() { 
    const modal = document.getElementById('modalCancelarOrden');
    if (modal) {
        modal.classList.remove('hidden'); 
        const inputFolio = document.getElementById('folioCancelar');
        if (inputFolio) {
            inputFolio.focus();
        }
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
    //const textareaMotivo = document.getElementById('motivoCancelar');
    
    const folio = inputFolio ? inputFolio.value.trim() : '';
    //const motivo = textareaMotivo ? textareaMotivo.value.trim() : '';
    
    /*if (!folio) {
        alert('Por favor, ingresa un folio válido.');
        return;
    }
    
    // El motivo es opcional por ahora, pero lo validamos como requerido en el formulario
    if (!motivo) {
        alert('Por favor, ingresa un motivo de cancelación.');
        return;
    }
    
    // Confirmación antes de proceder
    if (!confirm(`¿Estás seguro de que deseas cancelar y ELIMINAR permanentemente la orden ${folio}?\n\nEsta acción no se puede deshacer.`)) {
        return;
    } */
    
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
            //motivo: motivo
        })
    })
    .then(response => {
        // Primero verificar si la respuesta es JSON válido
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Respuesta no JSON:', text);
                throw new Error('El servidor respondió con un formato inválido. Verifica que el archivo PHP no tenga errores.');
            }
        });
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            cerrarModalCancelarOrden();
            /*
            // Recargar la tabla de órdenes si la función existe
            if (typeof actualizarTablaOrdenes === 'function') {
                actualizarTablaOrdenes();
            }
            
            // Opcional: Recargar la página después de 1 segundo para actualizar todos los datos
            setTimeout(() => {
                // location.reload();
            }, 1000); */
            
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        alert('Error al conectar con el servidor: ' + error.message);
    })
    .finally(() => {
        // Rehabilitar el botón
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Confirmar Cancelación';
        }
    });
}

// Hacer las funciones disponibles globalmente si es necesario
window.abrirModalCancelarOrden = abrirModalCancelarOrden;
window.cerrarModalCancelarOrden = cerrarModalCancelarOrden;
window.confirmarCancelarOrden = confirmarCancelarOrden;