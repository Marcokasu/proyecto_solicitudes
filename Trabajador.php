<!-- PHP -->
<?php
//Comprobación de sesión
session_start();
if (empty($_SESSION["id"]) || !is_numeric($_SESSION["id"]) || $_SESSION["id_rol"] != 2) {
    header("Location: index.php");
    exit();
}
// Mensajes flash
$msgExito = $_SESSION["exito"] ?? null;
$msgError = $_SESSION["error"] ?? null;
unset($_SESSION["exito"], $_SESSION["error"]);

require_once "php/conexion.php";

//  Trae solicitudes si la solicitud es id_estado = 1 (pendientes).
$stmtSolicitudes = $conexion->prepare(
    "SELECT s.id_sol, s.encabezado, s.descripcion, s.prioridad, s.fecha_creacion, s.fecha_limite,
            e.nombre AS estado,
            u.nombre AS solicitante_nombre, u.app AS solicitante_app,
            a.nombre AS area
    FROM solicitud s
    JOIN estado_solicitud e ON s.id_estado = e.id_estado
    JOIN usuario u ON s.id_us = u.id_us
    JOIN area a ON u.id_area = a.id_area
    WHERE s.id_estado = 1
    ORDER BY s.fecha_creacion DESC"
);
$stmtSolicitudes->execute();
$solicitudes = $stmtSolicitudes->get_result();
$totalSolicitudes = $solicitudes->num_rows;
$stmtSolicitudes->close();

// Trae las asignaciones activas y completadas del trabajador
$stmtAsignaciones = $conexion->prepare(
    "SELECT a.id_asg, a.estado_asignacion, a.fecha_inicio, a.fecha_fin,
            s.encabezado, s.prioridad,
            ar.nombre AS area
     FROM asignacion a
     JOIN solicitud s ON a.id_sol = s.id_sol
     JOIN usuario u ON s.id_us = u.id_us
     JOIN area ar ON u.id_area = ar.id_area
     WHERE a.id_trabajador = ?
       AND a.estado_asignacion != 'cancelada'
     ORDER BY a.estado_asignacion ASC, a.fecha_inicio DESC"
);
$stmtAsignaciones->bind_param("i", $_SESSION["id"]);
$stmtAsignaciones->execute();
$asignaciones = $stmtAsignaciones->get_result();
$totalAsignaciones = $asignaciones->num_rows;
$stmtAsignaciones->close();
?>

<!-- HTML -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Solicitudes — Trabajador | ITSRV</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="layout-app">

    <aside class="sidebar">
        <div class="sidebar-marca">
            <div class="marca-fila">
                <div class="marca-emblema"><img src="img/logo_tec_.png" alt="logo del Instituto Tecnológico Superior de Rioverde" width="30"></div>
                <div>
                    <div class="marca-nombre">ITSRV</div>
                    <div class="marca-subtitulo">SOPORTEC</div>
                </div>
            </div>
            <div class="usuario-pastilla">
                <!-- Iniciales calculadas desde la sesión ej. Juan Perez= JP -->
                <div class="usuario-avatar">
                    <?php echo strtoupper(substr($_SESSION["nombre"], 0, 1) . substr($_SESSION["app"], 0, 1)); ?>
                </div>
                <div>
                    <div class="usuario-nombre">
                        <?php echo htmlspecialchars($_SESSION["nombre"] . " " . $_SESSION["app"]); ?>
                    </div>
                    <div class="usuario-rol">Trabajador</div>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-etiqueta-seccion">Principal</div>
            <a href="#" class="nav-link nav-item active" data-section="solicitudes">
                Solicitudes
                <?php if ($totalSolicitudes > 0): ?>
                    <span class="nav-contador"><?= $totalSolicitudes ?></span>
                <?php endif; ?>
            </a>
            <a href="#" class="nav-link nav-item" data-section="mis-asignaciones">
                Solicitudes Aceptadas
                <?php if ($totalAsignaciones > 0): ?>
                    <span class="nav-contador"><?= $totalAsignaciones ?></span>
                <?php endif; ?>
            </a>
            <a href="#" class="nav-link nav-item" data-section="reporte">Reporte de Solicitud</a>
            <a href="#" class="nav-link nav-item" data-section="notificaciones">Notificaciones</a>
        </nav>

        <div class="sidebar-pie">
            <a href="php/controlador_cerrar.php" class="btn-cerrar-sesion">
                <span>❌</span> Cerrar Sesión
            </a>
        </div>
    </aside>

    <div class="contenido-principal">

        <header class="topbar">
            <div>
                <div class="topbar-titulo" id="topbar-titulo">Solicitudes</div>
                <div class="topbar-subtitulo">Instituto Tecnológico Superior de Rioverde</div>
            </div>
        </header>

        <div class="cuerpo-pagina">
            <?php if ($msgExito): ?>
                <div class="alerta alerta-exito"><?= htmlspecialchars($msgExito) ?></div>
            <?php endif; ?>
            <?php if ($msgError): ?>
                <div class="alerta alerta-error"><?= htmlspecialchars($msgError) ?></div>
            <?php endif; ?>

            <!-- SOLICITUDES -->
            <div id="solicitudes" class="section active">
                <!-- Un grid con dos columnas. La lista y el Panel lateral -->
                <div class="columnas-dashboard">

                    <div class="tarjeta">
                        <div class="tarjeta-encabezado">
                            <div class="tarjeta-titulo">Solicitudes disponibles</div>
                        </div>
                        <div style="padding: 12px;">
                            <?php if ($totalSolicitudes === 0): ?>
                                <p style="color:#8f98b2; text-align:center; padding:16px;">
                                    No hay solicitudes pendientes.
                                </p>
                            <?php else: ?>
                                <?php while ($s = $solicitudes->fetch_object()): ?>
                                    <?php
                                        $prioridad   = strtolower($s->prioridad);
                                        $solicitante = htmlspecialchars($s->solicitante_nombre . " " . $s->solicitante_app);
                                        $fecha       = date("d/m/Y", strtotime($s->fecha_creacion));
                                        $fechalimite = date("d/m/Y", strtotime($s->fecha_limite));
                                    ?>
                                    <div class="tarjeta-solicitud solicitud-item" data-id="<?= $s->id_sol ?>">
                                        <div class="barra-prioridad <?= $prioridad ?>"></div>
                                        <div class="cuerpo-solicitud">
                                            <div class="solicitud-titulo-texto">
                                                <h3><?= htmlspecialchars($s->encabezado) ?></h3>
                                            </div>
                                            <div class="solicitud-meta">
                                                <span><strong>Usuario:</strong> <?= $solicitante ?></span>
                                                <span><strong>Área:</strong> <?= htmlspecialchars($s->area) ?></span>
                                                <span><strong>Fecha de publicación:</strong> <?= $fecha ?></span>
                                                <span><strong>Fecha límite:</strong> <?= $fechalimite ?></span>
                                            </div>
                                            <p style="font-size:12px; margin-top:5px; color:#4d5a7a;">
                                                <?= htmlspecialchars($s->descripcion) ?>
                                            </p>
                                            <p class="status" style="font-size:12px; margin-top:5px; color:#4d5a7a;">
                                                <strong>Estado:</strong> <?= htmlspecialchars($s->estado) ?>
                                            </p>
                                        </div>
                                        <!-- Botones principales que se ocultan al aceptar o rechazar -->
                                        <div class="solicitud-acciones buttons">
                                            <button class="btn btn-exito btn-pequeno" onclick="aceptarSolicitud(this, <?= $s->id_sol ?>)">Aceptar</button>
                                            <button class="btn btn-peligro btn-pequeno" onclick="rechazarSolicitud(this, <?= $s->id_sol ?>)">Rechazar</button>
                                            <button class="btn btn-advertencia btn-pequeno postpone">Posponer</button>
                                        </div>
                                        <!-- Botones post-decisión que están ocultos hasta que se acepte o rechace -->
                                        <div class="cancel-btn" style="display:none; gap:6px;">
                                            <!-- crearReporte() solo funciona si la solicitud fue aceptada -->
                                            <button class="btn btn-primario btn-pequeno create-report" onclick="crearReporte(<?= $s->id_sol ?>)">Crear Reporte</button>
                                            <button class="btn btn-fantasma btn-pequeno" onclick="cancelarSolicitud(this, <?= $s->id_sol ?>)">Cancelar</button>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Por parte del panel lateral se encuentran notificaciones y recientes -->
                    <div class="columna-derecha">
                        <div class="tarjeta">
                            <div class="tarjeta-encabezado">
                                <div class="tarjeta-titulo">Notificaciones</div>
                            </div>
                            <!-- .no-leida hace referencia a las notificaciones que en teoría aun no se abren/leen -->
                            <div class="item-notificacion no-leida">
                                <span class="indicador-notificacion no-leida"></span>
                                <div>
                                    <div class="notificacion-mensaje">Nueva solicitud disponible — #666</div>
                                    <div class="notificacion-hora">hace 8 minutos</div>
                                </div>
                            </div>
                            <div class="item-notificacion no-leida">
                                <span class="indicador-notificacion no-leida"></span>
                                <div>
                                    <div class="notificacion-mensaje">Nueva solicitud disponible — #667</div>
                                    <div class="notificacion-hora">hace 22 minutos</div>
                                </div>
                            </div>
                            <div class="item-notificacion">
                                <span class="indicador-notificacion leida"></span>
                                <div>
                                    <div class="notificacion-mensaje">#123 marcada como completada</div>
                                    <div class="notificacion-hora">hace 2 horas</div>
                                </div>
                            </div>
                        </div>

                        <div class="tarjeta">
                            <div class="tarjeta-encabezado">
                                <div class="tarjeta-titulo">Recientes</div>
                            </div>
                            <div class="item-historial">
                                <div>
                                    <div class="historial-titulo">Mouse sin respuesta — Dirección</div>
                                    <div class="historial-meta">Dirección General · 14 min</div>
                                </div>
                                <span class="etiqueta etiqueta-completada">Completada</span>
                            </div>
                            <div class="item-historial">
                                <div>
                                    <div class="historial-titulo">PC lenta en biblioteca</div>
                                    <div class="historial-meta">Servicios · 31 min</div>
                                </div>
                                <span class="etiqueta etiqueta-completada">Completada</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- REPORTE  -->
            <!-- El reporte se oculta al cargar pero crearReporte() lo activa y pre-rellena los campos -->
            <div id="reporte" class="section" style="display:none;">
                <div class="tarjeta" style="max-width:680px;">
                    <div class="tarjeta-encabezado">
                        <div class="tarjeta-titulo">Reporte de Solicitud</div>
                    </div>
                    <div class="tarjeta-cuerpo">
                        <div id="report-form-container">
                            <p>Selecciona una solicitud aceptada para generar el reporte.</p>
                        </div>
                        <div id="report-form">
                            <h3 style="margin-bottom:16px; font-size:14px;">Generar Reporte para: <span id="report-title"></span>
                            </h3>
                            <form action="#" method="post" enctype="multipart/form-data">
                                <!-- Se pre-rellena desde crearReporte() -->
                                <div class="grupo-form">
                                    <label class="etiqueta-form" for="titulo-reporte">Título del reporte</label>
                                    <input class="campo-form" type="text" id="titulo-reporte" name="titulo-reporte" placeholder="Ingresa el título" required>
                                </div>
                                <!-- Se pre-rellena desde crearReporte() -->
                                <div class="grupo-form">
                                    <label class="etiqueta-form" for="descripcion-reporte">Descripción (problema y solución)</label>
                                    <textarea class="campo-form" id="descripcion-reporte" name="descripcion-reporte" rows="4" placeholder="Describe el problema y la solución" required></textarea>
                                </div>
                                <!-- El atributo multiple permite que se puedan subir más de una fotografía/evidencia -->
                                <div class="grupo-form">
                                    <label class="etiqueta-form" for="fotos">Subir fotografías (evidencia)</label>
                                    <input class="campo-form" type="file" id="fotos" name="fotos" multiple accept="image/*">
                                </div>
                                <button type="submit" class="btn btn-primario">Guardar Reporte</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- NOTIFICACIONES -->
            <div id="notificaciones" class="section" style="display:none;">
                <div class="tarjeta" style="max-width:600px;">
                    <div class="tarjeta-encabezado">
                        <div class="tarjeta-titulo">Notificaciones</div>
                    </div>
                    <div class="tarjeta-cuerpo">
                        <p>Aquí aparecerán las notificaciones</p>
                    </div>
                </div>
            </div>
            
            <!-- MIS ASIGNACIONES -->
            <div id="mis-asignaciones" class="section" style="display:none;">
                <div class="tarjeta">
                    <div class="tarjeta-encabezado">
                        <div class="tarjeta-titulo">Mis Asignaciones</div>
                    </div>

                    <?php if ($totalAsignaciones === 0): ?>
                        <div class="tarjeta-cuerpo">
                            <p style="color:#8f98b2; text-align:center;">No tienes asignaciones activas.</p>
                        </div>
                    <?php else: ?>

                        <!-- ACTIVAS -->
                        <?php
                            $hayActivas = false;
                            $hayCompletadas = false;
                            // Separar en dos arrays para renderizar en bloques distintos
                            $listaActivas = [];
                            $listaCompletadas = [];
                            while ($a = $asignaciones->fetch_object()) {
                                if ($a->estado_asignacion === 'activa') $listaActivas[] = $a;
                                else $listaCompletadas[] = $a;
                            }
                        ?>

                        <?php if (!empty($listaActivas)): ?>
                            <div style="padding: 12px 16px 4px;">
                                <p style="font-size:11px; font-weight:700; color:#8f98b2; text-transform:uppercase; letter-spacing:1px;">
                                    Activas
                                </p>
                            </div>
                            <div class="contenedor-tabla">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Título</th>
                                            <th>Área</th>
                                            <th>Prioridad</th>
                                            <th>Fecha Inicio</th>
                                            <th>Fecha Fin</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($listaActivas as $a): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($a->encabezado) ?></strong></td>
                                                <td class="texto-apagado"><?= htmlspecialchars($a->area) ?></td>
                                                <td>
                                                    <?php
                                                        $clasePrioridad = match(strtolower($a->prioridad)) {
                                                            'alta'  => 'etiqueta-alta',
                                                            'media' => 'etiqueta-media',
                                                            'baja'  => 'etiqueta-baja',
                                                            default => ''
                                                        };
                                                    ?>
                                                    <span class="etiqueta <?= $clasePrioridad ?>"><?= htmlspecialchars($a->prioridad) ?></span>
                                                </td>
                                                <td class="texto-apagado"><?= date("d/m/Y", strtotime($a->fecha_inicio)) ?></td>
                                                <td class="texto-apagado">
                                                    <?= $a->fecha_fin ? date("d/m/Y", strtotime($a->fecha_fin)) : '—' ?>
                                                </td>
                                                <td><span class="etiqueta etiqueta-proceso">Activa</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <!-- COMPLETADAS -->
                        <?php if (!empty($listaCompletadas)): ?>
                            <div style="padding: 12px 16px 4px; margin-top: 8px;">
                                <p style="font-size:11px; font-weight:700; color:#8f98b2; text-transform:uppercase; letter-spacing:1px;">
                                    Completadas
                                </p>
                            </div>
                            <div class="contenedor-tabla">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Título</th>
                                            <th>Área</th>
                                            <th>Prioridad</th>
                                            <th>Fecha Inicio</th>
                                            <th>Fecha Fin</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($listaCompletadas as $a): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($a->encabezado) ?></strong></td>
                                                <td class="texto-apagado"><?= htmlspecialchars($a->area) ?></td>
                                                <td>
                                                    <?php
                                                        $clasePrioridad = match(strtolower($a->prioridad)) {
                                                            'alta'  => 'etiqueta-alta',
                                                            'media' => 'etiqueta-media',
                                                            'baja'  => 'etiqueta-baja',
                                                            default => ''
                                                        };
                                                    ?>
                                                    <span class="etiqueta <?= $clasePrioridad ?>"><?= htmlspecialchars($a->prioridad) ?></span>
                                                </td>
                                                <td class="texto-apagado"><?= date("d/m/Y", strtotime($a->fecha_inicio)) ?></td>
                                                <td class="texto-apagado">
                                                    <?= $a->fecha_fin ? date("d/m/Y", strtotime($a->fecha_fin)) : '—' ?>
                                                </td>
                                                <td><span class="etiqueta etiqueta-completada">Completada</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="js/comun.js"></script>
    <script src="js/trabajador.js"></script>

    <!-- Modal de prioridad al aceptar solicitud -->
    <div id="modalPrioridad" class="fondo-modal">
        <div class="modal" style="max-width:360px;">
            <div class="modal-encabezado">
                <div class="modal-titulo">Asignar Prioridad</div>
                <button class="modal-cerrar" onclick="cerrarModalPrioridad()">✕</button>
            </div>
            <div class="modal-divisor"></div>
            <p style="font-size:13px; color:#4d5a7a; margin-bottom:16px;">
                Selecciona la prioridad para esta solicitud antes de aceptarla.
            </p>
            <form id="formAceptar" method="POST" action="php/controlador_trabajador.php">
                <input type="hidden" name="accion" value="aceptar">
                <input type="hidden" name="id_sol" id="modal-id-sol">
                <div class="grupo-form">
                    <label class="etiqueta-form" for="modal-prioridad">Prioridad</label>
                    <select class="campo-form" id="modal-prioridad" name="prioridad" required>
                        <option value="" disabled selected>Seleccionar...</option>
                        <option value="Alta">Alta</option>
                        <option value="Media">Media</option>
                        <option value="Baja">Baja</option>
                    </select>
                </div>
                <div class="modal-pie">
                    <button type="submit" class="btn btn-primario">Confirmar</button>
                    <button type="button" class="btn btn-fantasma" onclick="cerrarModalPrioridad()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>