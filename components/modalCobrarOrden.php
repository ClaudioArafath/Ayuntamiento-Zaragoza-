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
                <button type="button" onclick="buscarOrden()" class="mt-2 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded transition duration-200 w-full">
                    Buscar Orden
                </button>
            </div>
            
            <!-- Información de la orden -->
            <div id="info-orden" class="hidden mb-4 p-4 bg-gray-50 rounded-lg">
                <h4 class="font-semibold text-gray-700 mb-2">Información de la Orden:</h4>
                <div class="space-y-2">
                    <p><span class="font-medium">Departamento:</span> <span id="info-departamento">-</span></p>
                    <p><span class="font-medium">Descripción:</span> <span id="info-descripcion">-</span></p>
                    <p><span class="font-medium">Total a Pagar:</span> $<span id="info-total">0.00</span></p>
                    <p><span class="font-medium">Estatus:</span> <span id="info-estatus" class="font-semibold"></span></p>
                </div>
            </div>

            <!-- Mensaje de error -->
            <div id="mensaje-error" class="hidden mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded"></div>

            <!-- Sección de pago (solo visible si la orden está pendiente) -->
            <div id="seccion-pago" class="hidden mb-4">
                <div class="mb-3">
                    <label for="monto-recibido" class="block text-gray-700 font-semibold mb-2">Monto Recibido:</label>
                    <input type="number" id="monto-recibido" name="monto_recibido" step="0.01" min="0" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-500 focus:ring-1 focus:ring-green-500" placeholder="0.00">
                </div>
                
                <div id="info-cambio" class="hidden p-3 bg-green-100 border border-green-400 text-green-700 rounded mb-3">
                    <p class="font-semibold">Cambio: $<span id="monto-cambio">0.00</span></p>
                </div>

                <div id="mensaje-insuficiente" class="hidden p-3 bg-red-100 border border-red-400 text-red-700 rounded mb-3">
                    <p class="font-semibold">El monto recibido es insuficiente. No se puede realizar el cobro.</p>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="cerrarModalCobro()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition duration-200">
                    Cancelar
                </button>
                <button type="submit" id="btn-confirmar-cobro" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded transition duration-200 hidden">
                    Confirmar Cobro
                </button>
            </div>
        </form>
    </div>
</div>