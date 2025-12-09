// =============================================
// MÓDULO PARA TABLA DE ÓRDENES CON BOTONES HOVER
// =============================================

(function () {
    'use strict';

    // Función para abrir modal de cobro con folio pre-llenado
    function abrirModalCobrarConFolio(folio) {
        // Abrir el modal
        if (typeof window.abrirModalCobro === 'function') {
            window.abrirModalCobro();
        } else {
            console.error('❌ Función abrirModalCobro no encontrada');
        }

        // Pre-llenar el folio
        setTimeout(() => {
            const folioInput = document.getElementById('folio');
            if (folioInput) {
                folioInput.value = folio;
                // Disparar evento de búsqueda automáticamente
                if (typeof window.buscarOrden === 'function') {
                    window.buscarOrden();
                }
            }
        }, 100);
    }

    // Función para abrir página de impresión
    async function abrirPaginaImpresion(orderCode) {
        try {
            const response = await fetch(`api/get_invoice_code.php?order_code=${encodeURIComponent(orderCode)}`);
            const data = await response.json();

            if (data.success && data.invoice_code) {
                // Abrir página de impresión en nueva pestaña
                window.open(`api/generar_comprobante.php?invoice_code=${data.invoice_code}`, '_blank');
            } else {
                console.error('❌ Error al obtener invoice_code:', data.message);
                alert('❌ Error: ' + (data.message || 'No se pudo obtener el código de factura'));
            }
        } catch (error) {
            console.error('❌ Error al obtener invoice_code:', error);
            alert('❌ Error de conexión al obtener el código de factura');
        }
    }

    // Función para crear el botón hover
    function crearBotonHover(estatus, folio) {
        const button = document.createElement('button');
        button.className = 'orden-hover-btn';

        if (estatus === 'Pendiente' || estatus === 0) {
            button.textContent = '💲 Cobrar';
            button.classList.add('btn-cobrar');
            button.onclick = (e) => {
                e.stopPropagation();
                abrirModalCobrarConFolio(folio);
            };
        } else {
            button.textContent = '🖨️ Imprimir';
            button.classList.add('btn-imprimir');
            button.onclick = (e) => {
                e.stopPropagation();
                abrirPaginaImpresion(folio);
            };
        }

        return button;
    }

    // Función para inicializar los eventos hover en las celdas de estatus
    function inicializarHoverBotones() {
        const tablaCeldas = document.querySelectorAll('.orden-status-cell');

        if (tablaCeldas.length === 0) {
            return;
        }

        tablaCeldas.forEach((celda) => {
            const folio = celda.dataset.folio;
            const estatus = celda.dataset.estatus;

            // Crear contenedor para el botón si no existe
            let contenedor = celda.querySelector('.hover-btn-container');
            if (!contenedor) {
                contenedor = document.createElement('div');
                contenedor.className = 'hover-btn-container';
                celda.appendChild(contenedor);
            }

            // Limpiar contenedor antes de agregar nuevo botón
            contenedor.innerHTML = '';

            // Crear el botón
            const boton = crearBotonHover(estatus, folio);
            contenedor.appendChild(boton);

            // Eventos de hover (sin logs para evitar spam en consola)
            celda.addEventListener('mouseenter', () => {
                contenedor.classList.add('visible');
            });

            celda.addEventListener('mouseleave', () => {
                contenedor.classList.remove('visible');
            });
        });
    }

    // Función de inicialización con reintentos
    function inicializar() {
        const tabla = document.getElementById('tabla-ordenes-body');
        if (!tabla) {
            setTimeout(inicializar, 500);
            return;
        }

        inicializarHoverBotones();
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        inicializar();
    }

    // Exponer función para reinicializar después de actualizar la tabla
    window.reinicializarHoverBotones = inicializarHoverBotones;

})();
