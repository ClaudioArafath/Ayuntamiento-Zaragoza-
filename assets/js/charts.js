// =============================================
// MÓDULO DE GRÁFICAS
// =============================================

// Variables globales para los gráficos
let ingresosChart = null;
let departamentosChart = null;

// Inicializar gráficos
function inicializarGraficos(etiquetas, ingresos, categorias, ingresosCat, porcentajes, filtro) {
    // Gráfico de ingresos
    const ctxLine = document.getElementById('ingresosChart');
    if (ctxLine) {
        ingresosChart = new Chart(ctxLine.getContext('2d'), {
            type: 'line',
            data: {
                labels: etiquetas,
                datasets: [{
                    label: 'Ingresos $',
                    data: ingresos,
                    borderColor: 'rgba(37, 99, 235, 1)',
                    backgroundColor: 'rgba(37, 99, 235, 0.3)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true },
                    title: {
                        display: true,
                        text: 'Ingresos por ' + capitalizarPrimeraLetra(filtro)
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // Gráfico de pastel
    const ctxPie = document.getElementById('departamentosChart');
    if (ctxPie) {
        departamentosChart = new Chart(ctxPie.getContext('2d'), {
            type: 'pie',
            data: {
                labels: categorias,
                datasets: [{
                    data: ingresosCat,
                    backgroundColor: [
                        'rgba(37, 99, 235, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(249, 115, 22, 0.7)',
                        'rgba(236, 72, 153, 0.7)',
                        'rgba(234, 179, 8, 0.7)',
                        'rgba(139, 92, 246, 0.7)',
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(101, 163, 13, 0.7)',
                        'rgba(5, 150, 105, 0.7)',
                        'rgba(20, 184, 166, 0.7)'
                    ],
                    borderColor: 'white',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'right',
                        labels: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const labelIndex = context.dataIndex;
                                const value = context.dataset.data[labelIndex];
                                const percentage = porcentajes[labelIndex];
                                return `${context.label}: $${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
}

// Función para actualizar gráficas con nuevos datos
function actualizarGraficas(data, filtroActual) {
    if (data.ingresos && ingresosChart) {
        ingresosChart.data.labels = data.ingresos.labels;
        ingresosChart.data.datasets[0].data = data.ingresos.data;
        ingresosChart.options.plugins.title.text = 'Ingresos por ' + capitalizarPrimeraLetra(filtroActual);
        ingresosChart.update();
    }

    if (data.departamentos && departamentosChart) {
        departamentosChart.data.labels = data.departamentos.labels;
        departamentosChart.data.datasets[0].data = data.departamentos.data;
        departamentosChart.update();
    }
}

// Función para actualizar resumen
function actualizarResumen(data) {
    if (data.resumen) {
        const ingresosMes = document.getElementById('ingresos-mes');
        const totalFacturas = document.getElementById('total-facturas');
        const totalCondonaciones = document.getElementById('total-condonaciones');
        
        if (ingresosMes) ingresosMes.textContent = '$' + data.resumen.ingresos_mes.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (totalFacturas) totalFacturas.textContent = data.resumen.total_facturas;
        if (totalCondonaciones) totalCondonaciones.textContent = '$' + data.resumen.total_condonaciones.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
}

// Función auxiliar
function capitalizarPrimeraLetra(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

// Función para imprimir comprobante
function imprimirComprobante(facturaId) {
    const ventana = window.open(`comprobante.php?id=${facturaId}`, '_blank');
    ventana.onload = function() {
        ventana.print();
    };
}