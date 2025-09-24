<?php
// Dashboard para Empleados
if ($rol === 'Empleado'):
?>
<main class="p-4 max-w-6xl mx-auto">
    <!-- Header para empleados -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-lg">
        <h2 class="text-xl font-semibold text-blue-800">Panel de Empleado</h2>
        <p class="text-blue-600">Bienvenido <?php echo $_SESSION['username']; ?></p>
    </div>

    <!-- Herramientas rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="data-card bg-white shadow-lg">
            <h3 class="text-lg font-semibold mb-4">Acciones Rápidas</h3>
            <div class="space-y-3">
                <button id="escanear-qr-empleado" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-lg flex items-center">
                    <span class="text-2xl mr-2">📱</span>Escanear QR para cobro
                </button>
                <button onclick="buscarPorFolio()" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-lg flex items-center">
                    <span class="text-2xl mr-2">🔍</span>Buscar comprobante
                </button>
            </div>
        </div>
    </div>

    <!-- Tabla de últimos cobros -->
    <div class="data-card bg-white shadow-lg">
        <h2 class="text-xl font-semibold mb-4">Nuevas ordenes</h2>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-200 text-left">
                        <th class="px-4 py-2 border">Folio</th>
                        <th class="px-4 py-2 border">Fecha</th>
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
                        <tr><td colspan="5" class="text-center p-4">No hay cobros registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php endif; ?>