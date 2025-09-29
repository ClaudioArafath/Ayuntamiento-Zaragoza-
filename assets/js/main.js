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
        inicializarTablaOrdenes(datosApp.ordenes);
    }
    
    // Si hay datos de resumen, actualizar el dashboard
    if (datosApp.resumen) {
        actualizarResumenDashboard(datosApp.resumen);
    }
}

// Función para inicializar la tabla de órdenes
function inicializarTablaOrdenes(ordenes) {
    console.log('Inicializando tabla con', ordenes.length, 'órdenes');
    
    // Aquí puedes agregar lógica específica para la tabla de órdenes
    // Por ejemplo, ordenamiento, filtros, etc.
    
    const tabla = document.getElementById('tabla-ordenes');
    if (tabla && ordenes.length > 0) {
        console.log('Tabla de órdenes encontrada, mostrando datos...');
        // Tu lógica para llenar la tabla
    }
}

// Función para actualizar el resumen del dashboard
function actualizarResumenDashboard(resumen) {
    // Actualizar elementos del resumen si existen
    const elementosResumen = [
        { id: 'total-ingresos', valor: resumen.totalIngresos },
        { id: 'ordenes-hoy', valor: resumen.ordenesHoy },
        { id: 'ordenes-pendientes', valor: resumen.ordenesPendientes },
        // Agrega más elementos según tu estructura
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
        mes: mesSeleccionado
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
            console.log('Datos recibidos:', data);
            
            // Actualizar gráficas si existen
            if (typeof actualizarGraficas === 'function') {
                actualizarGraficas(data, filtroActual);
            }
            
            // Actualizar resumen si existe
            if (typeof actualizarResumen === 'function') {
                actualizarResumen(data);
            }
        },
        error: function(xhr, status, error) {
            console.log('Error al actualizar los datos:', error);
            console.log('Respuesta del servidor:', xhr.responseText);
            
            // Reintentar después de 5 segundos
            setTimeout(actualizarDatos, 5000);
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

// =============================================
// INICIALIZACIÓN PRINCIPAL
// =============================================

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado - Inicializando aplicación...');
    
    // Pequeño delay para asegurar que todos los scripts estén cargados
    setTimeout(function() {
        inicializarAplicacion();
        
        // Actualizar datos cada 5 segundos (solo si es admin/presidente)
        if (typeof datosApp !== 'undefined' && datosApp.rol) {
            const rolesPermitidos = ['Administrador', 'Presidente', 'admin', 'presidente'];
            if (rolesPermitidos.includes(datosApp.rol)) {
                console.log('Iniciando actualización automática para rol:', datosApp.rol);
                setInterval(actualizarDatos, 5000);
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