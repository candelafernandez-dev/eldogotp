/* app.js */
/* Che, este es el músculo del frontend, toda la lógica del lado del cliente. */

let clientes = []; // Caché global para la lista de clientes
let mascotas = []; // Caché global para la lista de mascotas

// ======================== UTILIDADES Y API FETCH ========================

/**
 * Muestra un mensaje flotante de alerta o error.
 * @param {string} mensaje El texto del mensaje.
 * @param {string} tipo 'success' (default) o 'error'.
 */
function mostrarMensaje(mensaje, tipo = 'success') {
    const alerta = document.getElementById('mensaje-alerta');
    if (!alerta) return;

    alerta.innerHTML = `
        <div class="p-4 rounded-lg shadow-lg text-white font-semibold flex items-center ${tipo === 'error' ? 'bg-red-500' : 'bg-green-500'}">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                ${tipo === 'error' 
                    ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>' 
                    : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
                }
            </svg>
            <span>${mensaje}</span>
        </div>
    `;
    // Muestra y luego oculta
    alerta.classList.remove('translate-x-full');
    setTimeout(() => {
        alerta.classList.add('translate-x-full');
    }, 4000);
}

/**
 * Función genérica para hacer peticiones a la API REST de PHP (api.php).
 */
async function apiFetch(entity, method, data = null) {
    const url = `api.php?entidad=${entity}`; 
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
    };

    if (data) {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, options);

        if (response.status === 401) {
            mostrarMensaje('La sesión ha caducado', 'error');
            setTimeout(() => window.location.reload(), 2000);
            return;
        }

        const jsonResponse = await response.json();

        if (!response.ok) {
            const errorMessage = jsonResponse.error || `Error ${response.status}: Algo falló en el servidor`;
            mostrarMensaje(errorMessage, 'error');
            throw new Error(errorMessage);
        }
        
        // Si la respuesta incluye un mensaje de backend, lo mostramos
        if (jsonResponse.mensaje) {
            mostrarMensaje(jsonResponse.mensaje, 'success');
        }

        return jsonResponse;

    } catch (error) {
        console.error('Error de API:', error);
        mostrarMensaje('Error de conexión o en la API. Revise la consola', 'error');
        throw error;
    }
}


// ======================== CLIENTES (MODALES Y CRUD) ========================

function cerrarModalCliente() {
    document.getElementById('modal-cliente').classList.add('hidden');
    document.getElementById('modal-cliente').classList.remove('flex');
    document.getElementById('cliente-form').reset();
}

/**
 * Abre el modal de cliente en modo 'crear' o 'editar'.
 * @param {string} opcion 'crear' o 'editar'.
 * @param {object} clienteData Datos del cliente a editar (solo en modo 'editar').
 */
function abrirModalCliente(opcion, clienteData = null) {
    const modal = document.getElementById('modal-cliente');
    const title = document.getElementById('modal-cliente-title');
    const submitBtn = document.getElementById('cliente-submit-btn');
    const idInput = document.getElementById('cliente-id');
    const opcionInput = document.getElementById('cliente-opcion');
    
    opcionInput.value = opcion;

    if (opcion === 'crear') {
        title.textContent = 'Nuevo Cliente';
        submitBtn.textContent = 'Guardar Cliente';
        idInput.value = '';
        document.getElementById('cliente-form').reset();
    } else { // editar
        title.textContent = `Editar Cliente ID: ${clienteData.id}`;
        submitBtn.textContent = 'Actualizar Cliente';
        idInput.value = clienteData.id;
        document.getElementById('cliente-nombre').value = clienteData.nombre;
        document.getElementById('cliente-direccion').value = clienteData.direccion;
        document.getElementById('cliente-telefono').value = clienteData.telefono;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

/**
 * Maneja el envío del formulario de cliente (Crear o Editar).
 */
async function handleClienteSubmit(event) {
    event.preventDefault();
    const opcion = document.getElementById('cliente-opcion').value;
    const data = {
        nombre: document.getElementById('cliente-nombre').value,
        direccion: document.getElementById('cliente-direccion').value,
        telefono: document.getElementById('cliente-telefono').value,
    };
    
    try {
        if (opcion === 'crear') {
            await apiFetch('clientes', 'POST', data);
        } else { // 'editar'
            data.id = parseInt(document.getElementById('cliente-id').value);
            await apiFetch('clientes', 'PUT', data);
        }
        
        cerrarModalCliente();
        await cargarClientes(true); // Recargamos y refrescamos los selects
    } catch (e) {
        console.error("Error al procesar cliente:", e);
    }
}

/**
 * Realiza la baja lógica de un cliente (desactiva su campo 'activo' y sus mascotas asociadas).
 */
async function bajaLogicaCliente(clienteId) {
    if (!confirm(`¿Está seguro que quiere darle de baja al Cliente ID ${clienteId}? Esto también desactiva sus mascotas asociadas`)) {
        return;
    }
    try {
        const data = { id: clienteId, baja: true };
        await apiFetch('clientes', 'PUT', data);
        await cargarClientes(true); // Recargar clientes
        await cargarMascotas(false); // Recargar mascotas por si cambiaron su estado
    } catch (e) {
        console.error("Error al dar de baja el cliente:", e);
    }
}

/**
 * Carga la lista de clientes desde la API y renderiza la tabla.
 * @param {boolean} updateSelects Indica si también debe actualizar los <select> de mascotas.
 */
async function cargarClientes(updateSelects = false) {
    try {
        const data = await apiFetch('clientes', 'GET');
        clientes = data || [];
        const lista = document.getElementById('clientes-list');
        lista.innerHTML = '';
        
        if (clientes.length === 0) {
            document.getElementById('clientes-empty-msg').classList.remove('hidden');
        } else {
            document.getElementById('clientes-empty-msg').classList.add('hidden');
        }

        clientes.forEach(cliente => {
            const row = lista.insertRow();
            row.className = 'border-b hover:bg-gray-50 transition duration-100';
            row.innerHTML = `
                <td class="py-3 px-4 text-sm text-gray-700">${cliente.id}</td>
                <td class="py-3 px-4 text-sm font-medium text-gray-900">${cliente.nombre}</td>
                <td class="py-3 px-4 text-sm text-gray-700">${cliente.direccion}</td>
                <td class="py-3 px-4 text-sm text-gray-700">${cliente.telefono}</td>
                <td class="py-3 px-4 text-center space-x-2">
                    <button onclick="abrirModalCliente('editar', ${JSON.stringify(cliente).replace(/"/g, '&quot;')})" 
                            class="text-blue-600 hover:text-blue-800 font-bold text-sm">Editar</button>
                    <button onclick="bajaLogicaCliente(${cliente.id})" 
                            class="text-red-600 hover:text-red-800 font-bold text-sm ml-2">Baja</button>
                </td>
            `;
        });
        
        if (updateSelects) {
            cargarClientesParaSelects();
        }

    } catch (e) {
        console.error("Fallo al cargar clientes:", e);
    }
}


// ======================== MASCOTAS (MODALES Y CRUD) ========================

function cerrarModalMascota() {
    document.getElementById('modal-mascota').classList.add('hidden');
    document.getElementById('modal-mascota').classList.remove('flex');
    document.getElementById('mascota-form').reset();
}

function cargarClientesParaSelects() {
    const selects = [
        document.getElementById('mascota-clienteId'),
        document.getElementById('cambio-dueno-cliente-id')
    ];
    
    // Limpiamos los selects (dejando la primera opción "Seleccionar")
    selects.forEach(select => {
        if (select) {
            select.innerHTML = '<option value="">Seleccionar Dueño</option>';
            clientes.forEach(cliente => {
                const option = document.createElement('option');
                option.value = cliente.id;
                option.textContent = `${cliente.nombre} (ID: ${cliente.id})`;
                select.appendChild(option);
            });
        }
    });
}

/**
 * Abre el modal de mascota en modo 'crear' o 'editar'.
 */
function abrirModalMascota(opcion, mascotaData = null) {
    cargarClientesParaSelects(); // Aseguramos que los selects de dueño estén actualizados

    const modal = document.getElementById('modal-mascota');
    const title = document.getElementById('modal-mascota-title');
    const submitBtn = document.getElementById('mascota-submit-btn');
    const idInput = document.getElementById('mascota-id');
    const opcionInput = document.getElementById('mascota-opcion');
    
    opcionInput.value = opcion;

    if (opcion === 'crear') {
        title.textContent = 'Nueva Mascota';
        submitBtn.textContent = 'Guardar Mascota';
        idInput.value = '';
        document.getElementById('mascota-form').reset();
    } else { // editar
        title.textContent = `Editar Mascota ID: ${mascotaData.id}`;
        submitBtn.textContent = 'Actualizar Mascota';
        idInput.value = mascotaData.id;
        document.getElementById('mascota-nombre').value = mascotaData.nombre;
        document.getElementById('mascota-especie').value = mascotaData.especie;
        document.getElementById('mascota-raza').value = mascotaData.raza;
        document.getElementById('mascota-edad').value = mascotaData.edad;
        document.getElementById('mascota-problemas').value = mascotaData.problemas;
        document.getElementById('mascota-salud').value = mascotaData.salud;
        document.getElementById('mascota-clienteId').value = mascotaData.clienteId;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

/**
 * Maneja el envío del formulario de mascota (Crear o Editar).
 */
async function handleMascotaSubmit(event) {
    event.preventDefault();
    const opcion = document.getElementById('mascota-opcion').value;
    const data = {
        nombre: document.getElementById('mascota-nombre').value,
        especie: document.getElementById('mascota-especie').value,
        raza: document.getElementById('mascota-raza').value,
        edad: parseInt(document.getElementById('mascota-edad').value),
        problemas: document.getElementById('mascota-problemas').value,
        salud: document.getElementById('mascota-salud').value,
        clienteId: parseInt(document.getElementById('mascota-clienteId').value),
    };

    try {
        if (opcion === 'crear') {
            await apiFetch('mascotas', 'POST', data);
        } else { // 'editar'
            data.id = parseInt(document.getElementById('mascota-id').value);
            await apiFetch('mascotas', 'PUT', data);
        }
        
        cerrarModalMascota();
        await cargarMascotas(false);
    } catch (e) {
        console.error("Error al procesar mascota:", e);
    }
}

/**
 * Realiza la baja lógica de una mascota.
 */
async function bajaLogicaMascota(mascotaId) {
    if (!confirm(`¿Seguro que quiere dar de baja a la Mascota ID ${mascotaId}?`)) {
        return;
    }
    try {
        const data = { id: mascotaId, baja: true };
        await apiFetch('mascotas', 'PUT', data);
        await cargarMascotas(false); // Recargar mascotas
    } catch (e) {
        console.error("Error al dar de baja la mascota:", e);
    }
}


/**
 * Carga la lista de mascotas desde la API y renderiza la tabla.
 * @param {boolean} reloadClientes Indica si también debe recargar la lista de clientes.
 */
async function cargarMascotas(reloadClientes = true) {
    try {
        // Primero aseguramos la lista de clientes si es necesario
        if (reloadClientes) {
             // Ojo: cargamos clientes sin forzar la actualización de selects aún
            await cargarClientes(false); 
        }

        const data = await apiFetch('mascotas', 'GET');
        mascotas = data || [];
        const lista = document.getElementById('mascotas-list');
        lista.innerHTML = '';
        
        if (mascotas.length === 0) {
            document.getElementById('mascotas-empty-msg').classList.remove('hidden');
        } else {
            document.getElementById('mascotas-empty-msg').classList.add('hidden');
        }

        mascotas.forEach(mascota => {
            const row = lista.insertRow();
            row.className = 'border-b hover:bg-gray-50 transition duration-100';
            
            // Buscamos el nombre del cliente
            const dueno = clientes.find(c => c.id === mascota.clienteId);
            const duenoNombre = dueno ? dueno.nombre : '<span class="text-red-500 font-bold">Sin Dueño (Dado de baja)</span>';

            row.innerHTML = `
                <td class="py-3 px-4 text-sm text-gray-700">${mascota.id}</td>
                <td class="py-3 px-4 text-sm font-medium text-gray-900">${mascota.nombre}</td>
                <td class="py-3 px-4 text-sm text-gray-700">${mascota.especie}</td>
                <td class="py-3 px-4 text-sm text-gray-700">${mascota.raza}</td>
                <td class="py-3 px-4 text-sm text-gray-700">${mascota.problemas || mascota.salud}</td>
                <td class="py-3 px-4 text-sm text-gray-700">${duenoNombre}</td>
                <td class="py-3 px-4 text-center space-x-2">
                    <button onclick="abrirModalMascota('editar', ${JSON.stringify(mascota).replace(/"/g, '&quot;')})" 
                            class="text-blue-600 hover:text-blue-800 font-bold text-sm">Editar</button>
                    <button onclick="abrirModalCambioDueno(${mascota.id}, '${mascota.nombre}')" 
                            class="text-green-600 hover:text-green-800 font-bold text-sm ml-2">Cambiar Dueño</button>
                    <button onclick="bajaLogicaMascota(${mascota.id})" 
                            class="text-red-600 hover:text-red-800 font-bold text-sm ml-2">Baja</button>
                </td>
            `;
        });
        
        // Al finalizar la carga, actualizamos los selects de clientes/dueños
        cargarClientesParaSelects();

    } catch (e) {
        console.error("Fallo al cargar mascotas:", e);
    }
}


// ======================== CAMBIO DE DUEÑO ========================

function cerrarModalCambioDueno() {
    document.getElementById('modal-cambio-dueno').classList.add('hidden');
    document.getElementById('modal-cambio-dueno').classList.remove('flex');
}

/**
 * Abre el modal de cambio de dueño.
 */
function abrirModalCambioDueno(mascotaId, mascotaNombre) {
    cargarClientesParaSelects(); // Aseguramos que los selects estén llenos
    
    document.getElementById('cambio-dueno-mascota-id').value = mascotaId;
    document.getElementById('cambio-dueno-mascota-info').textContent = `Mascota: ${mascotaNombre} (ID: ${mascotaId})`;
    
    document.getElementById('modal-cambio-dueno').classList.remove('hidden');
    document.getElementById('modal-cambio-dueno').classList.add('flex');
}

// Event Listener para el formulario de cambio de dueño
document.getElementById('cambio-dueno-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const mascotaId = parseInt(document.getElementById('cambio-dueno-mascota-id').value);
    const nuevoClienteId = parseInt(document.getElementById('cambio-dueno-cliente-id').value);
    
    if (isNaN(nuevoClienteId) || nuevoClienteId === 0) {
        mostrarMensaje('Tiene que seleccionar un dueño válido', 'error');
        return;
    }

    const dataToSend = {
        id: mascotaId,
        nuevo_cliente_id: nuevoClienteId,
        cambio_dueno: true
    };

    try {
        await apiFetch('mascotas', 'PUT', dataToSend);
        await cargarMascotas(false); // Recargar solo mascotas
        cerrarModalCambioDueno();
    } catch (e) {
        console.error("Error al cambiar de dueño:", e);
    }
});


/* ======================== INICIALIZACIÓN (MAIN) ======================== */
function inicializarApp() {
    // La inicialización solo corre si el HTML de la aplicación está presente (si el usuario está logueado)
    if (document.getElementById('clientes-section')) {
        // Inicializamos cargando las mascotas, que internamente carga los clientes necesarios.
        cargarMascotas(); 
    }
}

// Empezamos el laburo cuando la página está lista
document.addEventListener('DOMContentLoaded', inicializarApp);