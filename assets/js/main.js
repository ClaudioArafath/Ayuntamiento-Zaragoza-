// =============================================
// FUNCIONES PRINCIPALES
// =============================================

// Variables globales (ahora se inicializan con datos de PHP)
let filtroActual;
let mesSeleccionado;

// Inicializar la aplicación
function inicializarAplicacion() {
    console.log('Inicializando aplicación...');
    
    // Verificar que los datos de PHP estén disponibles
    if (typeof datosApp === 'undefined') {
        console.error('Error: datosApp no está definido');
        console.log('Asegúrate de que datosApp se defina antes de cargar main.js');
        return;
    }
    
    // Inicializar variables con datos de PHP
    filtroActual = datosApp.filtro;
    mesSeleccionado = datosApp.mesSeleccionado;
    
    console.log('Datos iniciales:', {
        filtro: filtroActual,
        mes: mesSeleccionado,
        rol: datosApp.rol
    });
    
    // Inicializar gráficos si existen
    if (typeof inicializarGraficos === 'function') {
        inicializarGraficos(
            datosApp.etiquetas,
            datosApp.ingresos,
            datosApp.categorias,
            datosApp.ingresosCat,
            datosApp.porcentajes,
            datosApp.filtro
        );
    }
    
    configurarEventListeners();
    
    // Inicializar componentes específicos que dependen de datosApp
    inicializarComponentesEspecificos();
}

// Configurar event listeners
function configurarEventListeners() {
    console.log('Configurando event listeners...');
    
    // Configurar eventos de los botones de filtro
    $('.filtro-btn').click(function() {
        const filtro = $(this).data('filtro');
        cambiarFiltro(filtro);
    });
    
    // Configurar evento del selector de mes
    $('#mes-selector').change(function() {
        const mes = $(this).val();
        cambiarMes(mes);
    });
    
    // Configurar botones de escaneo QR
    const botonesQR = ['escanear-qr-admin', 'escanear-qr-empleado'];
    botonesQR.forEach(id => {
        const boton = document.getElementById(id);
        if (boton) {
            boton.addEventListener('click', abrirModalCobroQR);
        }
    });
    
    // Configurar eventos de búsqueda
    const btnBuscar = document.getElementById('btn-buscar');
    const inputBusqueda = document.getElementById('input-busqueda');
    
    if (btnBuscar) {
        btnBuscar.addEventListener('click', buscarPorFolio);
    }
    
    if (inputBusqueda) {
        inputBusqueda.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                buscarPorFolio();
            }
        });
    }
}

// Inicializar componentes específicos que dependen de datosApp
function inicializarComponentesEspecificos() {
    console.log('Inicializando componentes específicos...');
    
    // Si hay datos de órdenes, inicializar la tabla
    if (datosApp.ordenes && Array.isArray(datosApp.ordenes)) {
        console.log('📋 Inicializando tabla con datos iniciales de PHP');
        actualizarTablaOrdenes(datosApp.ordenes);
    }
}

// Función para actualizar la tabla de órdenes
function actualizarTablaOrdenes(ordenes) {
    console.log('🔄 Actualizando tabla con', ordenes.length, 'órdenes');
    
    const tbody = document.getElementById('tabla-ordenes-body');
    if (!tbody) {
        console.log('❌ No se encontró tabla-ordenes-body');
        return;
    }
    
    // Limpiar tabla
    tbody.innerHTML = '';
    
    if (ordenes.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td colspan="7" class="px-4 py-4 text-center text-gray-500 border">
                No hay órdenes para mostrar
            </td>
        `;
        tbody.appendChild(row);
        return;
    }
    
    // Llenar con nuevos datos
    ordenes.forEach((orden) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="px-4 py-2 border">${escapeHtml(orden.code)}</td>
            <td class="px-4 py-2 border">${formatDateTime(orden.date)}</td>
            <td class="px-4 py-2 border">${escapeHtml(orden.employee)}</td>
            <td class="px-4 py-2 border" title="${escapeHtml(orden.descripcion_articulos || 'Sin descripción')}">
                ${truncateText(orden.descripcion_articulos || 'Sin descripción', 50)}
            </td>
            <td class="px-4 py-2 border">$${(orden.subtotal_real || orden.total).toFixed(2)}</td>
            <td class="px-4 py-2 border">$${(parseFloat(orden.total) || 0).toFixed(2)}</td>
            <td class="px-4 py-2 border">
                <span class="badge badge-${orden.estatus === 1 ? 'success' : 'warning'}">
                    ${orden.estatus_texto || (orden.estatus === 1 ? 'Pagado' : 'Pendiente')}
                </span>
            </td>
        `;
        tbody.appendChild(row);
    });
    
    console.log('✅ Tabla actualizada correctamente con', ordenes.length, 'órdenes');
}

// Función para actualizar el resumen del dashboard
function actualizarResumenDashboard(resumen) {
    console.log('Actualizando resumen del dashboard:', resumen);
    
    // Actualizar elementos del resumen si existen
    const elementosResumen = [
        { id: 'total-ingresos', valor: resumen.totalIngresos },
        { id: 'ordenes-hoy', valor: resumen.ordenesHoy },
        { id: 'ordenes-pendientes', valor: resumen.ordenesPendientes },
    ];
    
    elementosResumen.forEach(elemento => {
        const domElement = document.getElementById(elemento.id);
        if (domElement) {
            domElement.textContent = elemento.valor;
        }
    });
}

// Actualizar datos mediante AJAX
function actualizarDatos() {
    // Verificar que las variables estén definidas
    if (typeof filtroActual === 'undefined' || typeof mesSeleccionado === 'undefined') {
        console.error('Error: variables no inicializadas');
        return;
    }
    
    console.log('Actualizando datos...', {
        filtro: filtroActual,
        mes: mesSeleccionado,
        timestamp: new Date().toLocaleTimeString()
    });
    
    $.ajax({
        url: 'api/actualizar_datos.php',
        type: 'GET',
        data: {
            filtro: filtroActual,
            mes: mesSeleccionado
        },
        dataType: 'json',
        success: function(data) {
            console.log('✅ Datos recibidos correctamente', {
                facturasCount: data.facturas ? data.facturas.length : 0,
                tieneResumen: !!data.resumen
            });
            
            // ACTUALIZAR TABLA DE ÓRDENES
            if (data.facturas && Array.isArray(data.facturas)) {
                console.log('📋 Actualizando tabla con', data.facturas.length, 'órdenes');
                actualizarTablaOrdenes(data.facturas);
            } else {
                console.log('⚠️ No hay datos de facturas para actualizar la tabla');
            }
        },
        error: function(xhr, status, error) {
            console.log('❌ Error al actualizar los datos:', error);
            console.log('📄 Respuesta del servidor:', xhr.responseText);
            
            // Reintentar después de 5 segundos
            setTimeout(actualizarDatos, 5000);
        }
    });
}
// Actualizar contadores del resumen
function actualizarContadoresResumen(resumen) {
    console.log('Actualizando contadores:', resumen);
    
    // Actualizar elementos específicos del resumen
    const elementos = [
        { id: 'total-ingresos-mes', value: resumen.ingresos_mes, prefix: '$', decimals: 2 },
        { id: 'total-facturas-mes', value: resumen.total_facturas },
        { id: 'ordenes-pendientes', value: resumen.ordenes_pendientes || resumen.total_pendientes },
        { id: 'ordenes-pagadas', value: resumen.ordenes_pagadas }
    ];
    
    elementos.forEach(item => {
        const element = document.getElementById(item.id);
        if (element) {
            if (item.prefix) {
                element.textContent = item.prefix + (item.value || 0).toFixed(item.decimals || 0);
            } else {
                element.textContent = item.value || 0;
            }
        }
    });
}

// Cambiar filtro
function cambiarFiltro(nuevoFiltro) {
    // Actualizar estado de botones
    $('.filtro-btn').removeClass('bg-red-500 text-white').addClass('bg-orange-200');
    $(`#filtro-${nuevoFiltro}`).removeClass('bg-orange-200').addClass('bg-red-500 text-white');
    
    // Actualizar filtro actual
    filtroActual = nuevoFiltro;
    
    console.log('Filtro cambiado a:', nuevoFiltro);
    
    // Actualizar datos inmediatamente
    actualizarDatos();
}

// Cambiar mes
function cambiarMes(nuevoMes) {
    // Actualizar mes seleccionado
    mesSeleccionado = nuevoMes;
    
    // Actualizar URL para mantener el estado
    const url = new URL(window.location);
    url.searchParams.set('mes', nuevoMes);
    window.history.replaceState({}, '', url);
    
    console.log('Mes cambiado a:', nuevoMes);
    
    // Actualizar datos inmediatamente
    actualizarDatos();
}

// =============================================
// FUNCIONES UTILITARIAS
// =============================================

// Función auxiliar para capitalizar
function capitalizarPrimeraLetra(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

// Función para imprimir comprobante
function imprimirComprobante(facturaId) {
    const ventana = window.open(`comprobante.php?id=${facturaId}`, '_blank');
    ventana.onload = function() {
        ventana.print();
    };
}

// Escapar HTML para prevenir XSS
function escapeHtml(unsafe) {
    if (typeof unsafe !== 'string') return unsafe;
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Formatear fecha y hora
function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleString('es-MX');
    } catch (e) {
        return dateString;
    }
}

// Truncar texto largo
function truncateText(text, maxLength) {
    if (typeof text !== 'string') return text;
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

// =============================================
// INICIALIZACIÓN PRINCIPAL
// =============================================

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado - Inicializando aplicación...');
    
    // Pequeño delay para asegurar que todos los scripts estén cargados
    setTimeout(function() {
        inicializarAplicacion();
        
        // Actualizar datos cada 5 segundos (solo si es admin/presidente/empleado)
        if (typeof datosApp !== 'undefined' && datosApp.rol) {
            const rolesPermitidos = ['Administrador', 'Presidente', 'admin', 'presidente', 'Empleado', 'empleado'];
            if (rolesPermitidos.includes(datosApp.rol)) {
                console.log('Iniciando actualización automática para rol:', datosApp.rol);
                setInterval(actualizarDatos, 5000);
                
                // Actualizar inmediatamente al cargar
                setTimeout(actualizarDatos, 2000);
            } else {
                console.log('Actualización automática desactivada para rol:', datosApp.rol);
            }
        }
    }, 100);
});

// Función global para debugging
window.mostrarDatosApp = function() {
    console.log('Estado actual de datosApp:', datosApp);
    console.log('Filtro actual:', filtroActual);
    console.log('Mes seleccionado:', mesSeleccionado);
};

// Función para forzar actualización manual
window.actualizarManual = function() {
    console.log('Actualización manual solicitada');
    actualizarDatos();
};