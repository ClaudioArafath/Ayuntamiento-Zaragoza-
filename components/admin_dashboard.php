<?php
// Dashboard para Administradores y Presidentes
if ($rol === 'Administrador' || $rol === 'Presidente'):
?>
<main class="p-4 max-w-7xl mx-auto">
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
            <h3 class="text-sm font-semibold text-purple-700">Total Condonaciones</h3>
            <p class="text-2xl font-bold" id="total-condonaciones">$<?php echo number_format($total_condonaciones, 2); ?></p>
        </div>
    </div>
    
    <!-- Grid de gráficas -->
    <div class="dashboard-grid mb-6">          
        <!-- Gráfica de ingresos -->
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

        <!-- Gráfica de departamentos -->
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

    <!-- Búsqueda -->
    <div id="caja-busqueda" class="mt-3 hidden overflow-hidden">
        <div class="flex space-x-2">
            <input type="text" id="input-busqueda" placeholder="Ingrese el folio del comprobante" 
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button id="btn-buscar" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                Buscar
            </button>
        </div>
        <div id="resultado-busqueda" class="mt-3 hidden"></div>
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
<?php endif; ?>