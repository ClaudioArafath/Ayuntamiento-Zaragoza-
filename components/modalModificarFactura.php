<!-- Modal para Modificar Factura -->
<div id="modalModificarFactura" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h2 class="text-2xl font-bold">Modificar Factura</h2>
            <button class="close" onclick="cerrarModalModificarFactura()">&times;</button>
        </div>
        
        <div class="modal-body">
            <!-- Sección de búsqueda -->
            <div class="mb-6">
                <label class="block text-sm font-semibold mb-2">Buscar Factura por Folio</label>
                <div class="flex gap-2">
                    <input 
                        type="text" 
                        id="buscar-folio-factura" 
                        class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Ingrese el folio de la factura (ej: 0009643)"
                    >
                    <button 
                        onclick="buscarFactura()" 
                        class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-2 rounded-lg font-semibold">
                        Buscar
                    </button>
                </div>
                <p id="mensaje-busqueda" class="text-sm mt-2"></p>
            </div>

            <!-- Sección de datos de la factura (oculta hasta que se encuentre) -->
            <div id="seccion-factura" style="display: none;">
                <form id="form-modificar-factura">
                    <input type="hidden" id="factura-id" name="id">
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <!-- Folio (solo lectura) -->
                        <div>
                            <label class="block text-sm font-semibold mb-2">Folio</label>
                            <input 
                                type="text" 
                                id="factura-folio" 
                                class="w-full px-4 py-2 border rounded-lg bg-gray-100" 
                                readonly
                            >
                        </div>

                        <!-- Fecha -->
                        <div>
                            <label class="block text-sm font-semibold mb-2">Fecha</label>
                            <input 
                                type="datetime-local" 
                                id="factura-fecha" 
                                name="date"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            >
                        </div>

                        <!-- Empleado -->
                        <div>
                            <label class="block text-sm font-semibold mb-2">Empleado</label>
                            <input 
                                type="text" 
                                id="factura-empleado" 
                                name="employee"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            >
                        </div>

                        <!-- Subtotal -->
                        <div>
                            <label class="block text-sm font-semibold mb-2">Subtotal</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                id="factura-subtotal" 
                                name="subtotal"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            >
                        </div>

                        <!-- Total -->
                        <div>
                            <label class="block text-sm font-semibold mb-2">Total</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                id="factura-total" 
                                name="total"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            >
                        </div>

                        <!-- Monto Pagado -->
                        <div>
                            <label class="block text-sm font-semibold mb-2">Monto Pagado</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                id="factura-pagado" 
                                name="payed"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            >
                        </div>
                    </div>

                    <!-- Nombre del Cliente -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2">Nombre del Cliente</label>
                        <input 
                            type="text" 
                            id="factura-cliente-nombre" 
                            name="cliente_nombre"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        >
                    </div>

                    <!-- Dirección del Cliente -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2">Dirección del Cliente</label>
                        <input 
                            type="text" 
                            id="factura-cliente-direccion" 
                            name="cliente_direccion"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        >
                    </div>

                    <!-- Items (solo lectura, mostrado como lista) -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2">Artículos</label>
                        <div id="factura-items" class="w-full px-4 py-2 border rounded-lg bg-gray-50 max-h-40 overflow-y-auto">
                            <!-- Se llenará dinámicamente -->
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex gap-3 mt-6">
                        <button 
                            type="button" 
                            onclick="guardarCambiosFactura()" 
                            class="flex-1 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-semibold">
                            💾 Guardar Cambios
                        </button>
                        <button 
                            type="button" 
                            onclick="eliminarFactura()" 
                            class="flex-1 bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg font-semibold">
                            🗑️ Eliminar Factura
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para el modal de modificar factura */
#modalModificarFactura {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
}

#modalModificarFactura .modal-content {
    background-color: #fefefe;
    margin: 2% auto;
    padding: 0;
    border-radius: 10px;
    width: 90%;
    max-width: 800px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

#modalModificarFactura .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
}

#modalModificarFactura .modal-body {
    padding: 20px;
}

#modalModificarFactura .close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    background: none;
    border: none;
}

#modalModificarFactura .close:hover,
#modalModificarFactura .close:focus {
    color: #000;
}
</style>
