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
    die("Error de conexión: " . $conn_lycaios->connect_error); // Manejo de error de conexión
}

//Conexion a la base de datos secundaria (usuarios) de PHPmyAdmin
$host = "localhost";
$port = 3306;
$user = "root";
$password = "";
$database = "ayuntamiento";

$conn_ayuntamiento = new mysqli($host, $user, $password, $database, $port);
if ($conn_ayuntamiento->connect_error) {
    die("Error de conexión: " . $conn_ayuntamiento->connect_error);
}
// Obtener información del usuario logueado
$username = $_SESSION['username'];
$sql_usuario = "SELECT rol FROM usuarios WHERE username = '" . $conn_ayuntamiento->real_escape_string($username) . "'";
$result_usuario = $conn_ayuntamiento->query($sql_usuario);

if ($result_usuario && $result_usuario->num_rows > 0) {
    $usuario = $result_usuario->fetch_assoc();
    $rol = $usuario['rol'];
    $_SESSION['rol'] = $rol; // Guardar rol en sesión
} else {
    // Si no encuentra el usuario, cerrar sesión
    session_destroy();
    header("Location: login.html");
    exit();
}

// Obtener el filtro seleccionado
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'mes';

// Obtener el mes seleccionado
$mes_seleccionado = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');

if ($rol === 'Administrador' || $rol === 'Presidente') {
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
}

// === CONSULTA 5: Últimos cobros en tiempo real ===
$sql_facturas = "SELECT id, invoicecode, date, total, items FROM invoice ORDER BY date DESC LIMIT 8";
$result_facturas = $conn_lycaios->query($sql_facturas);

// Procesar los resultados para extraer la categoría
$cobros_con_categoria = [];
if ($result_facturas && $result_facturas->num_rows > 0) {
    while ($row = $result_facturas->fetch_assoc()) {
        $categoria = 'N/A';
        $folio = $row['invoicecode'];

        // Extraer la categoría del JSON
        $categoryFromJson = obtenerCategoryDesdeItems($row['items']);
        
        if ($categoryFromJson == 0) {
            // Si Category es 0, buscar en la tabla ordenes
            $categoria = obtenerDepartamentoDesdeOrdenes($folio, $conn_lycaios);
        } else {
        
        // Extraccion la categoría del JSON
        if (!empty($row['items'])) {
            $items_data = json_decode($row['items'], true);
            if (is_array($items_data) && count($items_data) > 0) {
                // Tomar la categoría del primer item
                $primer_item = $items_data[0];
                if (isset($primer_item['Category']) && $primer_item['Category'] != 0) {
                        $categoria = obtenerNombreCategoria($primer_item['Category']);
                    }
                }
            }
        }
        
        $cobros_con_categoria[] = [
            'id' => $row['id'],
            'invoicecode' => $row['invoicecode'],
            'date' => $row['date'],
            'total' => $row['total'],
            'categoria' => $categoria
        ];
    }
}

// Función para extraer el valor de Category desde el JSON
function obtenerCategoryDesdeItems($items_json) {
    if (empty($items_json)) {
        return 0;
    }
    
    $items_data = json_decode($items_json, true);
    if (is_array($items_data) && count($items_data) > 0) {
        $primer_item = $items_data[0];
        if (isset($primer_item['Category'])) {
            return (int)$primer_item['Category'];
        }
    }
    
    return 0;
}
// Función para obtener departamento desde la tabla ordenes
function obtenerDepartamentoDesdeOrdenes($folio, $conn) {
    // Primero verificar si la tabla ordenes existe
    $tabla_existe = $conn->query("SHOW TABLES LIKE 'ordenes'");
    if ($tabla_existe && $tabla_existe->num_rows > 0) {
        // Buscar el employee en la tabla ordenes
        $sql_ordenes = "SELECT employee FROM ordenes WHERE employee = '" . $conn->real_escape_string($folio) . "' LIMIT 1";
        $result = $conn->query($sql_ordenes);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return !empty($row['employee']) ? $row['employee'] : 'N/A';
        }
    }
    
    return 'N/A';
}

// Función para obtener el nombre de la categoría según el número
function obtenerNombreCategoria($categoryId) {
    $categorias = [
        2 => 'INDUSTRIA Y COMERCIO',
        3 => 'REGISTRO CIVIL',
        4 => 'SECRETARÍA DEL AYUNTAMIENTO',
        5 => 'PANTEONES, PARQUES Y JARDINES',
        6 => 'VIALIDAD',
        7 => 'JUZGADO',
        8 => 'SINDICATURA',
        10 => 'PROTECCIÓN CIVIL',
        11 => 'RECAUDACIÓN',
        12 => 'PATRIMONIO Y HACIENDA PÚBLICA',
        13 => 'OBRAS PÚBLICAS',
        14 => 'CONTRALORÍA',
        15 => 'DESARROLLO RURAL',    
    ];
    
    return $categorias[$categoryId] ?? 'CATEGORÍA ' . $categoryId;
}

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

// Para empleados, no necesitamos las otras consultas pesadas
if ($rol === 'Empleado') {
    // Consultas ligeras o vacías para empleados
    $periodos = $ingresos = $etiquetas = $categorias = $ingresos_cat = [];
    $total_ingresos_mes = $total_facturas = $total_condonaciones = 0;
    $meses_disponibles = [];
}
    // Cerrar la conexión
    $conn_lycaios->close();
    ?>

    <!-- ===== HTML y Tailwind CSS para el dashboard ==== -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?php echo $rol; ?></title>
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
                <h1 class="text-2xl font-bold"> Sistema integral de analisis estadistico</h1>
                <div class="flex space-x-2 items-center">
                    <span class="bg-neutral px-4 py-2 rounded-lg">Rol: <?php echo $rol; ?></span>
                    <!-- AGREGAR ESTE BOTÓN -->
                    <button id="escanear-qr-admin" class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded-lg">
                        Escanear QR
                    </button>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg">Cerrar sesión</a>
                </div>
            </header>

        <!-- DASHBOARD PARA ADMINISTRADORES/PRESIDENTES -->
    <?php if ($rol === 'Administrador' || $rol === 'Presidente'): ?>
    <main class="p-4 max-w-7xl mx-auto">

        <!-- Tarjetas de resumen de ingresos -->
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
        <!-- Caja de búsqueda -->
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
        
<!-- Tabla de facturas -->
<div class="data-card">
    <h2 class="text-xl font-semibold mb-4">Últimos cobros</h2>
    <div class="overflow-x-auto">
        <table id="tabla-facturas" class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-200 text-left">
                    <th class="px-4 py-2 border">Folio</th>
                    <th class="px-4 py-2 border">Fecha y hora</th>
                    <th class="px-4 py-2 border">Total</th>
                    <th class="px-4 py-2 border">Departamento</th>
                    <th class="px-4 py-2 border">Comprobante</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($cobros_con_categoria)): ?>
                    <?php foreach ($cobros_con_categoria as $cobro): ?>
                        <tr class="hover:bg-gray-100">
                            <td class="px-4 py-2 border"><?php echo $cobro['invoicecode']; ?></td>
                            <td class="px-4 py-2 border"><?php echo $cobro['date']; ?></td>
                            <td class="px-4 py-2 border">$<?php echo number_format($cobro['total'], 2); ?></td>
                            <td class="px-4 py-2 border"><?php echo htmlspecialchars($cobro['categoria']); ?></td>
                            <td class="px-4 py-2 border text-center">
                                <button onclick="imprimirComprobante(<?php echo $cobro['id']; ?>)" 
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                    🖨️ Imprimir
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center p-4">No hay facturas registradas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</main>
<?php else: ?>

        <!-- DASHBOARD PARA EMPLEADOS -->
        <main class="p-4 max-w-6xl mx-auto">
            <!-- Header para empleados -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-lg">
                <h2 class="text-xl font-semibold text-blue-800">Panel de Empleado - Módulo de Recaudación</h2>
                <p class="text-blue-600">Bienvenido <?php echo $_SESSION['username']; ?>, aquí puedes gestionar cobros e imprimir comprobantes.</p>
            </div>

            <!-- Herramientas rápidas para empleados -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Tarjeta de acciones rápidas -->
                <div class="data-card bg-white shadow-lg">
                    <h3 class="text-lg font-semibold mb-4">Acciones Rápidas</h3>
                    <div class="space-y-3">
                        <button id="escanear-qr-empleado" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-lg flex items-center">
                            <span class="text-2xl mr-2">📱</span>Escanear QR para cobro
                        </button>
                        <button onclick="buscarPorFolio()" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-lg flex items-center">
                            <span class="text-2xl mr-2">🔍</span>
                            Buscar comprobante por folio
                        </button>
                        <button onclick="window.open('nuevo_cobro.php', '_blank')" class="w-full bg-purple-500 hover:bg-purple-600 text-white px-4 py-3 rounded-lg flex items-center">
                            <span class="text-2xl mr-2">💳</span>
                            Registrar nuevo cobro
                        </button>
                    </div>
                </div>

                <!-- Tarjeta de herramientas extras -->
                <div class="data-card bg-white shadow-lg">
                    <h3 class="text-lg font-semibold mb-4">Más herramientas</h3>
                    <div class="space-y-3">
                        <button onclick="abrirModalQR()" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-lg flex items-center">
                            <span class="text-2xl mr-2">📊</span>
                            Exportar XML
                        </button>
                        <button onclick="buscarPorFolio()" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-lg flex items-center">
                            <span class="text-2xl mr-2">✂</span>
                            Corte Z
                        </button>
                        <button onclick="window.open('nuevo_cobro.php', '_blank')" class="w-full bg-purple-500 hover:bg-purple-600 text-white px-4 py-3 rounded-lg flex items-center">
                            <span class="text-2xl mr-2">⚔</span>
                            Corte X
                        </button>
                    </div>
                </div>
            </div>
            <!-- Tabla de últimos cobros (misma que para admin pero sin gráficas) -->
            <div class="data-card bg-white shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Nuevas ordenes</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table id="tabla-facturas" class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-200 text-left">
                                <th class="px-4 py-2 border">Folio</th>
                                <th class="px-4 py-2 border">Fecha y hora</th>
                                <th class="px-4 py-2 border">Total</th>
                                <th class="px-4 py-2 border">Departamento</th>
                                <th class="px-4 py-2 border">Comprobante</th>
                                <th class="px-4 py-2 border">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($cobros_con_categoria)): ?>
                                <?php foreach ($cobros_con_categoria as $cobro): ?>
                                    <tr class="hover:bg-gray-100">
                                        <td class="px-4 py-2 border"><?php echo $cobro['invoicecode']; ?></td>
                                        <td class="px-4 py-2 border"><?php echo $cobro['date']; ?></td>
                                        <td class="px-4 py-2 border">$<?php echo number_format($cobro['total'], 2); ?></td>
                                        <td class="px-4 py-2 border"><?php echo htmlspecialchars($cobro['categoria']); ?></td>
                                        <td class="px-4 py-2 border text-center">
                                            <button onclick="imprimirComprobante(<?php echo $cobro['id']; ?>)" 
                                                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                                🖨️ Imprimir
                                            </button>
                                        </td>
                                        <td class="px-4 py-2 border text-center">
                                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                                                Completado
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center p-4">No hay cobros registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    <?php endif; ?>

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

    <!-- Scripts de las gráficas -->
    <script>
        // Variables globales para los gráficos
let ingresosChart = null;
let departamentosChart = null;
let filtroActual = '<?php echo $filtro; ?>';
let mesSeleccionado = '<?php echo $mes_seleccionado; ?>';
let totalIngresosMes = <?php echo $total_ingresos_mes; ?>;
let porcentajes = <?php echo json_encode($porcentajes); ?>;

// Variables globales para el modal de cobro QR
let scanner = null;
let ordenActual = null;
let facturaGenerada = null;

// === Inicializar gráficos ===
function inicializarGraficos() {
    // === Gráfico de ingresos ===
    const ctxLine = document.getElementById('ingresosChart');
    if (ctxLine) {
        ingresosChart = new Chart(ctxLine.getContext('2d'), {
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
    }

    // === Gráfico de pastel ===
    const ctxPie = document.getElementById('departamentosChart');
    if (ctxPie) {
        departamentosChart = new Chart(ctxPie.getContext('2d'), {
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
            
            if (data.ingresos && ingresosChart) {
                // Actualizar gráfica de ingresos
                ingresosChart.data.labels = data.ingresos.labels;
                ingresosChart.data.datasets[0].data = data.ingresos.data;
                ingresosChart.options.plugins.title.text = 'Ingresos por ' + capitalizarPrimeraLetra(filtroActual);
                ingresosChart.update();
            }

            if (data.departamentos && departamentosChart) {
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
                const ingresosMes = document.getElementById('ingresos-mes');
                const totalFacturas = document.getElementById('total-facturas');
                const totalCondonaciones = document.getElementById('total-condonaciones');
                
                if (ingresosMes) ingresosMes.textContent = '$' + data.resumen.ingresos_mes.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                if (totalFacturas) totalFacturas.textContent = data.resumen.total_facturas;
                if (totalCondonaciones) totalCondonaciones.textContent = '$' + data.resumen.total_condonaciones.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            if (data.facturas) {
                // Actualizar tabla de facturas
                let tablaBody = '';
                if (data.facturas.length > 0) {
                    data.facturas.forEach(function(factura) {
                        tablaBody += `
                            <tr class="hover:bg-gray-100">
                                <td class="px-4 py-2 border">${factura.invoicecode}</td>
                                <td class="px-4 py-2 border">${factura.date}</td>
                                <td class="px-4 py-2 border">$${parseFloat(factura.total).toFixed(2)}</td>
                                <td class="px-4 py-2 border">${factura.categoria}</td>
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
                    tablaBody = '<tr><td colspan="5" class="text-center p-4">No hay facturas registradas.</td></tr>';
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

// =============================================
// MÓDULO DE BÚSQUEDA POR FOLIO
// =============================================

let busquedaAbierta = false;

// Alternar visibilidad de la caja de búsqueda
function toggleBusqueda() {
    const cajaBusqueda = document.getElementById('caja-busqueda');
    const toggleBtn = document.getElementById('toggle-busqueda');
    
    if (cajaBusqueda && toggleBtn) {
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
}

// Buscar comprobante por folio
function buscarPorFolio() {
    const inputBusqueda = document.getElementById('input-busqueda');
    const resultadoDiv = document.getElementById('resultado-busqueda');
    
    if (!inputBusqueda || !resultadoDiv) return;
    
    const folio = inputBusqueda.value.trim();
    
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

// =============================================
// MÓDULO DE COBRO QR - VERSIÓN CORREGIDA
// =============================================

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
    
    setTimeout(() => {
        // Datos de ejemplo
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

// =============================================
// CONFIGURACIÓN DE EVENT LISTENERS AL CARGAR LA PÁGINA
// =============================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard cargado - Configurando event listeners...');
    
    // Inicializar gráficos si existen
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
    
    // Configurar botones de escaneo QR
    const botonesQR = [
        'escanear-qr-admin',    // Botón del header para admin
        'escanear-qr-empleado'  // Botón del panel para empleados
    ];
    
    botonesQR.forEach(id => {
        const boton = document.getElementById(id);
        if (boton) {
            console.log('Configurando botón QR:', id);
            boton.addEventListener('click', abrirModalCobroQR);
        }
    });
    
    // Configurar eventos de búsqueda
    const btnBuscar = document.getElementById('btn-buscar');
    const inputBusqueda = document.getElementById('input-busqueda');
    
    if (btnBuscar) {
        btnBuscar.addEventListener('click', buscarPorFolio);
    }
    
    if (inputBusqueda) {
        inputBusqueda.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                buscarPorFolio();
            }
        });
    }
    
    // Configurar eventos dentro del modal de QR
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
    
    // Actualizar datos cada 5 segundos
    setInterval(actualizarDatos, 5000);
    
    console.log('Event listeners configurados correctamente');
});
        </script>
    </body>
</html>
