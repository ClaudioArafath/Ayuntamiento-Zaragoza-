// =============================================
// FUNCIONES PARA ÓRDENES PERSONALIZADAS
// =============================================

// Función para abrir el modal de orden personalizada
function abrirModalOrdenPersonalizada() {
    console.log('🔵 Abriendo modal de orden personalizada');

    // Actualizar fecha y hora actual
    const ahora = new Date();
    const fechaHora = ahora.toLocaleString('es-MX');
    document.getElementById('fecha_hora_actual').value = fechaHora;

    // Mostrar modal
    document.getElementById('modalOrdenPersonalizada').classList.remove('hidden');

    // Cargar el siguiente folio disponible
    console.log('🔵 Llamando a cargarSiguienteFolio()');
    cargarSiguienteFolio();

    // Enfocar el primer campo
    setTimeout(() => {
        document.getElementById('nombre_cliente').focus();
    }, 100);
}

// Función para cargar el siguiente folio disponible
function cargarSiguienteFolio() {
    console.log('🔄 cargarSiguienteFolio() - Iniciando...');
    const folioPreview = document.getElementById('folio_preview');

    if (!folioPreview) {
        console.error('❌ ERROR: No se encontró el elemento folio_preview');
        return;
    }

    console.log('✅ Elemento folio_preview encontrado');
    folioPreview.textContent = 'Cargando...';

    console.log('📡 Haciendo fetch a: api/obtener_siguiente_folio.php');
    fetch('api/obtener_siguiente_folio.php')
        .then(response => {
            console.log('📥 Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📦 Folio data recibido:', data);
            if (data.success && data.next_folio) {
                console.log('✅ Actualizando folio_preview con:', data.next_folio);
                folioPreview.textContent = data.next_folio;
                folioPreview.classList.remove('bg-red-50', 'text-red-700');
                folioPreview.classList.add('bg-blue-50', 'text-blue-700');
            } else {
                console.log('⚠️ No hay next_folio, usando COM-001 por defecto');
                folioPreview.textContent = 'COM-001';
                folioPreview.classList.remove('bg-red-50', 'text-red-700');
                folioPreview.classList.add('bg-blue-50', 'text-blue-700');
            }
        })
        .catch(error => {
            console.error('❌ Error al cargar siguiente folio:', error);
            folioPreview.textContent = 'Error al cargar';
            folioPreview.classList.remove('bg-blue-50', 'text-blue-700');
            folioPreview.classList.add('bg-red-50', 'text-red-700');
        });
}

// Función para cerrar el modal
function cerrarModalOrdenPersonalizada() {
    document.getElementById('modalOrdenPersonalizada').classList.add('hidden');
    document.getElementById('formOrdenPersonalizada').reset();
    document.getElementById('folio_preview').textContent = 'Cargando...';
}

// Función para guardar la orden personalizada
function guardarOrdenPersonalizada() {
    const form = document.getElementById('formOrdenPersonalizada');
    const formData = new FormData(form);

    // Validar campos requeridos
    const nombreCliente = formData.get('nombre_cliente');
    const cantidadTotal = formData.get('cantidad_total');

    if (!nombreCliente || !cantidadTotal) {
        alert('Por favor, complete todos los campos requeridos.');
        return;
    }

    // Validar que la cantidad sea mayor a 0
    if (parseFloat(cantidadTotal) <= 0) {
        alert('La cantidad total debe ser mayor a 0.');
        return;
    }

    // Agregar fecha y hora actual al formData
    const ahora = new Date();
    formData.append('fecha_hora', ahora.toISOString());

    console.log('Enviando datos de orden personalizada:', {
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

                // Mostrar mensaje con el folio generado
                if (data.folio) {
                    alert('Comprobante generado exitosamente.\nFolio: ' + data.folio);
                }

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
document.addEventListener('DOMContentLoaded', function () {
    console.log('Inicializando Dashboard...');

    // Pequeño delay para asegurar que todos los scripts estén cargados
    setTimeout(function () {
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