var titulosSecciones = {
    bitacora:          'Bitácora',
    'generar-reporte': 'Generar Reporte',
    'admin-usuarios':  'Administrar Usuarios'
};

inicializarNavegacion(titulosSecciones);

// Modal que cambia dependiendo de si se está agregando un usuario nuevo o editando a uno existente 
function openModal(action, id, nombre, app, apm, username, idRol) {
    nombre = nombre || '';
    app    = app    || '';
    apm    = apm    || '';

    var title      = document.getElementById('modal-title');
    var inputId    = document.getElementById('user-id');
    var inputNota  = document.getElementById('password-nota');
    var inputPass  = document.getElementById('user-password');
    var inputPass2 = document.getElementById('user-password2');
    var accion     = document.querySelector('#userForm input[name="accion"]');

    // Agregar un nuevo usuario
    if (action === 'add') {
        title.textContent      = 'Agregar Usuario';
        accion.value           = 'agregar';
        inputId.value          = '';
        inputNota.style.display = 'none';
        inputPass.required     = true;
        inputPass2.required    = true;
        inputPass.minLength    = 8;
        inputPass2.minLength   = 8;

        document.getElementById('user-nombre').value    = '';
        document.getElementById('user-app').value       = '';
        document.getElementById('user-apm').value       = '';
        document.getElementById('user-name').value      = '';
        inputPass.value                                  = '';
        inputPass2.value                                 = '';
        document.getElementById('user-role').value      = '';

    // Editar un usuario
    } else if (action === 'edit') {
        title.textContent      = 'Editar Usuario';
        accion.value           = 'editar';
        inputId.value          = id;
        inputNota.style.display = 'inline';
        inputPass.required     = false;
        inputPass2.required    = false;
        inputPass.minLength    = 0;
        inputPass2.minLength   = 0;

        document.getElementById('user-nombre').value    = nombre;
        document.getElementById('user-app').value       = app;
        document.getElementById('user-apm').value       = apm;
        document.getElementById('user-name').value      = username;
        inputPass.value                                  = '';
        inputPass2.value                                 = '';
        document.getElementById('user-role').value      = idRol;
    }

    document.getElementById('userModal').classList.add('abierto');
}

function closeModal() {
    document.getElementById('userModal').classList.remove('abierto');
}

document.getElementById('userModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Borrar un usuario
function deleteUser(id, nombre) {
    if (confirm('¿Eliminar a ' + nombre + '? Esta acción no se puede deshacer.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'php/controlador_usuario.php';

        var campoAccion = document.createElement('input');
        campoAccion.type  = 'hidden';
        campoAccion.name  = 'accion';
        campoAccion.value = 'eliminar';

        var campoId = document.createElement('input');
        campoId.type  = 'hidden';
        campoId.name  = 'id_us';
        campoId.value = id;

        form.appendChild(campoAccion);
        form.appendChild(campoId);
        document.body.appendChild(form);
        form.submit();
    }
}

// Filtrado de tabla de usuarios para la barra de búsqueda, después se pasará a comun.js
(function () {
    var inputBuscar = document.getElementById("buscar-usuario");
    var selectRol   = document.getElementById("filtro-rol");

    if (!inputBuscar || !selectRol) return;

    function filtrar() {
        var texto = inputBuscar.value.toLowerCase().trim();
        var rol   = selectRol.value.toLowerCase();

        document.querySelectorAll("#tabla-usuarios tr").forEach(function (fila) {
            if (fila.querySelector("td[colspan]")) {
                fila.style.display = "";
                return;
            }

            var nombre   = (fila.cells[0]?.textContent || "").toLowerCase();
            var username = (fila.cells[1]?.textContent || "").toLowerCase();
            var rolFila  = (fila.cells[2]?.textContent || "").toLowerCase();

            var coincideTexto = nombre.includes(texto) || username.includes(texto);
            var coincideRol   = rol === "" || rolFila.includes(rol);

            fila.style.display = coincideTexto && coincideRol ? "" : "none";
        });
    }
    inputBuscar.addEventListener("input", filtrar);
    selectRol.addEventListener("change", filtrar);
})();