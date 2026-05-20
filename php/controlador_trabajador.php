<?php
session_start();
if (empty($_SESSION["id"]) || !is_numeric($_SESSION["id"]) || $_SESSION["id_rol"] != 2) {
    header("Location: ../index.php");
    exit();
}

require_once "conexion.php";

date_default_timezone_set('America/Mexico_City');

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["accion"])) {

    if ($_POST["accion"] === "aceptar") {
        $id_sol      = (int)($_POST["id_sol"]    ?? 0);
        $prioridad   = trim($_POST["prioridad"]  ?? "");
        $id_trabajador = $_SESSION["id"];

        // 1.- Validaciones
        $errores = [];
        if ($id_sol < 1) {
            $errores[] = "Solicitud no válida.";
        }
        if (!in_array($prioridad, ["Alta", "Media", "Baja"])) {
            $errores[] = "Prioridad no válida.";
        }
        if (!empty($errores)) {
            $_SESSION["error"] = implode(" | ", $errores);
            header("Location: ../Trabajador.php");
            exit();
        }

        // 2.- Calcular fecha_fin según prioridad
        $horas = match($prioridad) {
            "Alta"  => 2,
            "Media" => 5,
            "Baja"  => 24,
        };
        $fechaFin = date("Y-m-d H:i:s", strtotime("+{$horas} hours"));

        // 3.- Actualizar prioridad y fecha límite en solicitud
        $stmtPrioridad = $conexion->prepare(
            "UPDATE solicitud SET prioridad = ?, fecha_limite = ? WHERE id_sol = ?"
        );
        $stmtPrioridad->bind_param("ssi", $prioridad, $fechaFin, $id_sol);
        $stmtPrioridad->execute();
        $stmtPrioridad->close();

        // 4.- Crear asignación
        // Los triggers se encargan de:
        // - Cambiar estado de solicitud a "En proceso"
        // - Marcar al trabajador como no disponible
        // - Evitar doble asignación
        try {
            $stmtAsignacion = $conexion->prepare(
                "INSERT INTO asignacion (id_sol, id_trabajador, fecha_fin)
                 VALUES (?, ?, ?)"
            );
            $stmtAsignacion->bind_param("iis", $id_sol, $id_trabajador, $fechaFin);
            $stmtAsignacion->execute();
            $stmtAsignacion->close();
            require_once __DIR__ . '/correo.php';
            require_once __DIR__ . '/notificaciones.php';
            correoSolicitudAceptada($conexion, $id_sol);
            notifSolicitudAceptada($conexion, $id_sol);
            $_SESSION["exito"] = "Solicitud aceptada y asignada correctamente.";
        } catch (mysqli_sql_exception $e) {
            // El trigger rechazó la asignación: la solicitud ya fue tomada por otro trabajador
            $_SESSION["error"] = "Esta solicitud ya fue aceptada por otro trabajador. Recarga la página para ver las solicitudes disponibles.";
        }

        header("Location: ../Trabajador.php");
        exit();
    }

    if ($_POST["accion"] === "reporte") {
        $id_sol               = (int)($_POST["id_sol"]               ?? 0);
        $encabezado           = trim($_POST["encabezado"]            ?? "");
        $descripcion_problema = trim($_POST["descripcion_problema"]  ?? "");
        $descripcion_solucion = trim($_POST["descripcion_solucion"]  ?? "");
        $id_us                = $_SESSION["id"];

        // 1.- Validaciones
        $errores     = [];
        $fotosSubidas = array_filter($_FILES["fotos"]["name"] ?? [], fn($n) => $n !== "");

        if ($id_sol < 1)                           $errores[] = "Debes seleccionar una solicitud.";
        if (empty($encabezado))                    $errores[] = "El título es obligatorio.";
        elseif (strlen($encabezado) > 50)          $errores[] = "El título no puede exceder 50 caracteres.";
        if (strlen($descripcion_problema) <= 10)   $errores[] = "La descripción del problema debe tener más de 10 caracteres.";
        elseif (strlen($descripcion_problema) > 120) $errores[] = "La descripción del problema no puede exceder 120 caracteres.";
        if (strlen($descripcion_solucion) <= 10)   $errores[] = "La descripción de la solución debe tener más de 10 caracteres.";
        elseif (strlen($descripcion_solucion) > 120) $errores[] = "La descripción de la solución no puede exceder 120 caracteres.";
        if (count($fotosSubidas) === 0)            $errores[] = "Debes subir al menos 1 fotografía como evidencia.";
        if (count($fotosSubidas) > 3)              $errores[] = "Solo se permiten hasta 3 fotografías.";

        if (!empty($errores)) {
            $_SESSION["error"]          = implode(" | ", $errores);
            $_SESSION["seccion_activa"] = "reporte";
            header("Location: ../Trabajador.php");
            exit();
        }

        // 2.- Insertar en bitacora sin evidencia para obtener id_bit
        $stmt = $conexion->prepare(
            "INSERT INTO bitacora (id_sol, id_us, encabezado, descripcion_problema, descripcion_solucion)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iisss", $id_sol, $id_us, $encabezado, $descripcion_problema, $descripcion_solucion);

        if (!$stmt->execute()) {
            $errorMsg = $stmt->error;
            $stmt->close();
            $_SESSION["error"]          = "Error al guardar el reporte: $errorMsg";
            $_SESSION["seccion_activa"] = "reporte";
            header("Location: ../Trabajador.php");
            exit();
        }
        $id_bit = $stmt->insert_id;
        $stmt->close();

        // 3.- Preparar carpeta del reporte [id_bit]-[slug]
        $slug    = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $encabezado);
        $slug    = preg_replace('/[^a-zA-Z0-9]+/', '-', $slug);
        $slug    = trim($slug, '-');
        $slug    = substr($slug, 0, 50);
        $carpeta = "../uploads/evidencias/{$id_bit}-{$slug}/";
        $rutaWeb = "uploads/evidencias/{$id_bit}-{$slug}/";
        if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);

        // 4.- Mover imágenes a la carpeta
        $evidencia = null;
        $rutas     = [];
        $contador  = 0;
        $allowed   = ["jpg", "jpeg", "png", "webp"];

        foreach ($_FILES["fotos"]["error"] as $i => $error) {
            if ($contador >= 3) break;
            if ($error !== UPLOAD_ERR_OK) continue;

            $tmpName = $_FILES["fotos"]["tmp_name"][$i];
            $ext     = strtolower(pathinfo($_FILES["fotos"]["name"][$i], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed) || !is_uploaded_file($tmpName)) continue;

            $nombre = ($contador + 1) . ".$ext";
            if (move_uploaded_file($tmpName, $carpeta . $nombre)) {
                $rutas[] = $rutaWeb . $nombre;
                $contador++;
            }
        }

        if ($rutas) {
            $evidencia = implode(",", $rutas);
            $stmtEv = $conexion->prepare("UPDATE bitacora SET evidencia = ? WHERE id_bit = ?");
            $stmtEv->bind_param("si", $evidencia, $id_bit);
            $stmtEv->execute();
            $stmtEv->close();
        }

        // 5.- Generar PDF del reporte
        require_once __DIR__ . '/generar_pdf.php';
        generarReportePDF([
            'id_bit'               => $id_bit,
            'id_sol'               => $id_sol,
            'encabezado'           => $encabezado,
            'descripcion_problema' => $descripcion_problema,
            'descripcion_solucion' => $descripcion_solucion,
            'trabajador'           => $_SESSION["nombre"] . " " . $_SESSION["app"],
            'evidencia'            => $evidencia,
        ], $carpeta);

        // 6.- Poner solicitud en "En revisión"
        $stmtRevision = $conexion->prepare(
            "UPDATE solicitud SET id_estado = 4 WHERE id_sol = ?"
        );
        $stmtRevision->bind_param("i", $id_sol);
        $stmtRevision->execute();
        $stmtRevision->close();

        require_once __DIR__ . '/correo.php';
        require_once __DIR__ . '/notificaciones.php';
        correoReporteEnviado($conexion, $id_sol);
        notifReporteEnviado($conexion, $id_sol);

        $_SESSION["exito"] = "Reporte enviado. Esperando aprobación del solicitante.";
        header("Location: ../Trabajador.php");
        exit();
    }
}

header("Location: ../Trabajador.php");
exit();
?>