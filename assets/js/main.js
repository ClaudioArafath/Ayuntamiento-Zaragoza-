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

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado - Inicializando aplicación...');
    inicializarAplicacion();
    
    // Actualizar datos cada 5 segundos (solo si es admin/presidente)
    if (typeof datosApp !== 'undefined' && 
        (datosApp.rol === 'Administrador' || datosApp.rol === 'Presidente')) {
        setInterval(actualizarDatos, 5000);
    }
});