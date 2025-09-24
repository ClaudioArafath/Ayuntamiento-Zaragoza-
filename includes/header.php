<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?php echo $rol; ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-orange-300 text-white p-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold">Sistema integral de analisis estadistico</h1>
        <div class="flex space-x-2 items-center">
            //<span class="bg-neutral px-4 py-2 rounded-lg">Rol: <?php echo $rol; ?></span>
            <button id="escanear-qr-admin" class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded-lg">
                Escanear QR
            </button>
            <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg">Cerrar sesión</a>
        </div>
    </header>