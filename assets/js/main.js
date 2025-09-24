// =============================================
// FUNCIONES PRINCIPALES - MOVIDAS DESDE charts.js
// =============================================

// Variables globales
let filtroActual = '<?php echo $filtro; ?>';
let mesSeleccionado = '<?php echo $mes_seleccionado; ?>';

// Actualizar datos mediante AJAX
function actualizarDatos() {
    console.log('Actualizando datos...', {filtro: filtroActual, mes: mesSeleccionado});
    
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
                actualizarGraficas(data);
            }
            
            // Actualizar resumen si existe
            if (typeof actualizarResumen === 'function') {
                actualizarResumen(data);
            }
        },
        error: function(xhr, status, error) {
            console.log('Error al actualizar los datos:', error);
            console.log('Respuesta del servidor:', xhr.responseText);
            setTimeout(actualizarDatos, 5000);
        }
    });
}

// Cambiar filtro
function cambiarFiltro(nuevoFiltro) {
    $('.filtro-btn').removeClass('bg-red-500 text-white').addClass('bg-orange-200');
    $(`#filtro-${nuevoFiltro}`).removeClass('bg-orange-200').addClass('bg-red-500 text-white');
    filtroActual = nuevoFiltro;
    actualizarDatos();
}

// Cambiar mes
function cambiarMes(nuevoMes) {
    mesSeleccionado = nuevoMes;
    const url = new URL(window.location);
    url.searchParams.set('mes', nuevoMes);
    window.history.replaceState({}, '', url);
    actualizarDatos();
}

// Función auxiliar
function capitalizarPrimeraLetra(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

// =============================================
// CONFIGURACIÓN DE EVENT LISTENERS
// =============================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard cargado - Configurando event listeners...');
    
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
            console.log('Configurando botón QR:', id);
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
    
    // Actualizar datos cada 5 segundos
    setInterval(actualizarDatos, 5000);
    
    console.log('Event listeners configurados correctamente');
});