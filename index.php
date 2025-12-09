<?php
// Incluir configuraciones
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Obtener información del usuario
$username = $_SESSION['username'];
$usuario = obtenerUsuario($username);

if (!$usuario) {
    session_destroy();
    header("Location: views/login.html");
    exit();
}

$rol = $usuario['rol'];
$_SESSION['rol'] = $rol;

// Obtener parámetros
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'mes';
$mes_seleccionado = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');

// Conexión a base de datos principal
$conn_lycaios = conectarLycaidosPOS();
$conn_ayuntamiento = conectarAyuntamiento(); // Para ordenes_backup

// Consultas según el rol
if ($rol === 'Administrador' || $rol === 'Presidente') {
    require_once 'includes/queries_admin.php'; // Consultas pesadas para admin
} else {
    // Consultas ligeras para empleados
    $periodos = $ingresos = $etiquetas = $categorias = $ingresos_cat = [];
    $total_ingresos_mes = $total_facturas = $total_condonaciones = 0;
    $meses_disponibles = [];
}

// Consulta de últimos cobros (ESTA YA TIENE LOS DATOS CORRECTOS)
require_once 'includes/queries_common.php';

// USAR DIRECTAMENTE $cobros_con_categoria QUE YA TIENE EL ESTATUS CORRECTO
$ordenes_iniciales = $cobros_con_categoria;

$conn_lycaios->close();
$conn_ayuntamiento->close();
    ?>
        <?php include 'includes/header.php'; ?>

        <?php 
        // Incluir dashboard según rol
        if ($rol === 'Administrador' || $rol === 'Presidente') {
            include 'components/admin_dashboard.php';
        } else {
            include 'components/employee_dashboard.php';
        }
    ?>
    <!-- Incluir modal para escanear QR-->
<?php include 'components/modal_qr.php'; ?>
    <!-- Incluir modal para cancelar orden-->
<?php include 'components/modalCancelarOrden.php'; ?>
    <!-- Incluir modal para cobrar orden-->
<?php include 'components/modalCobrarOrden.php'; ?>
    <!-- Incluir modal para orden personalizada -->
<?php include 'components/modalOrdenPersonalizada.php'; ?>

<!-- Definir datosApp ANTES de cargar cualquier script -->
<script>
// Pasar datos PHP a JavaScript con manejo robusto de errores
try {
    const datosApp = {
        // Datos para gráficas (con flags para manejar caracteres especiales)
        etiquetas: <?php echo json_encode($etiquetas ?? [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
        ingresos: <?php echo json_encode($ingresos ?? [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
        categorias: <?php echo json_encode($categorias ?? [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
        ingresosCat: <?php echo json_encode($ingresos_cat ?? [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
        porcentajes: <?php echo json_encode($porcentajes ?? [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
        ordenes: <?php echo json_encode($ordenes_iniciales ?? [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?>,
        
        // Variables de estado
        filtro: '<?php echo htmlspecialchars($filtro, ENT_QUOTES, 'UTF-8'); ?>',
        mesSeleccionado: '<?php echo htmlspecialchars($mes_seleccionado, ENT_QUOTES, 'UTF-8'); ?>',
        rol: '<?php echo htmlspecialchars($rol, ENT_QUOTES, 'UTF-8'); ?>'
    };
    
    // Verificar en consola que se cargó correctamente
    console.log('✅ datosApp definido correctamente:', datosApp);
    
    // Hacer datosApp global
    window.datosApp = datosApp;
} catch (error) {
    console.error('❌ Error al definir datosApp:', error);
    // Definir datosApp con valores por defecto en caso de error
    window.datosApp = {
        etiquetas: [],
        ingresos: [],
        categorias: [],
        ingresosCat: [],
        porcentajes: [],
        ordenes: [],
        filtro: 'mes',
        mesSeleccionado: '<?php echo date('Y-m'); ?>',
        rol: '<?php echo htmlspecialchars($rol, ENT_QUOTES, 'UTF-8'); ?>'
    };
    console.warn('⚠️ Se usaron valores por defecto para datosApp');
}
</script>

<!-- Cargar scripts DESPUÉS de definir datosApp -->
<script src="assets/js/charts.js" defer></script>
<script src="assets/js/search.js" defer></script>
<script src="assets/js/qr_scanner.js" defer></script>
<script src="assets/js/cobrarOrden.js" defer></script>
<script src="assets/js/sanitarios.js" defer></script>
<script src="assets/js/main.js" defer></script>
<script src="assets/js/cancelarOrden.js" defer></script>

<?php include 'includes/footer.php'; ?>