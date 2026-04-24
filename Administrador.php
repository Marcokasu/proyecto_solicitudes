<!-- PHP -->
 
<?php
session_start();
if (empty($_SESSION["id"]) || !is_numeric($_SESSION["id"]) || $_SESSION["id_rol"] != 3) {
    header("Location: index.php");
    exit();
}

// Este tipo de mensajes son los flash. Se envian para su lectura y después de cumplir su función son obliterados
$msgExito = $_SESSION["exito"] ?? null;
$msgError = $_SESSION["error"] ?? null;
$seccionActiva = $_SESSION["seccion_activa"] ?? null;
unset($_SESSION["exito"], $_SESSION["error"], $_SESSION["seccion_activa"]);

require_once "php/conexion.php";

$stmtUsuarios = $conexion->prepare(
    "SELECT u.id_us, u.nombre, u.app, u.apm, u.username, u.contrasena, u.disponible,
        u.id_rol, r.nombre AS rol
    FROM usuario u
    JOIN rol r ON u.id_rol = r.id_rol
    WHERE u.id_us != ? AND u.id_rol != 3
    ORDER BY u.nombre ASC"
);
$stmtUsuarios->bind_param("i", $_SESSION["id"]);
$stmtUsuarios->execute();
$usuarios = $stmtUsuarios->get_result();
$stmtUsuarios->close();
?>

<!-- HTML -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Solicitudes - Administrador — ITSRV</title>
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
                <!-- El administrador. En color para que se distinga de entre los demás -->
                <div class="usuario-avatar" style="background-color:#3d6bbf;">AD</div>
                <div>
                    <div class="usuario-nombre">
                        <?php echo $_SESSION["nombre"]. " " .$_SESSION["app"]; ?>
                    </div>
                    <div class="usuario-rol">Control total</div>
                </div>
            </div>
        </div>

        <!-- La sección de los datos: Redirige a la sección correspondiente -->
        <nav class="sidebar-nav">
            <div class="nav-etiqueta-seccion">Panel</div>
            <a href="#" class="nav-link nav-item active" data-section="bitacora">Bitácora</a>
            <a href="#" class="nav-link nav-item" data-section="generar-reporte">Generar Reporte</a>
            <a href="#" class="nav-link nav-item" data-section="admin-usuarios">Administrar Usuarios</a>
        </nav>

        <!-- El controlador PHP destruye la sesión antes de redirigir -->
        <div class="sidebar-pie">
            <a href="php/controlador_cerrar.php" class="btn-cerrar-sesion">
                <span>❌</span> Cerrar Sesión
            </a>
        </div>
    </aside>

    <div class="contenido-principal">

        <!-- El topbar se actualiza al navegar entre secciones -->
        <header class="topbar">
            <div>
                <div class="topbar-titulo" id="topbar-titulo">Bitácora</div>
                <div class="topbar-subtitulo">Instituto Tecnológico Superior de Rioverde</div>
            </div>
        </header>

        <div class="cuerpo-pagina">

            <!-- El mensaje flash solo aparece si el controlador dejó un mensaje en sesión -->
            <?php if ($msgExito): ?>
                <div class="alerta alerta-exito"><?= htmlspecialchars($msgExito) ?></div>
            <?php endif; ?>
            <?php if ($msgError): ?>
                <div class="alerta alerta-error"><?= htmlspecialchars($msgError) ?></div>
            <?php endif; ?>

            <!-- BITÁCORA -->
            <div id="bitacora" class="section active">
                <div class="tarjeta">
                    <div class="tarjeta-encabezado">
                        <div class="tarjeta-titulo">Bitácora de solicitudes</div>
                    </div>
                    <div class="barra-herramientas">
                        <div class="campo-busqueda">
                            <input class="campo-form" type="text" placeholder="Buscar en bitácora...">
                        </div>
                        <select class="campo-form" style="width:auto; min-width:140px;">
                            <option value="">Todos los estados</option>
                            <option>Pendiente</option>
                            <option>En proceso</option>
                            <option>Completada</option>
                        </select>
                        <select class="campo-form" style="width:auto; min-width:140px;">
                            <option value="">Todas las áreas</option>
                            <option>Docencia</option>
                            <option>Coordinación Académica</option>
                            <option>Servicios Escolares</option>
                            <option>Recursos Humanos</option>
                        </select>
                    </div>
                    <!-- loop PHP y datos de la BD. (NOTA: Datos de ejemplo)-->
                    <div class="contenedor-tabla">
                        <table>
                            <thead>
                                <tr>
                                    <th>Solicitante</th>
                                    <th>Trabajador Asignado</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Detalles Resumidos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Juan Pérez</td>
                                    <td>Carlos López</td>
                                    <td><span class="etiqueta etiqueta-completada">Finalizada</span></td>
                                    <td class="texto-apagado">2023-10-01</td>
                                    <td>Solicitud de soporte técnico resuelta.</td>
                                </tr>
                                <tr>
                                    <td>María García</td>
                                    <td>Ana Rodríguez</td>
                                    <td><span class="etiqueta etiqueta-proceso">En curso</span></td>
                                    <td class="texto-apagado">2023-09-28</td>
                                    <td>Actualización de software en proceso.</td>
                                </tr>
                                <tr>
                                    <td>Pedro Sánchez</td>
                                    <td>Carlos López</td>
                                    <td><span class="etiqueta etiqueta-pendiente">Aceptada</span></td>
                                    <td class="texto-apagado">2023-09-25</td>
                                    <td>Reporte de bug aceptado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- GENERAR REPORTE  -->
            <!-- La acción se define ya cuando el back-end esté listo -->
            <div id="generar-reporte" class="section" style="display:none;">
                <div class="tarjeta" style="max-width:620px;">
                    <div class="tarjeta-encabezado">
                        <div class="tarjeta-titulo">Generar Reporte</div>
                    </div>
                    <div class="tarjeta-cuerpo">
                        <form action="#" method="post">
                            <div class="grupo-form">
                                <label class="etiqueta-form" for="tipo-reporte">Tipo de reporte</label>
                                <select class="campo-form" id="tipo-reporte" name="tipo-reporte" required>
                                    <option value="">— Selecciona tipo —</option>
                                    <option value="mensual">Mensual</option>
                                    <option value="anual">Anual</option>
                                    <option value="personalizado">Personalizado</option>
                                </select>
                            </div>
                            <!-- Este div es un grid de dos columnas -->
                            <div class="fila-form">
                                <div class="grupo-form">
                                    <label class="etiqueta-form" for="fecha-inicio">Fecha de inicio</label>
                                    <input class="campo-form" type="date" id="fecha-inicio" name="fecha-inicio" required>
                                </div>
                                <div class="grupo-form">
                                    <label class="etiqueta-form" for="fecha-fin">Fecha de fin</label>
                                    <input class="campo-form" type="date" id="fecha-fin" name="fecha-fin" required>
                                </div>
                            </div>
                            <div class="grupo-form">
                                <label class="etiqueta-form" for="descripcion-reporte">Descripción adicional</label>
                                <textarea class="campo-form" id="descripcion-reporte" name="descripcion-reporte" rows="4" placeholder="Describe el reporte"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primario">Generar Reporte</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ADMINISTRAR USUARIOS -->
            <div id="admin-usuarios" class="section" style="display:none;">
                <div class="tarjeta">
                    <div class="tarjeta-encabezado">
                        <div class="tarjeta-titulo">Administrar Usuarios</div>
                        <!-- Se abre el modal para  agregar usuarios nuevos -->
                        <button class="btn btn-primario btn-pequeno add-user" onclick="openModal('add')">Agregar Usuario</button>
                    </div>
                    <!-- Busqueda y filtro de roles para que el admin encuentre usuarios específicos -->
                    <div class="barra-herramientas">
                        <div class="campo-busqueda">
                            <input class="campo-form" type="text" id="buscar-usuario" placeholder="Buscar usuario...">
                        </div>
                        <select class="campo-form" id="filtro-rol" style="width:auto; min-width:140px;">
                            <option value="">Todos los roles</option>
                            <option value="trabajador">Trabajador</option>
                            <option value="solicitante">Solicitante</option>
                        </select>
                    </div>
                    <div class="contenedor-tabla">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Disponible</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <!-- Se hace un filtro de filas -->
                            <tbody id="tabla-usuarios">
                            <?php if ($usuarios->num_rows === 0): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; color:#8f98b2;">
                                        No hay usuarios registrados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($u = $usuarios->fetch_object()): ?>
                                    <?php
                                        $iniciales = strtoupper(substr($u->nombre, 0, 1) . substr($u->app, 0, 1));
                                        $nombreCompleto = htmlspecialchars($u->nombre . " " . $u->app . ($u->apm ? " " . $u->apm : ""));
                                        $claseRol = $u->rol === "Trabajador" ? "etiqueta-proceso" : "etiqueta-pendiente";
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <div class="avatar-fila-tabla"><?= $iniciales ?></div>
                                                <strong><?= $nombreCompleto ?></strong>
                                            </div>
                                        </td>
                                        <td class="texto-apagado"><?= htmlspecialchars($u->username) ?></td>
                                        <td><span class="etiqueta <?= $claseRol ?>"><?= htmlspecialchars($u->rol) ?></span></td>
                                        <td>
                                            <?php if ($u->disponible): ?>
                                                <span class="etiqueta etiqueta-completada">Sí</span>
                                            <?php else: ?>
                                                <span class="etiqueta etiqueta-cancelada">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="acciones-tabla">
                                                <!-- Todos los datos del usuario se pasan al JS para pre-rellenar el modal sin necesidad de un fetch -->
                                                <button class="btn btn-advertencia btn-pequeno"
                                                        onclick="openModal('edit', <?= $u->id_us ?>, '<?= htmlspecialchars($u->nombre) ?>', '<?= htmlspecialchars($u->app) ?>', '<?= htmlspecialchars($u->apm ?? "") ?>', '<?= htmlspecialchars($u->username) ?>', <?= $u->id_rol ?>)">
                                                    Editar
                                                </button>
                                                <button class="btn btn-peligro btn-pequeno"
                                                        onclick="deleteUser(<?= $u->id_us ?>, '<?= $nombreCompleto ?>')">
                                                    Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL: AGREGAR / EDITAR USUARIO  -->
    <div id="userModal" class="fondo-modal">
        <div class="modal">
            <div class="modal-encabezado">
                <!-- JS cambia este texto entre "Agregar Usuario" y "Editar Usuario" -->
                <div class="modal-titulo" id="modal-title">Agregar Usuario</div>
                <button class="modal-cerrar" onclick="closeModal()">✕</button>
            </div>
            <div class="modal-divisor"></div>

            <form id="userForm" method="POST" action="php/controlador_usuario.php">
                <input type="hidden" name="accion" value="agregar">
                <input type="hidden" name="id_us" id="user-id">

                <div class="grupo-form">
                    <label class="etiqueta-form" for="user-nombre">Nombre</label>
                    <input class="campo-form" type="text" id="user-nombre" name="nombre" maxlength="50" required>
                </div>

                <div class="grupo-form">
                    <label class="etiqueta-form" for="user-app">Apellido Paterno</label>
                    <input class="campo-form" type="text" id="user-app" name="app" maxlength="50" required>
                </div>

                <!-- Opcional: Es decir, no incluye el atributo required que obliga a rellenar ese input -->
                <div class="grupo-form">
                    <label class="etiqueta-form" for="user-apm">Apellido Materno</label>
                    <input class="campo-form" type="text" id="user-apm" name="apm" maxlength="50" placeholder="Opcional">
                </div>

                <div class="grupo-form">
                    <label class="etiqueta-form" for="user-name">Usuario/Username</label>
                    <input class="campo-form" type="text" id="user-name" name="username" maxlength="50" required>
                </div>

                <!-- La nota se muestra en modo edición -->
                <div class="grupo-form">
                    <label class="etiqueta-form" for="user-password">Contraseña</label>
                    <small id="password-nota" style="color:#8f98b2; display:none; font-size:10px;">
                        Dejar vacío para no cambiar
                    </small>
                    <input class="campo-form" type="password" id="user-password" name="password" maxlength="255" required>
                </div>

                <!-- Se valida si ambas contraseñas coinciden.  -->
                <div class="grupo-form">
                    <label class="etiqueta-form" for="user-password2">Confirmar Contraseña</label>
                    <input class="campo-form" type="password" id="user-password2" name="password2" maxlength="255" required>
                </div>

                <div class="grupo-form">
                    <label class="etiqueta-form" for="user-role">Rol</label>
                    <select class="campo-form" id="user-role" name="id_rol" required>
                        <option value="" disabled selected>Seleccionar...</option>
                        <option value="1">Solicitante</option>
                        <option value="2">Trabajador</option>
                    </select>
                </div>

                <div class="modal-pie">
                    <button type="submit" class="btn btn-primario">Guardar</button>
                    <button type="button" class="btn btn-fantasma" onclick="closeModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/comun.js"></script>
    <script src="js/administrador.js"></script>

    <?php if ($seccionActiva): ?>
    <script>
        navegarSeccion("<?= htmlspecialchars($seccionActiva) ?>", titulosSecciones);
    </script>
    <?php endif; ?>
</body>
</html>