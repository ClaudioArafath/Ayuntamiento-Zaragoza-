// =============================================
// MÓDULO PARA COBRAR ORDEN
// =============================================

(function() {
    'use strict';
    
    let ordenActual = null;

    // Función para procesar respuesta del servidor
    function procesarRespuestaServidor(response) {
        console.log('Status HTTP:', response.status);
        console.log('Content-Type:', response.headers.get('content-type'));
        
        return response.text().then(text => {
            console.log('Respuesta cruda del servidor:', text);
            
            // Verificar si es JSON válido
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('No es JSON válido:', text);
                throw new Error('El servidor respondió con un formato inválido. Posible error PHP.');
            }
        });
    }

    // Función para abrir el modal de cobro
    function abrirModalCobro() {
        console.log('Abriendo modal de cobro...');
        const modal = document.getElementById('modalCobrarOrden');
        if (modal) {
            modal.classList.remove('hidden');
            resetearModal();
            const inputFolio = document.getElementById('folio');
            if (inputFolio) {
                inputFolio.focus();
            }
        } else {
            console.error('Modal no encontrado');
        }
    }

    // Función para cerrar el modal de cobro
    function cerrarModalCobro() {
        console.log('Cerrando modal de cobro...');
        const modal = document.getElementById('modalCobrarOrden');
        if (modal) {
            modal.classList.add('hidden');
        }
        resetearModal();
    }

    // Resetear el modal a su estado inicial
    function resetearModal() {
        console.log('Reseteando modal...');
        ordenActual = null;
        
        const elements = [
            'info-orden', 'seccion-pago', 'mensaje-error', 
            'info-cambio', 'mensaje-insuficiente', 'btn-confirmar-cobro'
        ];
        
        elements.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.classList.add('hidden');
            }
        });
        
        const folioInput = document.getElementById('folio');
        const montoInput = document.getElementById('monto-recibido');
        if (folioInput) folioInput.value = '';
        if (montoInput) montoInput.value = '';
    }

    // Buscar orden en la base de datos
    async function buscarOrden() {
        const folio = document.getElementById('folio').value.trim();
        console.log('Buscando orden con folio:', folio);
        
        if (!folio) {
            mostrarError('Por favor ingrese un folio válido');
            return;
        }

        // Mostrar loading
        const btnBuscar = document.querySelector('button[onclick="buscarOrden()"]');
        if (btnBuscar) {
            btnBuscar.textContent = 'Buscando...';
            btnBuscar.disabled = true;
        }

        try {
            const response = await fetch('api/buscar_orden.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ folio: folio })
            });

            const data = await procesarRespuestaServidor(response);
            console.log('Datos recibidos:', data);

            if (data.success) {
                ordenActual = data.orden;
                mostrarInformacionOrden(data.orden);
            } else {
                mostrarError(data.message || 'Error al buscar la orden');
            }
        } catch (error) {
            console.error('Error en buscarOrden:', error);
            mostrarError('Error de conexión al buscar la orden: ' + error.message);
        } finally {
            // Restaurar botón
            if (btnBuscar) {
                btnBuscar.textContent = 'Buscar Orden';
                btnBuscar.disabled = false;
            }
        }
    }

    // Mostrar información de la orden
    function mostrarInformacionOrden(orden) {
        console.log('Mostrando información de orden:', orden);
        
        const errorElement = document.getElementById('mensaje-error');
        if (errorElement) errorElement.classList.add('hidden');
        
        // Mostrar información básica
        document.getElementById('info-departamento').textContent = orden.employee || 'No especificado';
        document.getElementById('info-descripcion').textContent = orden.descripcion_articulos || 'Sin descripción';
        document.getElementById('info-total').textContent = parseFloat(orden.total).toFixed(2);
        
        // Auto-completar monto recibido con el total
        const montoRecibidoInput = document.getElementById('monto-recibido');
        if (montoRecibidoInput) {
            montoRecibidoInput.value = parseFloat(orden.total).toFixed(2);
        }
        
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
            if (montoRecibidoInput) {
                montoRecibidoInput.addEventListener('input', calcularCambio);
                // Calcular cambio inicial con el monto auto-completado
                setTimeout(() => calcularCambio(), 100);
            }
        }
        
        document.getElementById('info-orden').classList.remove('hidden');
    }

    // Calcular cambio
    function calcularCambio() {
        if (!ordenActual) return;
        
        const montoRecibido = parseFloat(document.getElementById('monto-recibido').value) || 0;
        const total = parseFloat(ordenActual.total);
        
        console.log('Calculando cambio - Recibido:', montoRecibido, 'Total:', total);
        
        const infoCambio = document.getElementById('info-cambio');
        const mensajeInsuficiente = document.getElementById('mensaje-insuficiente');
        
        if (infoCambio) infoCambio.classList.add('hidden');
        if (mensajeInsuficiente) mensajeInsuficiente.classList.add('hidden');
        
        if (montoRecibido > 0) {
            if (montoRecibido >= total) {
                const cambio = montoRecibido - total;
                document.getElementById('monto-cambio').textContent = cambio.toFixed(2);
                if (infoCambio) infoCambio.classList.remove('hidden');
            } else {
                if (mensajeInsuficiente) mensajeInsuficiente.classList.remove('hidden');
            }
        }
    }

    // Mostrar mensaje de error
    function mostrarError(mensaje) {
        console.error('Error:', mensaje);
        const errorElement = document.getElementById('mensaje-error');
        if (errorElement) {
            errorElement.textContent = mensaje;
            errorElement.classList.remove('hidden');
        }
    }

    // Confirmar cobro de la orden
    async function confirmarCobroOrden() {
        console.log('Confirmando cobro...');
        
        if (!ordenActual || ordenActual.estatus == 1) {
            mostrarError('No se puede cobrar esta orden');
            return;
        }

        const montoRecibido = parseFloat(document.getElementById('monto-recibido').value) || 0;
        const total = parseFloat(ordenActual.total);

        console.log('Monto recibido:', montoRecibido, 'Total:', total);

        if (montoRecibido < total) {
            mostrarError('El monto recibido es insuficiente para realizar el cobro');
            return;
        }

        // Mostrar loading
        const btnConfirmar = document.getElementById('btn-confirmar-cobro');
        if (btnConfirmar) {
            btnConfirmar.textContent = 'Procesando...';
            btnConfirmar.disabled = true;
        }

        try {
            console.log('Enviando datos al servidor...', {
                folio: ordenActual.code,
                monto_recibido: montoRecibido,
                cambio: montoRecibido - total
            });

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

            const data = await procesarRespuestaServidor(response);
            console.log('Datos de respuesta (cobro):', data);

            if (data.success) {
                alert('✅ Cobro realizado exitosamente');
                cerrarModalCobro();
                // Recargar la página para actualizar la tabla
                setTimeout(() => location.reload(), 1000);
            } else {
                mostrarError(data.message || 'Error al procesar el cobro');
            }
        } catch (error) {
            console.error('Error en confirmarCobroOrden:', error);
            mostrarError('Error de conexión al procesar el cobro: ' + error.message);
            
            // Mostrar más detalles del error
            if (error.message.includes('PHP')) {
                mostrarError('Error del servidor. Verifique los logs para más detalles.');
            }
        } finally {
            // Restaurar botón
            if (btnConfirmar) {
                btnConfirmar.textContent = 'Confirmar Cobro';
                btnConfirmar.disabled = false;
            }
        }
    }

    // Inicializar event listeners cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Inicializando módulo de cobro...');
        
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
            console.log('✅ Botón "Cobrar orden" configurado correctamente');
        }
    });

    // Hacer funciones disponibles globalmente
    window.abrirModalCobro = abrirModalCobro;
    window.cerrarModalCobro = cerrarModalCobro;
    window.buscarOrden = buscarOrden;
    window.calcularCambio = calcularCambio;
    window.confirmarCobroOrden = confirmarCobroOrden;

    console.log('Módulo de cobro cargado correctamente');

})();