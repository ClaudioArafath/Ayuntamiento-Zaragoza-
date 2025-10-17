// =============================================
// MÓDULO PARA COBRAR ORDEN
// =============================================

(function() {
    let ordenActual = null;

    // Función para abrir el modal de cobro
    function abrirModalCobro() {
        const modal = document.getElementById('modalCobrarOrden');
        if (modal) {
            modal.classList.remove('hidden');
            resetearModal();
            const inputFolio = document.getElementById('folio');
            if (inputFolio) {
                inputFolio.focus();
            }
        }
    }

    // Función para cerrar el modal de cobro
    function cerrarModalCobro() {
        const modal = document.getElementById('modalCobrarOrden');
        if (modal) {
            modal.classList.add('hidden');
        }
        resetearModal();
    }

    // Resetear el modal a su estado inicial
    function resetearModal() {
        ordenActual = null;
        document.getElementById('info-orden').classList.add('hidden');
        document.getElementById('seccion-pago').classList.add('hidden');
        document.getElementById('mensaje-error').classList.add('hidden');
        document.getElementById('info-cambio').classList.add('hidden');
        document.getElementById('mensaje-insuficiente').classList.add('hidden');
        document.getElementById('btn-confirmar-cobro').classList.add('hidden');
        
        const folioInput = document.getElementById('folio');
        const montoInput = document.getElementById('monto-recibido');
        if (folioInput) folioInput.value = '';
        if (montoInput) montoInput.value = '';
    }

    // Buscar orden en la base de datos
    async function buscarOrden() {
        const folio = document.getElementById('folio').value.trim();
        
        if (!folio) {
            mostrarError('Por favor ingrese un folio válido');
            return;
        }

        try {
            const response = await fetch('api/buscar_orden.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ folio: folio })
            });

            const data = await response.json();

            if (data.success) {
                ordenActual = data.orden;
                mostrarInformacionOrden(data.orden);
            } else {
                mostrarError(data.message || 'Error al buscar la orden');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error de conexión al buscar la orden');
        }
    }

    // Mostrar información de la orden
    function mostrarInformacionOrden(orden) {
        const errorElement = document.getElementById('mensaje-error');
        if (errorElement) errorElement.classList.add('hidden');
        
        // Mostrar información básica
        document.getElementById('info-departamento').textContent = orden.employee || 'No especificado';
        document.getElementById('info-descripcion').textContent = orden.descripcion_articulos || 'Sin descripción';
        document.getElementById('info-total').textContent = parseFloat(orden.total).toFixed(2);
        
        // Mostrar estatus
        const estatusElement = document.getElementById('info-estatus');
        if (orden.estatus == 1) {
            estatusElement.textContent = 'PAGADA';
            estatusElement.className = 'text-green-600 font-semibold';
            mostrarError('Esta orden ya ha sido cobrada anteriormente');
            document.getElementById('seccion-pago').classList.add('hidden');
            document.getElementById('btn-confirmar-cobro').classList.add('hidden');
        } else {
            estatusElement.textContent = 'PENDIENTE';
            estatusElement.className = 'text-orange-600 font-semibold';
            document.getElementById('seccion-pago').classList.remove('hidden');
            document.getElementById('btn-confirmar-cobro').classList.remove('hidden');
            
            // Configurar evento para calcular cambio
            const montoRecibidoInput = document.getElementById('monto-recibido');
            if (montoRecibidoInput) {
                montoRecibidoInput.value = '';
                montoRecibidoInput.focus();
                montoRecibidoInput.addEventListener('input', calcularCambio);
            }
        }
        
        document.getElementById('info-orden').classList.remove('hidden');
    }

    // Calcular cambio
    function calcularCambio() {
        const montoRecibido = parseFloat(document.getElementById('monto-recibido').value) || 0;
        const total = parseFloat(ordenActual.total);
        
        document.getElementById('info-cambio').classList.add('hidden');
        document.getElementById('mensaje-insuficiente').classList.add('hidden');
        
        if (montoRecibido > 0) {
            if (montoRecibido >= total) {
                const cambio = montoRecibido - total;
                document.getElementById('monto-cambio').textContent = cambio.toFixed(2);
                document.getElementById('info-cambio').classList.remove('hidden');
            } else {
                document.getElementById('mensaje-insuficiente').classList.remove('hidden');
            }
        }
    }

    // Mostrar mensaje de error
    function mostrarError(mensaje) {
        const errorElement = document.getElementById('mensaje-error');
        if (errorElement) {
            errorElement.textContent = mensaje;
            errorElement.classList.remove('hidden');
        }
    }

    // Confirmar cobro de la orden
    async function confirmarCobroOrden() {
        if (!ordenActual || ordenActual.estatus == 1) {
            mostrarError('No se puede cobrar esta orden');
            return;
        }

        const montoRecibido = parseFloat(document.getElementById('monto-recibido').value) || 0;
        const total = parseFloat(ordenActual.total);

        if (montoRecibido < total) {
            mostrarError('El monto recibido es insuficiente para realizar el cobro');
            return;
        }

        try {
            const response = await fetch('api/procesar_cobro.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    folio: ordenActual.code,
                    monto_recibido: montoRecibido,
                    cambio: montoRecibido - total
                })
            });

            const data = await response.json();

            if (data.success) {
                alert('✅ Cobro realizado exitosamente');
                cerrarModalCobro();
                // Recargar la página para actualizar la tabla
                setTimeout(() => location.reload(), 1000);
            } else {
                mostrarError(data.message || 'Error al procesar el cobro');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error de conexión al procesar el cobro');
        }
    }

    // Inicializar event listeners cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        const formCobro = document.getElementById('form-cobro');
        if (formCobro) {
            formCobro.addEventListener('submit', function(e) {
                e.preventDefault();
                confirmarCobroOrden();
            });
        }

        // Configurar el botón "Cobrar orden"
        const btnCobrarOrden = document.getElementById('cobrarOrden');
        if (btnCobrarOrden) {
            btnCobrarOrden.addEventListener('click', abrirModalCobro);
            console.log('Botón "Cobrar orden" configurado correctamente');
        }
    });

    // Hacer funciones disponibles globalmente
    window.abrirModalCobro = abrirModalCobro;
    window.cerrarModalCobro = cerrarModalCobro;
    window.buscarOrden = buscarOrden;
    window.calcularCambio = calcularCambio;
    window.confirmarCobroOrden = confirmarCobroOrden;

})();