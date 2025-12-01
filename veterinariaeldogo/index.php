<?php
// index.php
// ¡Che, estas tres líneas obligan a PHP a mostrar los errores si los hay!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 

// Aseguramos que el archivo de configuración exista para acceder a las constantes
require_once 'config.php';

// ======================= SESIÓN Y AUTENTICACIÓN =======================
session_start();

// Manejo de la acción de LOGIN
if (isset($_POST['action']) && $_POST['action'] == 'login') {
    $user = $_POST['usuario'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    // Validamos el usuario y contraseña (usando las constantes de config.php)
    if ($user === USER_LOGIN && $pass === PASS_LOGIN) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $user;
    } else {
        $_SESSION['login_error'] = 'Usuario o contraseña incorrectos';
    }
    // Redirección para evitar reenvío de formulario
    header("Location: index.php");
    exit();
}

// Manejo de la acción de LOGOUT
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Limpiamos el error de sesión para mostrarlo una sola vez
$login_error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

// #######################################################################
// # HTML/VISTA
// #######################################################################
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinaria "El Dogo" | Gestión CRUD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
    <script>
        // Configuración de Tailwind para usar el color principal
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#38aefdff', // Azul Principal
                        'secondary': '#053f96ff', // Azul Secundario
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100">

    <div id="mensaje-alerta" class="fixed top-5 right-5 z-50 transition-transform duration-300 transform translate-x-full">
    </div>

<?php
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // =================================================================================
    // USUARIO AUTENTICADO: MOSTRAMOS LA APLICACIÓN COMPLETA
    // =================================================================================
?>
    <header class="header-gradient text-white shadow-lg p-6">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-3xl font-extrabold tracking-tight">
                <span class="bg-white text-blue-800 p-1 rounded-md shadow-md">El Dogo</span> Veterinaria
            </h1>
            <nav class="flex items-center space-x-6">
                <span class="font-medium text-white text-lg">Hola, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="#clientes-section" class="mx-3 hover:underline font-medium">Clientes</a>
                <a href="#mascotas-section" class="mx-3 hover:underline font-medium">Mascotas</a>
                <a href="?action=logout" class="bg-red-500 text-white font-bold py-2 px-4 rounded-lg shadow-md hover:bg-red-600 transition duration-150">
                    Cerrar Sesión
                </a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto p-6 space-y-12">
        <section id="clientes-section" class="bg-white p-8 rounded-xl shadow-2xl">
            <div class="flex justify-between items-center mb-6 border-b pb-4">
                <h2 class="text-3xl font-bold text-gray-700">Gestión de Clientes</h2>
                <button onclick="abrirModalCliente('crear')" class="bg-primary text-white font-bold py-2 px-6 rounded-lg shadow-md hover:bg-secondary transition duration-150 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    Nuevo Cliente
                </button>
            </div>
            <div class="table-container">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                    <thead class="bg-gray-100 sticky top-0">
                        <tr>
                            <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600 border-b">ID</th>
                            <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600 border-b">Nombre</th>
                            <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600 border-b">Dirección</th>
                            <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600 border-b">Teléfono</th>
                            <th class="py-3 px-4 text-center text-sm font-semibold text-gray-600 border-b">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="clientes-list">
                        </tbody>
                </table>
            </div>
            <p id="clientes-empty-msg" class="text-center text-gray-500 mt-4 hidden"> Aun no hay clientes cargados </p>
        </section>

        <section id="mascotas-section" class="bg-white p-8 rounded-xl shadow-2xl">
             <div class="flex justify-between items-center mb-6 border-b pb-4">
                <h2 class="text-3xl font-bold text-gray-700">Gestión de Mascotas</h2>
                <button onclick="abrirModalMascota('crear')" class="bg-primary text-white font-bold py-2 px-6 rounded-lg shadow-md hover:bg-secondary transition duration-150 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10m-8 4h16a2 2 0 002-2V5a2 2 0 00-2-2H4a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    Nueva Mascota
                </button>
            </div>
            <div class="table-container">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                    <thead class="bg-gray-100 sticky top-0">
                        <tr>
                            <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600 border-b">ID</th>
                            <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600 border-b">Nombre</th>
                            <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600 border-b">Especie</th>
                            <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600 border-b">Raza</th>
                            <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600 border-b">Problemas/Salud</th>
                            <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600 border-b">Dueño</th>
                            <th class="py-3 px-4 text-center text-sm font-semibold text-gray-600 border-b">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="mascotas-list">
                        </tbody>
                </table>
            </div>
            <p id="mascotas-empty-msg" class="text-center text-gray-500 mt-4 hidden"> Aun no hay mascotas cargadas </p>
        </section>

        <div id="modal-cliente" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-40">
            <div class="bg-white rounded-xl shadow-3xl p-8 w-full max-w-lg transform transition-all">
                <h3 id="modal-cliente-title" class="text-2xl font-bold mb-6 border-b pb-2 text-gray-800"></h3>
                <form id="cliente-form" onsubmit="handleClienteSubmit(event)">
                    <input type="hidden" id="cliente-id">
                    <input type="hidden" id="cliente-opcion">
                    <div class="space-y-4">
                        <input type="text" id="cliente-nombre" placeholder="Nombre completo" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        <input type="text" id="cliente-direccion" placeholder="Dirección" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        <input type="tel" id="cliente-telefono" placeholder="Teléfono" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="cerrarModalCliente()" class="bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg hover:bg-gray-400 transition duration-150">Cancelar</button>
                        <button type="submit" id="cliente-submit-btn" class="bg-primary text-white font-bold py-2 px-4 rounded-lg hover:bg-secondary transition duration-150"></button>
                    </div>
                </form>
            </div>
        </div>
        
        <div id="modal-mascota" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-40">
            <div class="bg-white rounded-xl shadow-3xl p-8 w-full max-w-lg transform transition-all">
                <h3 id="modal-mascota-title" class="text-2xl font-bold mb-6 border-b pb-2 text-gray-800"></h3>
                <form id="mascota-form" onsubmit="handleMascotaSubmit(event)">
                    <input type="hidden" id="mascota-id">
                    <input type="hidden" id="mascota-opcion">
                    <div class="space-y-4">
                        <input type="text" id="mascota-nombre" placeholder="Nombre de la mascota" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        <input type="text" id="mascota-especie" placeholder="Especie (ej: Perro, Gato)" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        <input type="text" id="mascota-raza" placeholder="Raza" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        <input type="number" id="mascota-edad" placeholder="Edad (años)" required min="0" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        <textarea id="mascota-problemas" placeholder="Problemas de salud o historial médico relevante" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"></textarea>
                        <select id="mascota-salud" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            <option value="">Estado de Salud</option>
                            <option value="Excelente">Excelente</option>
                            <option value="Bueno">Bueno</option>
                            <option value="Estable">Estable</option>
                            <option value="Crítico">Crítico</option>
                        </select>
                        <select id="mascota-clienteId" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            <option value="">Seleccionar Dueño</option>
                            </select>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="cerrarModalMascota()" class="bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg hover:bg-gray-400 transition duration-150">Cancelar</button>
                        <button type="submit" id="mascota-submit-btn" class="bg-primary text-white font-bold py-2 px-4 rounded-lg hover:bg-secondary transition duration-150"></button>
                    </div>
                </form>
            </div>
        </div>

        <div id="modal-cambio-dueno" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-40">
            <div class="bg-white rounded-xl shadow-3xl p-8 w-full max-w-sm transform transition-all">
                <h3 class="text-xl font-bold mb-4 text-gray-800">Cambiar Dueño de Mascota</h3>
                <p id="cambio-dueno-mascota-info" class="mb-4 text-gray-600"></p>
                <form id="cambio-dueno-form">
                    <input type="hidden" id="cambio-dueno-mascota-id">
                    <div class="space-y-4">
                        <label for="cambio-dueno-cliente-id" class="block text-sm font-medium text-gray-700">Seleccioná el Nuevo Dueño:</label>
                        <select id="cambio-dueno-cliente-id" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            <option value="">Seleccionar Cliente</option>
                            </select>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="cerrarModalCambioDueno()" class="bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-lg hover:bg-gray-400 transition duration-150">Cancelar</button>
                        <button type="submit" class="bg-green-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-green-700 transition duration-150">Confirmar Cambio</button>
                    </div>
                </form>
            </div>
        </div>


    </main>

    <script src="app.js"></script>

<?php
} else {
    // =================================================================================
    // USUARIO NO AUTENTICADO: MOSTRAMOS EL FORMULARIO DE LOGIN
    // =================================================================================
?>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md">
            <div class="text-center mb-6">
                <h2 class="text-3xl font-bold text-gray-800 mb-1">Veterinaria "El Dogo"</h2>
                <p class="text-gray-500">Ingrese sus credenciales</p>
            </div>
            
            <?php if ($login_error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error de Login</strong>
                    <span class="block sm:inline"><?php echo htmlspecialchars($login_error); ?></span>
                </div>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <input type="hidden" name="action" value="login">
                <div class="space-y-4">
                    <input type="text" name="usuario" placeholder="Usuario (Ej: admin)" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    <input type="password" name="password" placeholder="Contraseña (Ej: 1234)" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                </div>
                <div class="mt-6">
                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg shadow-md hover:bg-blue-700 transition duration-150">
                        Entrar al Sistema
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php
}
?>

</body>
</html>