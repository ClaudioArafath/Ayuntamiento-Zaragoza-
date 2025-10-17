<!-- Modal para Escanear QR y Procesar Cobro -->
<div id="modalCobroQR" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold">Escanear QR para Cobro</h3>
            <button onclick="cerrarModalCobroQR()" class="text-gray-500 hover:text-gray-700 text-2xl">
                ✕
            </button>
        </div>
        
        <!-- Paso 1: Selección de método -->
        <div id="paso-seleccion" class="space-y-4">
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <p class="text-blue-700">Seleccione el método para escanear el código QR de la orden:</p>
            </div>
            
            <div class="grid grid-cols-1 gap-3">
                <button onclick="iniciarCamara()" class="bg-green-500 hover:bg-green-600 text-white p-4 rounded-lg flex items-center justify-center">
                    <span class="text-2xl mr-2">📷</span>
                    Usar Cámara del Dispositivo
                </button>
                
                <button onclick="mostrarSubirImagen()" class="bg-blue-500 hover:bg-blue-600 text-white p-4 rounded-lg flex items-center justify-center">
                    <span class="text-2xl mr-2">🖼️</span>
                    Subir Imagen QR
                </button>
                
                <button onclick="mostrarIngresoManual()" class="bg-purple-500 hover:bg-purple-600 text-white p-4 rounded-lg flex items-center justify-center">
                    <span class="text-2xl mr-2">⌨️</span>
                    Ingresar Código Manualmente
                </button>
            </div>
        </div>

        <!-- Paso 2: Área de cámara -->
        <div id="paso-camara" class="hidden">
            <div class="mb-4">
                <div id="lector-camara" class="w-full h-64 bg-black rounded-lg flex items-center justify-center">
                    <p class="text-white">Iniciando cámara...</p>
                </div>
                <p class="text-sm text-gray-600 mt-2 text-center">Apunte la cámara hacia el código QR de la orden</p>
            </div>
            
            <div class="flex justify-between">
                <button onclick="volverASeleccion()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    ← Volver
                </button>
                <button onclick="detenerCamara()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                    Detener Cámara
                </button>
            </div>
        </div>

        <!-- Paso 3: Subir imagen -->
        <div id="paso-imagen" class="hidden">
            <div class="mb-4">
                <input type="file" id="input-imagen-qr" accept="image/*" class="hidden">
                <div id="area-subida" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer" onclick="document.getElementById('input-imagen-qr').click()">
                    <span class="text-4xl">📁</span>
                    <p class="text-gray-600">Haga clic para seleccionar imagen QR</p>
                    <p class="text-sm text-gray-400">Formatos: JPG, PNG, GIF</p>
                </div>
                <div id="vista-previa" class="hidden mt-4">
                    <img id="imagen-previa" class="mx-auto max-h-48 rounded">
                </div>
            </div>
            
            <div class="flex justify-between">
                <button onclick="volverASeleccion()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    ← Volver
                </button>
                <button onclick="procesarImagenQR()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                    Procesar Imagen
                </button>
            </div>
        </div>

        <!-- Paso 4: Ingreso manual -->
        <div id="paso-manual" class="hidden">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Ingrese el código de la orden:</label>
                <input type="text" id="codigo-manual" placeholder="Ej: ORD-2024-001" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div class="flex justify-between">
                <button onclick="volverASeleccion()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    ← Volver
                </button>
                <button onclick="validarCodigoManual()" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded">
                    Validar Código
                </button>
            </div>
        </div>

        <!-- Paso 5: Información de la orden -->
        <div id="paso-orden" class="hidden">
            <div id="info-orden" class="bg-gray-50 p-4 rounded-lg mb-4">
                <!-- Aquí se cargará la información de la orden -->
            </div>
            
            <div class="flex justify-between">
                <button onclick="volverASeleccion()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    ← Nueva Búsqueda
                </button>
                <button onclick="procesarCobro()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                    💳 Procesar Cobro
                </button>
            </div>
        </div>

        <!-- Paso 6: Resultado del cobro -->
        <div id="paso-resultado" class="hidden">
            <div id="resultado-cobro" class="p-4 rounded-lg mb-4">
                <!-- Aquí se mostrará el resultado del cobro -->
            </div>
            
            <div class="flex justify-between">
                <button onclick="volverASeleccion()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    ← Nuevo Cobro
                </button>
                <button onclick="imprimirComprobanteResultado()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                    🖨️ Imprimir Comprobante
                </button>
            </div>
        </div>
    </div>
</div>