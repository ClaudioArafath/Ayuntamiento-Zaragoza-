// Funciones para el modal de modificar factura
let facturaActual = null;

// Abrir modal
function abrirModalModificarFactura() {
    document.getElementById('modalModificarFactura').style.display = 'block';
    limpiarFormularioFactura();
}

// Cerrar modal
function cerrarModalModificarFactura() {
    document.getElementById('modalModificarFactura').style.display = 'none';
    limpiarFormularioFactura();
}

// Limpiar formulario
function limpiarFormularioFactura() {
    document.getElementById('buscar-folio-factura').value = '';
    document.getElementById('mensaje-busqueda').textContent = '';
    document.getElementById('mensaje-busqueda').className = 'text-sm mt-2';
    document.getElementById('seccion-factura').style.display = 'none';
    facturaActual = null;
}

// Buscar factura por folio
async function buscarFactura() {
    const folio = document.getElementById('buscar-folio-factura').value.trim();
    const mensajeBusqueda = document.getElementById('mensaje-busqueda');

    if (!folio) {
        mensajeBusqueda.textContent = '⚠️ Por favor ingrese un folio';
        mensajeBusqueda.className = 'text-sm mt-2 text-yellow-600';
        return;
    }

    mensajeBusqueda.textContent = '🔍 Buscando...';
    mensajeBusqueda.className = 'text-sm mt-2 text-blue-600';

    try {
        const response = await fetch(`api/buscar_factura.php?folio=${encodeURIComponent(folio)}`);
        const data = await response.json();

        if (data.success) {
            facturaActual = data.factura;
            mostrarDatosFactura(data.factura);
            mensajeBusqueda.textContent = '✅ Factura encontrada';
            mensajeBusqueda.className = 'text-sm mt-2 text-green-600';
        } else {
            mensajeBusqueda.textContent = '❌ ' + data.message;
            mensajeBusqueda.className = 'text-sm mt-2 text-red-600';
            document.getElementById('seccion-factura').style.display = 'none';
        }
    } catch (error) {
        console.error('Error al buscar factura:', error);
        mensajeBusqueda.textContent = '❌ Error al buscar la factura';
        mensajeBusqueda.className = 'text-sm mt-2 text-red-600';
    }
}

// Mostrar datos de la factura en el formulario
function mostrarDatosFactura(factura) {
    document.getElementById('factura-id').value = factura.id;
    document.getElementById('factura-folio').value = factura.invoicecode;

    // Convertir fecha al formato datetime-local
    if (factura.date) {
        const fecha = new Date(factura.date);
        const fechaLocal = new Date(fecha.getTime() - fecha.getTimezoneOffset() * 60000);
        document.getElementById('factura-fecha').value = fechaLocal.toISOString().slice(0, 16);
    }

    document.getElementById('factura-empleado').value = factura.employee || '';
    document.getElementById('factura-subtotal').value = parseFloat(factura.subtotal || 0).toFixed(2);
    document.getElementById('factura-total').value = parseFloat(factura.total || 0).toFixed(2);
    document.getElementById('factura-pagado').value = parseFloat(factura.payed || 0).toFixed(2);

    // Extraer datos del cliente del campo description
    if (factura.cliente_nombre) {
        document.getElementById('factura-cliente-nombre').value = factura.cliente_nombre;
    }
    if (factura.cliente_direccion) {
        document.getElementById('factura-cliente-direccion').value = factura.cliente_direccion;
    }

    // Mostrar items
    mostrarItems(factura.items);

    // Mostrar sección de factura
    document.getElementById('seccion-factura').style.display = 'block';
}

// Mostrar items de la factura
function mostrarItems(itemsJson) {
    const contenedorItems = document.getElementById('factura-items');

    try {
        const items = JSON.parse(itemsJson);
        let html = '<ul class="list-disc pl-5 space-y-1">';

        items.forEach(item => {
            const nombre = item.Name || item.name || 'Artículo sin nombre';
            const cantidad = item.Quantity || item.quantity || 1;
            const precio = parseFloat(item.Price || item.price || 0).toFixed(2);
            html += `<li><strong>${nombre}</strong> - Cantidad: ${cantidad}, Precio: $${precio}</li>`;
        });

        html += '</ul>';
        contenedorItems.innerHTML = html;
    } catch (error) {
        console.error('Error al parsear items:', error);
        contenedorItems.innerHTML = '<p class="text-gray-500">No se pudieron cargar los artículos</p>';
    }
}

// Guardar cambios de la factura
async function guardarCambiosFactura() {
    if (!facturaActual) {
        alert('No hay factura cargada');
        return;
    }

    // Confirmar cambios
    if (!confirm('¿Está seguro de que desea guardar los cambios en esta factura?')) {
        return;
    }

    const formData = {
        id: document.getElementById('factura-id').value,
        date: document.getElementById('factura-fecha').value,
        employee: document.getElementById('factura-empleado').value,
        subtotal: parseFloat(document.getElementById('factura-subtotal').value),
        total: parseFloat(document.getElementById('factura-total').value),
        payed: parseFloat(document.getElementById('factura-pagado').value),
        cliente_nombre: document.getElementById('factura-cliente-nombre').value,
        cliente_direccion: document.getElementById('factura-cliente-direccion').value
    };

    try {
        const response = await fetch('api/actualizar_factura.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            alert('✅ Factura actualizada exitosamente');
            cerrarModalModificarFactura();
        } else {
            alert('❌ Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error al actualizar factura:', error);
        alert('❌ Error al actualizar la factura');
    }
}

// Eliminar factura
async function eliminarFactura() {
    if (!facturaActual) {
        alert('No hay factura cargada');
        return;
    }

    // Confirmar eliminación con advertencia
    const folio = document.getElementById('factura-folio').value;
    if (!confirm(`⚠️ ADVERTENCIA: ¿Está seguro de que desea ELIMINAR PERMANENTEMENTE la factura ${folio}?\n\nEsta acción NO se puede deshacer.`)) {
        return;
    }

    // Segunda confirmación
    if (!confirm('¿Realmente desea continuar con la eliminación?')) {
        return;
    }

    const facturaId = document.getElementById('factura-id').value;

    try {
        const response = await fetch('api/eliminar_factura.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: facturaId })
        });

        const data = await response.json();

        if (data.success) {
            alert('✅ Factura eliminada exitosamente');
            cerrarModalModificarFactura();
        } else {
            alert('❌ Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error al eliminar factura:', error);
        alert('❌ Error al eliminar la factura');
    }
}

// Cerrar modal al hacer clic fuera de él
window.onclick = function (event) {
    const modal = document.getElementById('modalModificarFactura');
    if (event.target === modal) {
        cerrarModalModificarFactura();
    }
}
