<?php
session_start();

// Verificar que el usuario esté autenticado y sea administrador
if (!isset($_SESSION['username']) || $_SESSION['rol'] !== 'Administrador') {
    header('Location: views/login.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dar de Baja Usuario - Ayuntamiento</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .user-item {
            transition: all 0.3s ease;
        }
        .user-item:hover {
            background-color: #f3f4f6;
            transform: translateX(5px);
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <div class="form-card p-8">
            <div class="text-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">🚫 Dar de Baja Usuario</h1>
                <p class="text-gray-600">Seleccione el usuario que desea dar de baja del sistema</p>
            </div>

            <!-- Formulario de búsqueda -->
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2">Buscar Usuario</label>
                <input type="text" id="search-input" placeholder="Buscar por nombre o usuario..." 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Lista de usuarios -->
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-3">Usuarios Activos</label>
                <div id="users-list" class="border border-gray-300 rounded-lg max-h-96 overflow-y-auto">
                    <div class="text-center py-8 text-gray-500">
                        <p>Cargando usuarios...</p>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="flex space-x-4">
                <button onclick="window.location.href='index.php'" 
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-300">
                    ← Volver al Dashboard
                </button>
                <button id="deactivate-btn" disabled
                        class="flex-1 bg-red-500 hover:bg-red-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                    Dar de Baja Usuario
                </button>
            </div>
        </div>
    </div>

    <script>
        let selectedUserId = null;
        let allUsers = [];

        // Cargar usuarios al iniciar
        document.addEventListener('DOMContentLoaded', loadUsers);

        // Búsqueda en tiempo real
        document.getElementById('search-input').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            filterUsers(searchTerm);
        });

        // Función para cargar usuarios
        async function loadUsers() {
            try {
                const response = await fetch('api/get_users.php');
                const data = await response.json();
                
                if (data.success) {
                    allUsers = data.users;
                    displayUsers(allUsers);
                } else {
                    showError('Error al cargar usuarios: ' + data.error);
                }
            } catch (error) {
                showError('Error de conexión: ' + error.message);
            }
        }

        // Función para mostrar usuarios
        function displayUsers(users) {
            const usersList = document.getElementById('users-list');
            
            if (users.length === 0) {
                usersList.innerHTML = '<div class="text-center py-8 text-gray-500"><p>No se encontraron usuarios</p></div>';
                return;
            }

            usersList.innerHTML = users.map(user => `
                <div class="user-item p-4 border-b border-gray-200 cursor-pointer" onclick="selectUser(${user.id}, '${user.username}', '${user.nombre_completo}')">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="font-semibold text-gray-800">${user.nombre_completo}</p>
                            <p class="text-sm text-gray-600">Usuario: ${user.username}</p>
                            <p class="text-xs text-gray-500">Rol: ${user.rol}</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold ${getRoleBadgeClass(user.rol)}">
                                ${user.rol}
                            </span>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Función para filtrar usuarios
        function filterUsers(searchTerm) {
            const filtered = allUsers.filter(user => 
                user.nombre_completo.toLowerCase().includes(searchTerm) ||
                user.username.toLowerCase().includes(searchTerm) ||
                user.rol.toLowerCase().includes(searchTerm)
            );
            displayUsers(filtered);
        }

        // Función para seleccionar usuario
        function selectUser(userId, username, nombreCompleto) {
            selectedUserId = userId;
            
            // Remover selección previa
            document.querySelectorAll('.user-item').forEach(item => {
                item.classList.remove('bg-red-100', 'border-l-4', 'border-red-500');
            });
            
            // Marcar usuario seleccionado
            event.currentTarget.classList.add('bg-red-100', 'border-l-4', 'border-red-500');
            
            // Habilitar botón
            document.getElementById('deactivate-btn').disabled = false;
        }

        // Función para obtener clase de badge según rol
        function getRoleBadgeClass(rol) {
            switch(rol.toLowerCase()) {
                case 'administrador':
                    return 'bg-purple-200 text-purple-800';
                case 'presidente':
                    return 'bg-blue-200 text-blue-800';
                case 'empleado':
                    return 'bg-green-200 text-green-800';
                default:
                    return 'bg-gray-200 text-gray-800';
            }
        }

        // Función para dar de baja usuario
        document.getElementById('deactivate-btn').addEventListener('click', async function() {
            if (!selectedUserId) {
                alert('Por favor seleccione un usuario');
                return;
            }

            const confirmed = confirm('¿Está seguro que desea dar de baja a este usuario? Esta acción eliminará permanentemente el usuario del sistema.');
            
            if (!confirmed) return;

            try {
                const response = await fetch('api/deactivate_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ user_id: selectedUserId })
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ Usuario dado de baja exitosamente');
                    window.location.href = 'dashboard.php';
                } else {
                    alert('❌ Error: ' + data.error);
                }
            } catch (error) {
                alert('❌ Error de conexión: ' + error.message);
            }
        });

        // Función para mostrar errores
        function showError(message) {
            document.getElementById('users-list').innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <p>${message}</p>
                </div>
            `;
        }
    </script>
</body>
</html>
