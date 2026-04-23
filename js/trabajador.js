// Secciones de la página
var titulosPagina = {
    solicitudes:    'Solicitudes',
    'mis-asignaciones': 'Mis Asignaciones',
    reporte:        'Reporte de Solicitud',
    notificaciones: 'Notificaciones'
};

var acceptedRequests = {};

inicializarNavegacion(titulosPagina);

// Funciones para mostrar los distintos estados de las solicitudes en la interfaz de los trabajadores
function aceptarSolicitud(button, id) {
    document.getElementById('modal-id-sol').value = id;
    document.getElementById('modalPrioridad').classList.add('abierto');
}

function cerrarModalPrioridad() {
    document.getElementById('modalPrioridad').classList.remove('abierto');
    document.getElementById('modal-prioridad').value = '';
}

document.getElementById('modalPrioridad').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalPrioridad();
});

function rechazarSolicitud(button) {
    var item = button.closest('.solicitud-item');
    item.classList.add('rejected');
    item.classList.remove('accepted');
    item.querySelector('.status').innerHTML = '<strong>Estado:</strong> Rechazada';
    item.querySelector('.buttons').style.display = 'none';
    item.querySelector('.cancel-btn').style.display = 'flex';
    item.querySelector('.create-report').style.display = 'none';
}

function cancelarSolicitud(button, id) {
    var item = button.closest('.solicitud-item');
    item.classList.remove('accepted', 'rejected');
    item.querySelector('.status').innerHTML = '<strong>Estado:</strong> Pendiente';
    item.querySelector('.buttons').style.display = 'flex';
    item.querySelector('.cancel-btn').style.display = 'none';
    item.querySelector('.create-report').style.display = '';

    delete acceptedRequests[id];
}

// Función para el botón de Crear Reporte, temporal
function crearReporte(id) {
    if (acceptedRequests[id]) {
        var datos = acceptedRequests[id];

        navegarSeccion('reporte', titulosPagina);

        document.getElementById('report-title').textContent = datos.title;
        document.getElementById('titulo-reporte').value = 'Reporte de ' + datos.title;
        document.getElementById('descripcion-reporte').value = 'Problema relacionado con ' + datos.title + ' en el área ' + datos.area + ' para el usuario ' + datos.user + '.';
    } else {
        alert('Esta solicitud no está aceptada.');
    }
}