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
    header("Location: login.html");
    exit();
}

$rol = $usuario['rol'];
$_SESSION['rol'] = $rol;

// Obtener parámetros
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'mes';
$mes_seleccionado = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');

// Conexión a base de datos principal
$conn_lycaios = conectarLycaidosPOS();

// Consultas según el rol
if ($rol === 'Administrador' || $rol === 'Presidente') {
    require_once 'includes/queries_admin.php'; // Consultas pesadas para admin
} else {
    // Consultas ligeras para empleados
    $periodos = $ingresos = $etiquetas = $categorias = $ingresos_cat = [];
    $total_ingresos_mes = $total_facturas = $total_condonaciones = 0;
    $meses_disponibles = [];
}

// Consulta de últimos cobros (común para todos)
require_once 'includes/queries_common.php';

$conn_lycaios->close();
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

<?php include 'components/modal_qr.php'; ?>
<script>
// Pasar datos PHP a JavaScript
const datosApp = {
    // Datos para gráficas
    etiquetas: <?php echo json_encode($etiquetas); ?>,
    ingresos: <?php echo json_encode($ingresos); ?>,
    categorias: <?php echo json_encode($categorias); ?>,
    ingresosCat: <?php echo json_encode($ingresos_cat); ?>,
    porcentajes: <?php echo json_encode($porcentajes); ?>,
    
    // Variables de estado
    filtro: '<?php echo $filtro; ?>',
    mesSeleccionado: '<?php echo $mes_seleccionado; ?>',
    rol: '<?php echo $rol; ?>'
};
</script>
<script src="assets/js/charts.js"></script>
<script src="assets/js/search.js"></script>
<script src="assets/js/qr_scanner.js"></script>
<script src="assets/js/main.js"></script>

<?php include 'includes/footer.php'; ?>