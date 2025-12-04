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
    <div class="mb-6">
        <div class="data-card bg-white shadow-lg">
            <h3 class="text-lg font-semibold mb-4">Acciones Rápidas</h3>
            <div class="grid grid-cols-2 gap-2 mb-2">
                <button id="cobrarOrden" class="w-full bg-orange-500 hover:bg-orange-600 text-white px-4 py-3 rounded-lg flex items-center">
                    <span class="text-2xl mr-2">💲</span>Cobrar orden
                </button>
                <button id="escanear-qr-empleado" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-lg flex items-center">
                    <span class="text-2xl mr-2">📱</span>Escanear QR para cobro
                </button>
                <button onclick="buscarPorFolio()" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-lg flex items-center">
                    <span class="text-2xl mr-2">🔍</span>Buscar comprobante
                </button>
                <button id="cancelarOrden" class="w-full bg-red-500 hover:bg-red-600 text-white px-4 py-3 rounded-lg flex items-center">
                    <span class="text-2xl mr-2">❌</span>Cancelar orden
                </button>
                <button id="ordenPersonalizada" class="w-full bg-amber-500 hover:bg-amber-600 text-white px-4 py-3 rounded-lg flex items-center">
                    <span class="text-2xl mr-2">🚽</span>Cobro de sanitarios
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
                        <th class="px-4 py-2 border">Fecha y hora</th>
                        <th class="px-4 py-2 border">Departamento</th>
                        <th class="px-4 py-2 border">Descripción</th>
                        <th class="px-4 py-2 border">Subtotal</th>
                        <th class="px-4 py-2 border">Total</th>
                        <th class="px-4 py-2 border">Estatus</th>
                    </tr>
                </thead>
                <tbody id="tabla-ordenes-body">
                    <?php 
                    if (!empty($ordenes_iniciales) && is_array($ordenes_iniciales)): 
                        foreach ($ordenes_iniciales as $orden): 
                            // Usar estatus_texto si existe, de lo contrario determinar basado en estatus_num
                            $estatus_texto = $orden['estatus_texto'] ?? (($orden['estatus_num'] == 1) ? 'Pagado' : 'Pendiente');
                            $badge_class = ($orden['estatus'] == 1) ? 'success' : 'warning';
                    ?>
                        <tr>
                            <td class="px-4 py-2 border"><?php echo htmlspecialchars($orden['code']); ?></td>
                            <td class="px-4 py-2 border"><?php echo htmlspecialchars($orden['date']); ?></td>
                            <td class="px-4 py-2 border"><?php echo htmlspecialchars($orden['employee']); ?></td>
                            <td class="px-4 py-2 border"><?php echo htmlspecialchars($orden['descripcion_articulos']); ?></td>
                            <td class="px-4 py-2 border">$<?php echo number_format($orden['subtotal_real'], 2);?></td>
                            <td class="px-4 py-2 border">$<?php echo number_format($orden['total'], 2); ?></td>
                            <td class="px-4 py-2 border">
                                <span class="badge badge-<?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars($estatus_texto); ?>
                                </span>
                            </td>
                        </tr>
                    <?php 
                        endforeach; 
                    else:
                    ?>
                        <tr>
                            <td colspan="7" class="px-4 py-4 text-center text-gray-500 border">
                                No hay órdenes para mostrar
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php endif; ?>