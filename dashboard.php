<?php
session_start();

// Validar sesión, si no hay sesión, redirigir al login.
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

// Conexión a la base de datos principal (lycaios_pos)
$host = "localhost";
$port = 3311;
$user = "root";
$password = "";
$database = "lycaios_pos";

$conn_lycaios = new mysqli($host, $user, $password, $database, $port);
if ($conn_lycaios->connect_error) {
    die("Error de conexión: " . $conn_lycaios->connect_error);
}

// Obtener el filtro seleccionado (si existe)
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'mes';

// Obtener el mes seleccionado (si existe)
$mes_seleccionado = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');

// === CONSULTA 1: Ingresos según filtro ===
$sql_ingresos = "";
switch($filtro) {
    case 'dia':
        $sql_ingresos = "
            SELECT DATE_FORMAT(date, '%Y-%m-%d') as periodo, SUM(total) as ingresos
            FROM invoice
            WHERE date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY periodo
            ORDER BY periodo ASC
        ";
        break;
    case 'semana':
        $sql_ingresos = "
            SELECT YEARWEEK(date) as periodo, CONCAT('Sem ', YEARWEEK(date)) as etiqueta, SUM(total) as ingresos
            FROM invoice
            WHERE date >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
            GROUP BY YEARWEEK(date)
            ORDER BY periodo ASC
        ";
        break;
    case 'mes':
    default:
        $sql_ingresos = "
            SELECT DATE_FORMAT(date, '%Y-%m') as periodo, DATE_FORMAT(date, '%b %Y') as etiqueta, SUM(total) as ingresos
            FROM invoice
            GROUP BY periodo
            ORDER BY periodo ASC
        ";
        break;
}

$result_ingresos = $conn_lycaios->query($sql_ingresos);

$periodos = [];
$ingresos = [];
$etiquetas = [];

if ($result_ingresos && $result_ingresos->num_rows > 0) {
    while ($row = $result_ingresos->fetch_assoc()) {
        $periodos[] = $row['periodo'];
        $ingresos[] = $row['ingresos'];
        // Usar etiqueta personalizada si existe, de lo contrario usar periodo
        $etiquetas[] = isset($row['etiqueta']) ? $row['etiqueta'] : $row['periodo'];
    }
}

//  === CONSULTA 2: Cobros por departamento para el mes seleccionado ===
$sql_pie = "
    SELECT c.name as categoria, SUM(t.total) as ingresos
    FROM topseller t
    INNER JOIN categorias c ON t.categoryid = c.id
    WHERE DATE_FORMAT(t.date, '%Y-%m') = '$mes_seleccionado'
    GROUP BY c.name
    ORDER BY ingresos DESC
";
$result_pie = $conn_lycaios->query($sql_pie);

$categorias = [];
$ingresos_cat = [];
$total_ingresos_mes = 0;

if ($result_pie && $result_pie->num_rows > 0) {
    while ($row = $result_pie->fetch_assoc()) {
        $categorias[] = $row['categoria'];
        $ingresos_cat[] = $row['ingresos'];
        $total_ingresos_mes += $row['ingresos'];
    }
}

// Calcular porcentajes para cada categoría
$porcentajes = [];
if ($total_ingresos_mes > 0) {
    foreach ($ingresos_cat as $ingreso) {
        $porcentajes[] = round(($ingreso / $total_ingresos_mes) * 100, 2);
    }
}

// === CONSULTA 3: Obtener meses disponibles para el selector ===
$sql_meses = "
    SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') as periodo, 
           CONCAT(ELT(MONTH(date), 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'), ' ', YEAR(date)) as nombre_mes
    FROM invoice 
    ORDER BY periodo DESC
";
$result_meses = $conn_lycaios->query($sql_meses);

$meses_disponibles = [];
if ($result_meses && $result_meses->num_rows > 0) {
    while ($row = $result_meses->fetch_assoc()) {
        $meses_disponibles[$row['periodo']] = $row['nombre_mes'];
    }
}

// === CONSULTA 4: Total de facturas del mes ===
$sql_total_facturas = "
    SELECT COUNT(*) as total_facturas 
    FROM invoice 
    WHERE DATE_FORMAT(date, '%Y-%m') = '$mes_seleccionado'
";
$result_total_facturas = $conn_lycaios->query($sql_total_facturas);
$total_facturas = 0;
if ($result_total_facturas && $result_total_facturas->num_rows > 0) {
    $row = $result_total_facturas->fetch_assoc();
    $total_facturas = $row['total_facturas'];
}

// === CONSULTA 5: Últimos cobros en tiempo real ===
$sql_facturas = "SELECT id, invoicecode, date, total, employee as departamento FROM invoice ORDER BY date DESC LIMIT 8";
$result_facturas = $conn_lycaios->query($sql_facturas);

// === CONSULTA 6: Total de condonaciones (descuentos) del mes ===
$sql_condonaciones = "
    SELECT COALESCE(SUM(descuento), 0) as total_condonaciones 
    FROM invoice 
    WHERE DATE_FORMAT(date, '%Y-%m') = '$mes_seleccionado'
";
$result_condonaciones = $conn_lycaios->query($sql_condonaciones);
$total_condonaciones = 0;
if ($result_condonaciones && $result_condonaciones->num_rows > 0) {
    $row = $result_condonaciones->fetch_assoc();
    $total_condonaciones = $row['total_condonaciones'];
}

$conn_lycaios->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        .filtro-btn {
            transition: all 0.3s ease;
        }
        .data-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .mes-selector {
            padding: 0.5rem;
            border-radius: 0.25rem;
            border: 1px solid #d1d5db;
            background-color: white;
        }
        /* Animaciones para la caja de búsqueda */
#caja-busqueda {
    transition: all 0.3s ease-in-out;
    max-height: 0;
}

#caja-busqueda.mostrar {
    max-height: 200px;
}

#resultado-busqueda {
    transition: opacity 0.3s ease-in-out;
}

.btn-imprimir-resultado {
    transition: all 0.2s ease;
}

.btn-imprimir-resultado:hover {
    transform: scale(1.05);
}
    </style>
</head>
<body class="bg-gray-100">

    <!-- Header -->
    <header class="bg-orange-300 text-white p-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold"> 🏛📊Sistema integral de analisis estadistico - Zaragoza </h1>
        <div class="flex space-x-2">
            <button id="escanear-qr" class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded-lg">Escanear QR</button>
            <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg">Cerrar sesión</a>
        </div>
    </header>

    <!-- Caja de búsqueda por folio -->
<div class="bg-white shadow-md p-3">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <button id="toggle-busqueda" class="bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-lg mr-2">
                🔍
            </button>
            <span class="text-gray-700">Buscar comprobante por folio</span>
        </div>
    </div>
    
    <div id="caja-busqueda" class="mt-3 hidden overflow-hidden">
        <div class="flex space-x-2">
            <input type="text" id="input-busqueda" placeholder="Ingrese el folio del comprobante" 
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button id="btn-buscar" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                Buscar
            </button>
        </div>
        <div id="resultado-busqueda" class="mt-3 hidden">
            <!-- Aquí se mostrarán los resultados -->
        </div>
    </div>
</div>

    <!-- Contenido -->
    <main class="p-4 max-w-7xl mx-auto">
        
        <!-- Resumen de ingresos --> 
        <!-- Tarjetas de resumen -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="data-card bg-blue-50 border-l-4 border-blue-500">
                <h3 class="text-sm font-semibold text-blue-700">Ingresos del Mes</h3>
                <p class="text-2xl font-bold" id="ingresos-mes">$<?php echo number_format($total_ingresos_mes, 2); ?></p>
            </div>
            <div class="data-card bg-green-50 border-l-4 border-green-500">
                <h3 class="text-sm font-semibold text-green-700">Total de cobros</h3>
                <p class="text-2xl font-bold" id="total-facturas"><?php echo $total_facturas; ?></p>
            </div>
            <div class="data-card bg-purple-50 border-l-4 border-purple-500">
                <h3 class="text-sm font-semibold text-purple-700">Total Condonaciones (Descuentos)</h3>
                <p class="text-2xl font-bold" id="total-condonaciones">$<?php echo number_format($total_condonaciones, 2); ?></p>
            </div>
        </div>
        
        <!-- Grid de gráficas -->
        <div class="dashboard-grid mb-6">
            
            <!-- Gráfica de ingresos mensuales -->
            <div class="data-card">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Ingresos totales</h2>
                    <div class="flex space-x-2">
                        <button id="filtro-dia" class="filtro-btn px-3 py-1 rounded <?php echo $filtro == 'dia' ? 'bg-red-500 text-white' : 'bg-orange-200'; ?>" data-filtro="dia">Día</button>
                        <button id="filtro-semana" class="filtro-btn px-3 py-1 rounded <?php echo $filtro == 'semana' ? 'bg-red-500 text-white' : 'bg-orange-200'; ?>" data-filtro="semana">Semana</button>
                        <button id="filtro-mes" class="filtro-btn px-3 py-1 rounded <?php echo $filtro == 'mes' ? 'bg-red-500 text-white' : 'bg-orange-200'; ?>" data-filtro="mes">Mes</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="ingresosChart"></canvas>
                </div>
            </div>

            <!-- Gráfica de cobros por departamento -->
            <div class="data-card">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Cobros por Departamento</h2>
                    <select id="mes-selector" class="mes-selector">
                        <?php foreach ($meses_disponibles as $valor => $nombre): ?>
                            <option value="<?php echo $valor; ?>" <?php echo $valor == $mes_seleccionado ? 'selected' : ''; ?>>
                                <?php echo $nombre; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="chart-container">
                    <canvas id="departamentosChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Tabla de facturas -->
<div class="data-card">
    <h2 class="text-xl font-semibold mb-4">Últimos cobros</h2>
    <div class="overflow-x-auto">
        <table id="tabla-facturas" class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-200 text-left">
                    <th class="px-4 py-2 border">ID</th>
                    <th class="px-4 py-2 border">Folio</th>
                    <th class="px-4 py-2 border">Fecha y hora</th>
                    <th class="px-4 py-2 border">Total</th>
                    <th class="px-4 py-2 border">Departamento</th>
                    <th class="px-4 py-2 border">Comprobante</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_facturas && $result_facturas->num_rows > 0): ?>
                    <?php while ($row = $result_facturas->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-100">
                            <td class="px-4 py-2 border"><?php echo $row['id']; ?></td>
                            <td class="px-4 py-2 border"><?php echo $row['invoicecode']; ?></td>
                            <td class="px-4 py-2 border"><?php echo $row['date']; ?></td>
                            <td class="px-4 py-2 border">$<?php echo number_format($row['total'], 2); ?></td>
                            <td class="px-4 py-2 border"><?php echo htmlspecialchars($row['departamento'] ?? 'N/A'); ?></td>
                            <td class="px-4 py-2 border text-center">
                                <button onclick="imprimirComprobante(<?php echo $row['id']; ?>)" 
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                    🖨️ Imprimir
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center p-4">No hay cobros registradas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

    </main>

    <!-- Scripts de las gráficas -->
    <script>
        // Variables globales para los gráficos
        let ingresosChart = null;
        let departamentosChart = null;
        let filtroActual = '<?php echo $filtro; ?>';
        let mesSeleccionado = '<?php echo $mes_seleccionado; ?>';
        let totalIngresosMes = <?php echo $total_ingresos_mes; ?>;
        let porcentajes = <?php echo json_encode($porcentajes); ?>;

        // === Inicializar gráficos ===
        function inicializarGraficos() {
            // === Gráfico de ingresos ===
            const ctxLine = document.getElementById('ingresosChart').getContext('2d');
            ingresosChart = new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($etiquetas); ?>,
                    datasets: [{
                        label: 'Ingresos $',
                        data: <?php echo json_encode($ingresos); ?>,
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
                            text: 'Ingresos por <?php echo ucfirst($filtro); ?>'
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

            // === Gráfico de pastel ===
            const ctxPie = document.getElementById('departamentosChart').getContext('2d');
            departamentosChart = new Chart(ctxPie, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($categorias); ?>,
                    datasets: [{
                        data: <?php echo json_encode($ingresos_cat); ?>,
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

        // === Actualizar datos mediante AJAX ===
        function actualizarDatos() {
            console.log('Actualizando datos...', {filtro: filtroActual, mes: mesSeleccionado});
            
            $.ajax({
                url: 'actualizar_datos.php',
                type: 'GET',
                data: {
                    filtro: filtroActual,
                    mes: mesSeleccionado
                },
                dataType: 'json',
                success: function(data) {
                    console.log('Datos recibidos:', data);
                    
                    if (data.ingresos) {
                        // Actualizar gráfica de ingresos
                        ingresosChart.data.labels = data.ingresos.labels;
                        ingresosChart.data.datasets[0].data = data.ingresos.data;
                        ingresosChart.options.plugins.title.text = 'Ingresos por ' + capitalizarPrimeraLetra(filtroActual);
                        ingresosChart.update();
                    }

                    if (data.departamentos) {
                        // Actualizar gráfica de departamentos
                        departamentosChart.data.labels = data.departamentos.labels;
                        departamentosChart.data.datasets[0].data = data.departamentos.data;
                        
                        // Actualizar porcentajes
                        if (data.porcentajes) {
                            porcentajes = data.porcentajes;
                        }
                        
                        departamentosChart.update();
                    }

                    if (data.resumen) {
                        // Actualizar tarjetas de resumen
                        $('#ingresos-mes').text('$' + data.resumen.ingresos_mes.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#total-facturas').text(data.resumen.total_facturas);
                        $('#total-condonaciones').text('$' + data.resumen.total_condonaciones.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    }

                    if (data.facturas) {
                        // Actualizar tabla de facturas
                        let tablaBody = '';
                        if (data.facturas.length > 0) {
                            data.facturas.forEach(function(factura) {
                                tablaBody += `
                                    <tr class="hover:bg-gray-100">
                                    <td class="px-4 py-2 border">${factura.id}</td>
                                    <td class="px-4 py-2 border">${factura.invoicecode}</td>
                                    <td class="px-4 py-2 border">${factura.date}</td>
                                    <td class="px-4 py-2 border">$${parseFloat(factura.total).toFixed(2)}</td>
                                    <td class="px-4 py-2 border">${factura.departamento || 'N/A'}</td>
                                    <td class="px-4 py-2 border text-center">
                                <button onclick="imprimirComprobante(${factura.id})" 
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                            🖨️ Imprimir
                        </button>
                    </td>
                </tr>
            `;
        });
    } else {
        tablaBody = '<tr><td colspan="5" class="text-center p-4">No hay cobros registradas.</td></tr>';
    }
    $('#tabla-facturas tbody').html(tablaBody);
}
                },
                error: function(xhr, status, error) {
                    console.log('Error al actualizar los datos:', error);
                    console.log('Respuesta del servidor:', xhr.responseText);
                    // En caso de error, intentar nuevamente después de 5 segundos
                    setTimeout(actualizarDatos, 5000);
                }
            });
        }

        // === Cambiar filtro ===
        function cambiarFiltro(nuevoFiltro) {
            // Actualizar estado de botones
            $('.filtro-btn').removeClass('bg-red-500 text-white').addClass('bg-orange-200');
            $(`#filtro-${nuevoFiltro}`).removeClass('bg-orange-200').addClass('bg-red-500 text-white');
        
            // Actualizar filtro actual
            filtroActual = nuevoFiltro;
        
            // Actualizar datos inmediatamente
            actualizarDatos();
    }

        // === Cambiar mes ===
        function cambiarMes(nuevoMes) {
            // Actualizar mes seleccionado
            mesSeleccionado = nuevoMes;
            
            // Actualizar URL para mantener el estado
            const url = new URL(window.location);
            url.searchParams.set('mes', nuevoMes);
            window.history.replaceState({}, '', url);
            
            // Actualizar datos inmediatamente
            actualizarDatos();
        }

        // Función auxiliar para capitalizar la primera letra
        function capitalizarPrimeraLetra(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }
        // Función para imprimir comprobante
        function imprimirComprobante(facturaId) {
            // Abrir el comprobante en una nueva ventana
            const ventana = window.open(`comprobante.php?id=${facturaId}`, '_blank');
    
            // Esperar a que la ventana se cargue para imprimir
            ventana.onload = function() {
                ventana.print();
            };
        }

        // Inicializar gráficos al cargar la página
        $(document).ready(function() {
            inicializarGraficos();
            
            // Configurar eventos de los botones de filtro
            $('.filtro-btn').click(function() {
                const filtro = $(this).data('filtro');
                cambiarFiltro(filtro);
            });
            
            // Configurar evento del selector de mes
            $('#mes-selector').change(function() {
                const mes = $(this).val();
                cambiarMes(mes);
            });
            
            // Actualizar datos cada 5 segundos
            setInterval(actualizarDatos, 5000);
        });

        // Variables y funciones para la búsqueda
        let busquedaAbierta = false;

        // Alternar visibilidad de la caja de búsqueda
        function toggleBusqueda() {
            const cajaBusqueda = document.getElementById('caja-busqueda');
            const toggleBtn = document.getElementById('toggle-busqueda');
            
            if (busquedaAbierta) {
                cajaBusqueda.classList.add('hidden');
                cajaBusqueda.classList.remove('mostrar');
                toggleBtn.innerHTML = '🔍';
            } else {
                cajaBusqueda.classList.remove('hidden');
                cajaBusqueda.classList.add('mostrar');
                toggleBtn.innerHTML = '▼';
            }
            
            busquedaAbierta = !busquedaAbierta;
        }

// Buscar comprobante por folio
function buscarPorFolio() {
    const folio = document.getElementById('input-busqueda').value.trim();
    const resultadoDiv = document.getElementById('resultado-busqueda');
    
    if (!folio) {
        resultadoDiv.innerHTML = '<div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">Por favor ingrese un folio válido</div>';
        resultadoDiv.classList.remove('hidden');
        return;
    }
    
    // Mostrar loading
    resultadoDiv.innerHTML = '<div class="text-center py-4"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div><p class="mt-2">Buscando...</p></div>';
    resultadoDiv.classList.remove('hidden');
    
    // Realizar búsqueda via AJAX
    $.ajax({
        url: 'buscar_comprobante.php',
        type: 'GET',
        data: { folio: folio },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                resultadoDiv.innerHTML = `
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-semibold text-green-800">Comprobante Encontrado</h4>
                                <p class="text-sm text-green-600">Folio: ${data.comprobante.folio}</p>
                                <p class="text-sm text-green-600">Fecha: ${data.comprobante.fecha}</p>
                                <p class="text-sm text-green-600">Total: $${data.comprobante.total}</p>
                                <p class="text-sm text-green-600">Concepto: ${data.comprobante.concepto || 'N/A'}</p>
                            </div>
                            <button onclick="imprimirComprobante(${data.comprobante.id})" 
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm btn-imprimir-resultado">
                                🖨️ Imprimir
                            </button>
                        </div>
                    </div>
                `;
            } else {
                resultadoDiv.innerHTML = `<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4">${data.message}</div>`;
            }
        },
        error: function() {
            resultadoDiv.innerHTML = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4">Error al realizar la búsqueda</div>';
        }
    });
}

// Event listeners para la búsqueda
document.addEventListener('DOMContentLoaded', function() {
    // Toggle búsqueda
    document.getElementById('toggle-busqueda').addEventListener('click', toggleBusqueda);
    
    // Buscar al hacer clic en el botón
    document.getElementById('btn-buscar').addEventListener('click', buscarPorFolio);
    
    // Buscar al presionar Enter en el input
    document.getElementById('input-busqueda').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            buscarPorFolio();
        }
    });
});
    </script>

</body>
</html>