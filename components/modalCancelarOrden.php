<!-- components/modalCancelarOrden.php -->
<div id="modalCancelarOrden" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-red-600">Cancelar Orden</h3>
            <button type="button" onclick="cerrarModalCancelarOrden()" class="text-gray-500 hover:text-gray-700 text-2xl">
                ✕
            </button>
        </div>
        
        <form id="formCancelarOrden">
            <div class="mb-4">
                <label for="folioCancelar" class="block text-gray-700 font-semibold mb-2">Folio de la Orden:</label>
                <input type="text" id="folioCancelar" name="folioCancelar" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="Ingrese el folio de la orden" required>
            </div>
            
            <div class="mb-4">
                <label for="motivoCancelar" class="block text-gray-700 font-semibold mb-2">Motivo de Cancelación:</label>
                <textarea id="motivoCancelar" name="motivoCancelar" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-red-500 focus:ring-1 focus:ring-red-500" rows="3" placeholder="Ingrese el motivo de la cancelación" required></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="cerrarModalCancelarOrden()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition duration-200">
                    Cancelar
                </button>
                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded transition duration-200">
                    Confirmar Cancelación
                </button>
            </div>
        </form>
    </div>
</div>