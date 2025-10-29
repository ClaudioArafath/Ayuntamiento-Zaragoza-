// =============================================
// FUNCIONES PARA ÓRDENES PERSONALIZADAS
// =============================================

// Función para abrir el modal de orden personalizada
function abrirModalOrdenPersonalizada() {
    console.log('Abriendo modal de orden personalizada');
    
    // Actualizar fecha y hora actual
    const ahora = new Date();
    const fechaHora = ahora.toLocaleString('es-MX');
    document.getElementById('fecha_hora_actual').value = fechaHora;
    
    // Mostrar modal
    document.getElementById('modalOrdenPersonalizada').classList.remove('hidden');
    
    // Enfocar el primer campo
    setTimeout(() => {
        document.getElementById('folio').focus();
    }, 100);
}

// Función para cerrar el modal
function cerrarModalOrdenPersonalizada() {
    document.getElementById('modalOrdenPersonalizada').classList.add('hidden');
    document.getElementById('formOrdenPersonalizada').reset();
}

// Función para guardar la orden personalizada
function guardarOrdenPersonalizada() {
    const form = document.getElementById('formOrdenPersonalizada');
    const formData = new FormData(form);
    
    // Validar campos requeridos
    const folio = formData.get('folio');
    const nombreCliente = formData.get('nombre_cliente');
    const cantidadTotal = formData.get('cantidad_total');
    
    if (!folio || !nombreCliente || !cantidadTotal) {
        alert('Por favor, complete todos los campos requeridos.');
        return;
    }
    
    // Validar formato del folio
    if (!/^COM-\d{3}$/.test(folio)) {
        alert('El folio debe tener el formato COM-XXX (ej: COM-028)');
        return;
    }
    
    // Agregar fecha y hora actual al formData
    const ahora = new Date();
    formData.append('fecha_hora', ahora.toISOString());
    
    console.log('Enviando datos de orden personalizada:', {
        folio: folio,
        nombre_cliente: nombreCliente,
        cantidad_total: cantidadTotal,
        fecha_hora: ahora.toISOString()
    });
    
    // Enviar datos al servidor
    fetch('api/guardar_orden_personalizada.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✅ Orden personalizada guardada correctamente');
            cerrarModalOrdenPersonalizada();
            
            // Abrir comprobante en nueva ventana
            if (data.comprobante_url) {
                window.open(data.comprobante_url, '_blank');
            }
        } else {
            console.error('❌ Error al guardar orden personalizada:', data.error);
            alert('Error al guardar la orden: ' + (data.error || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('❌ Error en la solicitud:', error);
        alert('Error de conexión. Intente nuevamente.');
    });
}

// =============================================
// INICIALIZACIÓN PRINCIPAL
// =============================================

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando Dashboard...');
    
    // Pequeño delay para asegurar que todos los scripts estén cargados
    setTimeout(function() {
        inicializarAplicacion();
        
        // Actualizar datos cada 8 segundos
        if (typeof datosApp !== 'undefined' && datosApp.rol) {
            const rolesPermitidos = ['Administrador', 'Presidente', 'admin', 'presidente', 'Empleado', 'empleado'];
            if (rolesPermitidos.includes(datosApp.rol)) {
                console.log('Iniciando actualización automática para rol:', datosApp.rol);
                setInterval(actualizarDatos, 8000);
            } else {
                console.log('Actualización automática desactivada para rol:', datosApp.rol);
            }
        }
    }, 100);
});