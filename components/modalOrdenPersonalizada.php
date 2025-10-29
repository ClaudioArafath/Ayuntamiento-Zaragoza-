<?php
// components/modalOrdenPersonalizada.php
?>
<!-- Modal para Orden Personalizada -->
<div id="modalOrdenPersonalizada" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <!-- Header -->
        <div class="bg-blue-600 text-white p-4 rounded-t-lg flex justify-between items-center">
            <h3 class="text-lg font-semibold">Agregar Orden Personalizada - Sanitarios</h3>
            <button type="button" class="text-white hover:text-gray-200" onclick="cerrarModalOrdenPersonalizada()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Formulario -->
        <form id="formOrdenPersonalizada" class="p-4">
            <div class="mb-4">
                <label for="folio" class="block text-sm font-medium text-gray-700 mb-1">Folio *</label>
                <input type="text" id="folio" name="folio" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required placeholder="Ej: COM-028">
                <p class="text-xs text-gray-500 mt-1">Folio especial para sanitarios (COM-XXX)</p>
            </div>
            
            <div class="mb-4">
                <label for="nombre_cliente" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Cliente *</label>
                <input type="text" id="nombre_cliente" name="nombre_cliente" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="mb-4">
                <label for="cantidad_total" class="block text-sm font-medium text-gray-700 mb-1">Cantidad Total *</label>
                <input type="number" id="cantidad_total" name="cantidad_total" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            
            <div class="mb-4">
                <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Descripción del servicio de sanitarios..."></textarea>
            </div>
            
            <!-- Fecha y hora actual (solo lectura) -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha y Hora</label>
                <input type="text" id="fecha_hora_actual" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
            </div>
        </form>
        
        <!-- Footer -->
        <div class="bg-gray-50 px-4 py-3 rounded-b-lg flex justify-end space-x-2">
            <button type="button" class="px-4 py-2 text-gray-600 hover:text-gray-800" onclick="cerrarModalOrdenPersonalizada()">
                Cancelar
            </button>
            <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="guardarOrdenPersonalizada()">
                Generar Comprobante
            </button>
        </div>
    </div>
</div>