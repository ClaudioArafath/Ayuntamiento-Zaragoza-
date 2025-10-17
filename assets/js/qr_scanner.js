// =============================================
// MÓDULO DE COBRO QR
// =============================================

// Variables globales para el modal de cobro QR
let scanner = null;
let ordenActual = null;
let facturaGenerada = null;

// FUNCIONES PRINCIPALES DEL MODAL
function abrirModalCobroQR() {
    console.log('Abriendo modal de cobro QR...');
    const modal = document.getElementById('modalCobroQR');
    if (modal) {
        modal.classList.remove('hidden');
        mostrarPaso('seleccion');
    }
}

function cerrarModalCobroQR() {
    const modal = document.getElementById('modalCobroQR');
    if (modal) {
        modal.classList.add('hidden');
    }
    detenerCamara();
    limpiarModal();
}

function limpiarModal() {
    ordenActual = null;
    facturaGenerada = null;
    const codigoManual = document.getElementById('codigo-manual');
    const inputImagen = document.getElementById('input-imagen-qr');
    const vistaPrevia = document.getElementById('vista-previa');
    
    if (codigoManual) codigoManual.value = '';
    if (inputImagen) inputImagen.value = '';
    if (vistaPrevia) vistaPrevia.classList.add('hidden');
}

function mostrarPaso(paso) {
    console.log('Mostrando paso:', paso);
    // Ocultar todos los pasos
    const pasos = ['seleccion', 'camara', 'imagen', 'manual', 'orden', 'resultado'];
    pasos.forEach(p => {
        const elemento = document.getElementById(`paso-${p}`);
        if (elemento) {
            elemento.classList.add('hidden');
        }
    });
    
    // Mostrar el paso solicitado
    const pasoActual = document.getElementById(`paso-${paso}`);
    if (pasoActual) {
        pasoActual.classList.remove('hidden');
    }
}

function volverASeleccion() {
    detenerCamara();
    mostrarPaso('seleccion');
    limpiarModal();
}

// MÉTODO 1: CÁMARA
function iniciarCamara() {
    console.log('Iniciando cámara...');
    mostrarPaso('camara');
    
    // Simulación de cámara (para desarrollo)
    const lector = document.getElementById('lector-camara');
    if (lector) {
        lector.innerHTML = `
            <div class="text-center">
                <div class="inline-block border-2 border-green-500 p-8 rounded-lg mb-4">
                    <span class="text-4xl">📷</span>
                </div>
                <p class="text-white">Cámara activa - Buscando códigos QR...</p>
                <p class="text-yellow-300 text-sm mt-2">MODO SIMULACIÓN</p>
                <button onclick="simularDeteccionQR()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded mt-4">
                    Simular Detección QR
                </button>
            </div>
        `;
    }
}

function simularDeteccionQR() {
    const codigoSimulado = 'ORD-' + new Date().getTime();
    console.log('Código QR simulado:', codigoSimulado);
    procesarCodigoQR(codigoSimulado);
}

function detenerCamara() {
    if (scanner) {
        scanner.stop();
        scanner = null;
    }
}

// MÉTODO 2: SUBIR IMAGEN
function mostrarSubirImagen() {
    console.log('Mostrando subida de imagen...');
    mostrarPaso('imagen');
}

function procesarImagenQR() {
    const fileInput = document.getElementById('input-imagen-qr');
    if (!fileInput || !fileInput.files[0]) {
        alert('Por favor seleccione una imagen');
        return;
    }
    
    mostrarCargando('Procesando imagen QR...');
    setTimeout(() => {
        const codigoSimulado = 'ORD-' + new Date().getTime();
        procesarCodigoQR(codigoSimulado);
    }, 2000);
}

// MÉTODO 3: INGRESO MANUAL
function mostrarIngresoManual() {
    console.log('Mostrando ingreso manual...');
    mostrarPaso('manual');
}

function validarCodigoManual() {
    const codigoManual = document.getElementById('codigo-manual');
    if (!codigoManual) return;
    
    const codigo = codigoManual.value.trim();
    if (!codigo) {
        alert('Por favor ingrese un código');
        return;
    }
    
    procesarCodigoQR(codigo);
}

// PROCESAMIENTO COMÚN
function procesarCodigoQR(codigo) {
    console.log('Procesando código QR:', codigo);
    mostrarCargando('Buscando información de la orden...');
    
    // Aquí iría la llamada real al servidor
    setTimeout(() => {
        // Datos de ejemplo (simulación)
        ordenActual = {
            id: Math.floor(Math.random() * 1000),
            codigo: codigo,
            fecha: new Date().toLocaleString(),
            departamento: 'REGISTRO CIVIL',
            concepto: 'Trámite de actas',
            monto: 250.00,
            descuento: 0.00,
            total: 250.00,
            estado: 'PENDIENTE'
        };
        
        mostrarInformacionOrden();
    }, 1500);
}

function mostrarInformacionOrden() {
    const infoDiv = document.getElementById('info-orden');
    if (infoDiv && ordenActual) {
        infoDiv.innerHTML = `
            <h4 class="font-semibold text-lg mb-2">Información de la Orden</h4>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div><strong>Código:</strong></div><div>${ordenActual.codigo}</div>
                <div><strong>Fecha:</strong></div><div>${ordenActual.fecha}</div>
                <div><strong>Departamento:</strong></div><div>${ordenActual.departamento}</div>
                <div><strong>Concepto:</strong></div><div>${ordenActual.concepto}</div>
                <div><strong>Monto:</strong></div><div>$${ordenActual.monto.toFixed(2)}</div>
                <div><strong>Descuento:</strong></div><div>$${ordenActual.descuento.toFixed(2)}</div>
                <div><strong>Total a pagar:</strong></div><div class="font-bold text-green-600">$${ordenActual.total.toFixed(2)}</div>
                <div><strong>Estado:</strong></div><div><span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">${ordenActual.estado}</span></div>
            </div>
        `;
    }
    mostrarPaso('orden');
}

function procesarCobro() {
    mostrarCargando('Procesando pago...');
    
    // Simulación de procesamiento de pago
    setTimeout(() => {
        facturaGenerada = {
            id: Math.floor(Math.random() * 10000),
            folio: 'FAC-' + new Date().getTime(),
            fecha: new Date().toLocaleString(),
            monto: ordenActual.total,
            orden_id: ordenActual.id
        };
        
        mostrarResultadoCobro(true, 'Pago procesado exitosamente');
    }, 2000);
}

function mostrarResultadoCobro(exitoso, mensaje) {
    const resultadoDiv = document.getElementById('resultado-cobro');
    if (resultadoDiv) {
        if (exitoso) {
            resultadoDiv.innerHTML = `
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
                    <div class="flex items-center">
                        <span class="text-2xl mr-2">✅</span>
                        <div>
                            <h4 class="font-semibold">${mensaje}</h4>
                            <p class="text-sm">Folio: ${facturaGenerada.folio}</p>
                            <p class="text-sm">Monto: $${facturaGenerada.monto.toFixed(2)}</p>
                        </div>
                    </div>
                </div>
            `;
        } else {
            resultadoDiv.innerHTML = `
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                    <div class="flex items-center">
                        <span class="text-2xl mr-2">❌</span>
                        <div>
                            <h4 class="font-semibold">${mensaje}</h4>
                        </div>
                    </div>
                </div>
            `;
        }
    }
    mostrarPaso('resultado');
}

function imprimirComprobanteResultado() {
    if (facturaGenerada) {
        imprimirComprobante(facturaGenerada.id);
        cerrarModalCobroQR();
    }
}

function mostrarCargando(mensaje) {
    const infoOrden = document.getElementById('info-orden');
    if (infoOrden) {
        infoOrden.innerHTML = `
            <div class="text-center py-4">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                <p class="mt-2">${mensaje}</p>
            </div>
        `;
    }
}

// Configurar eventos de subida de imagen
document.addEventListener('DOMContentLoaded', function() {
    const inputImagen = document.getElementById('input-imagen-qr');
    if (inputImagen) {
        inputImagen.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('imagen-previa');
                    const vistaPrevia = document.getElementById('vista-previa');
                    if (img && vistaPrevia) {
                        img.src = e.target.result;
                        vistaPrevia.classList.remove('hidden');
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
});