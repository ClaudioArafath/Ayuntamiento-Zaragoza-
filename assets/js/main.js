// Variables globales
let filtroActual;
let mesSeleccionado;

// =============================================
// ELIMINACIÓN AUTOMÁTICA DE REGISTROS ANTIGUOS
// =============================================

// Función para eliminar registros de más de 5 días hábiles
function eliminarRegistrosAntiguos() {
    console.log('🔄 Verificando registros antiguos para eliminar...');

    fetch('api/eliminar_registros_antiguos.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new Error(`Respuesta no JSON: ${text.substring(0, 100)}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const mensaje = `🗑️ Eliminados: ${data.eliminados_backup} de backup, ${data.eliminados_original} de original`;
                console.log(mensaje);

                if (data.mensaje) {
                    console.log(`📝 ${data.mensaje}`);
                }

                // Mostrar notificación solo si se eliminó algo
                if (data.eliminados_backup > 0 || data.eliminados_original > 0) {
                    console.log(`✅ Limpieza completada. Fecha límite: ${data.fechaLimite}`);
                }
            } else {
                console.log('ℹ️ ' + (data.mensaje || 'No hay registros para eliminar'));
            }
        })
        .catch(error => {
            console.error('❌ Error en limpieza automática:', error.message);
        });
}

// Programar limpieza diaria a las 3:00 AM
function programarLimpiezaAutomatica() {
    // Ejecutar una vez al día a las 3:00 AM
    const ahora = new Date();
    const hora = ahora.getHours();

    // Si es la 1:00 PM (13:00) o 3:00 AM, ejecutar limpieza (para pruebas usa 13)
    if (hora === 13 || hora === 3) { // Cambia 13 por 3 para producción
        console.log('🕒 Ejecutando limpieza programada...');
        eliminarRegistrosAntiguos();
    }
}

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

    // Diferir inicialización de gráficos para mejorar LCP
    if (datosApp.rol === 'Administrador' || datosApp.rol === 'Presidente') {
        if ('requestIdleCallback' in window) {
            requestIdleCallback(() => {
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
            }, { timeout: 2000 });
        } else {
            setTimeout(() => {
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
            }, 100);
        }

        // INICIALIZAR LIMPIEZA AUTOMÁTICA
        inicializarLimpiezaAutomatica();

        // ... resto de tu código ...
    }

    // Nueva función para inicializar la limpieza
    function inicializarLimpiezaAutomatica() {
        console.log('🔧 Inicializando sistema de limpieza automática...');

        // Ejecutar limpieza al iniciar la aplicación
        setTimeout(() => {
            eliminarRegistrosAntiguos();
        }, 10000); // Esperar 10 segundos después del inicio

        // Programar verificación horaria
        setInterval(programarLimpiezaAutomatica, 3600000); // Verificar cada hora

        console.log('✅ Sistema de limpieza automática inicializado');
    }

    configurarEventListeners();

    // Inicializar componentes específicos que dependen de datosApp
    inicializarComponentesEspecificos();
}

// Configurar event listeners
function configurarEventListeners() {
    console.log('Configurando event listeners...');

    // Configurar eventos de los botones de filtro
    $('.filtro-btn').click(function () {
        const filtro = $(this).data('filtro');
        cambiarFiltro(filtro);
    });

    // Configurar evento del selector de mes
    $('#mes-selector').change(function () {
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
        inputBusqueda.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                buscarPorFolio();
            }
        });
    }
    // Configurar botón "Cobrar orden"
    const btnCobrarOrden = document.getElementById('cobrarOrden');
    if (btnCobrarOrden) {
        btnCobrarOrden.addEventListener('click', function () {
            // Verificar si la función existe antes de llamarla
            if (typeof abrirModalCobro === 'function') {
                abrirModalCobro();
            } else {
                console.error('La función abrirModalCobro no está disponible');
                // Mostrar un mensaje de error al usuario
                alert('Error: La función de cobro no está disponible. Recargue la página.');
            }
        });
        console.log('Botón "Cobrar orden" configurado en main.js');
    }

    // Configurar botón de orden personalizada
    const btnOrdenPersonalizada = document.getElementById('ordenPersonalizada');
    if (btnOrdenPersonalizada) {
        btnOrdenPersonalizada.addEventListener('click', abrirModalOrdenPersonalizada);
    }
}

// Inicializar componentes específicos que dependen de datosApp
function inicializarComponentesEspecificos() {
    console.log('Inicializando componentes específicos...');

    // Si hay datos de órdenes, inicializar la tabla
    if (datosApp.ordenes && Array.isArray(datosApp.ordenes)) {
        console.log('Inicializando tabla con datos iniciales de PHP');
        actualizarTablaOrdenes(datosApp.ordenes);
    }
    //Evento para cancelar orden
    const btnCancelar = document.getElementById('cancelarOrden');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', abrirModalCancelarOrden);
    }
}

// Función para actualizar la tabla de órdenes
function actualizarTablaOrdenes(ordenes) {
    // Si ordenes no es un array, usar un array vacío
    if (!Array.isArray(ordenes)) {
        console.error('Las órdenes no son un array:', ordenes);
        ordenes = [];
    }
    console.log('🔄 Actualizando tabla con', ordenes.length, 'órdenes');

    const tbody = document.getElementById('tabla-ordenes-body');
    if (!tbody) {
        console.log('❌ No se encontró tabla ordenes');
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
        // Determinar el estatus correcto (compatible con ambas estructuras)
        const estatusNum = orden.estatus || orden.estatus_num || 0;
        const estatusTexto = orden.estatus_texto || (estatusNum == 1 ? 'Pagado' : 'Pendiente');

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
            <td class="px-4 py-2 border orden-status-cell" data-folio="${escapeHtml(orden.code)}" data-estatus="${estatusTexto}">
                <span class="badge badge-${estatusNum == 1 ? 'success' : 'warning'}">
                    ${estatusTexto}
                </span>
            </td>
        `;
        tbody.appendChild(row);
    });

    console.log('✅ Tabla actualizada correctamente con', ordenes.length, 'órdenes');

    // Reinicializar botones hover después de actualizar la tabla
    if (typeof window.reinicializarHoverBotones === 'function') {
        window.reinicializarHoverBotones();
    }
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
        success: function (data) {
            console.log('✅ Datos recibidos correctamente', {
                facturasCount: data.facturas ? data.facturas.length : 0,
                tieneResumen: !!data.resumen
            });

            // ✅ ACTUALIZAR GRÁFICAS
            if (typeof actualizarGraficas === 'function') {
                actualizarGraficas(data, filtroActual);
                console.log('📊 Gráficas actualizadas');
            }
            // ✅ ACTUALIZAR RESUMEN
            if (typeof actualizarResumen === 'function' && data.resumen) {
                actualizarResumen(data);
                console.log('📈 Resumen actualizado');
            }
            // ACTUALIZAR TABLA DE ÓRDENES
            if (data.facturas && Array.isArray(data.facturas)) {
                console.log('📋 Actualizando tabla con', data.facturas.length, 'órdenes');
                actualizarTablaOrdenes(data.facturas);
            } else {
                console.log('No hay datos de facturas para actualizar la tabla');
            }

        },
        error: function (xhr, status, error) {
            console.log('❌ Error al actualizar los datos:', error);
            console.log('📄 Respuesta del servidor:', xhr.responseText);

            // Reintentar después de 8 segundos
            setTimeout(actualizarDatos, 8000);
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
    ventana.onload = function () {
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

// Función global para debugging
window.mostrarDatosApp = function () {
    console.log('Estado actual de datosApp:', datosApp);
    console.log('Filtro actual:', filtroActual);
    console.log('Mes seleccionado:', mesSeleccionado);
};
