// =============================================
// MÓDULO DE BÚSQUEDA
// =============================================

let busquedaAbierta = false;

// Alternar visibilidad de la caja de búsqueda
function toggleBusqueda() {
    const cajaBusqueda = document.getElementById('caja-busqueda');
    const toggleBtn = document.getElementById('toggle-busqueda');
    
    if (cajaBusqueda && toggleBtn) {
        if (busquedaAbierta) {
            cajaBusqueda.classList.add('hidden');
            cajaBusqueda.classList.remove('mostrar');
            toggleBtn.innerHTML = '🔍';
        } else {
            cajaBusqueda.classList.remove('hidden');
            cajaBusqueda.classList.add('mostrar');
            toggleBtn.innerHTML = '▼';
        }
        
        busquedaAbierta = !busquedaAbierta;
    }
}

// Buscar comprobante por folio
function buscarPorFolio() {
    const inputBusqueda = document.getElementById('input-busqueda');
    const resultadoDiv = document.getElementById('resultado-busqueda');
    
    if (!inputBusqueda || !resultadoDiv) return;
    
    const folio = inputBusqueda.value.trim();
    
    if (!folio) {
        resultadoDiv.innerHTML = '<div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">Por favor ingrese un folio válido</div>';
        resultadoDiv.classList.remove('hidden');
        return;
    }
    
    // Mostrar loading
    resultadoDiv.innerHTML = '<div class="text-center py-4"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div><p class="mt-2">Buscando...</p></div>';
    resultadoDiv.classList.remove('hidden');
    
    // Realizar búsqueda via AJAX
    $.ajax({
        url: 'api/buscar_comprobante.php',
        type: 'GET',
        data: { folio: folio },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                resultadoDiv.innerHTML = `
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-semibold text-green-800">Comprobante Encontrado</h4>
                                <p class="text-sm text-green-600">Folio: ${data.comprobante.folio}</p>
                                <p class="text-sm text-green-600">Fecha: ${data.comprobante.fecha}</p>
                                <p class="text-sm text-green-600">Total: $${data.comprobante.total}</p>
                            </div>
                            <button onclick="imprimirComprobante(${data.comprobante.id})" 
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm btn-imprimir-resultado">
                                🖨️ Imprimir
                            </button>
                        </div>
                    </div>
                `;
            } else {
                resultadoDiv.innerHTML = `<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4">${data.message}</div>`;
            }
        },
        error: function() {
            resultadoDiv.innerHTML = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4">Error al realizar la búsqueda</div>';
        }
    });
}