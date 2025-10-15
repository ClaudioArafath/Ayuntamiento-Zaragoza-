<!-- Modal para Cobrar Orden -->
<div id="modalCobrarOrden" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-green-600">Cobrar Orden</h3>
            <button type="button" onclick="cerrarModalCobro()" class="text-gray-500 hover:text-gray-700 text-2xl">
                ✕
            </button>
        </div>
        
        <form id="form-cobro">
            <div class="mb-4">
                <label for="folio" class="block text-gray-700 font-semibold mb-2">Folio de la Orden:</label>
                <input type="text" id="folio" name="folio" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-500 focus:ring-1 focus:ring-green-500" placeholder="Ingrese el folio de la orden" required>
            </div>
            
            <!-- Agrega aquí más campos si los necesitas -->
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="cerrarModalCobro()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition duration-200">
                    Cancelar
                </button>
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded transition duration-200">
                    Aceptar
                </button>
            </div>
        </form>
    </div>
</div>