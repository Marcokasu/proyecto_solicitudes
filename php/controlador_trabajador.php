<?php
session_start();
if (empty($_SESSION["id"]) || !is_numeric($_SESSION["id"]) || $_SESSION["id_rol"] != 2) {
    header("Location: ../index.php");
    exit();
}

require_once "conexion.php";

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
        $semanas = match($prioridad) {
            "Alta"  => 1,
            "Media" => 2,
            "Baja"  => 3,
        };
        $fechaFin = date("Y-m-d H:i:s", strtotime("+{$semanas} weeks"));

        // 3.- Actualizar prioridad en solicitud
        $stmtPrioridad = $conexion->prepare(
            "UPDATE solicitud SET prioridad = ? WHERE id_sol = ?"
        );
        $stmtPrioridad->bind_param("si", $prioridad, $id_sol);
        $stmtPrioridad->execute();
        $stmtPrioridad->close();

        // 4.- Crear asignación
        // Los triggers se encargan de:
        // - Cambiar estado de solicitud a "En proceso"
        // - Marcar al trabajador como no disponible
        // - Evitar doble asignación
        $stmtAsignacion = $conexion->prepare(
            "INSERT INTO asignacion (id_sol, id_trabajador, fecha_fin)
             VALUES (?, ?, ?)"
        );
        $stmtAsignacion->bind_param("iis", $id_sol, $id_trabajador, $fechaFin);

        if ($stmtAsignacion->execute()) {
            $stmtAsignacion->close();
            $_SESSION["exito"] = "Solicitud aceptada y asignada correctamente.";
        } else {
            $errorMsg = $stmtAsignacion->error;
            $stmtAsignacion->close();
            $_SESSION["error"] = "Error al crear la asignación: $errorMsg";
        }

        header("Location: ../Trabajador.php");
        exit();
    }

    if ($_POST["accion"] === "reporte") {
    $id_sol              = (int)($_POST["id_sol"]               ?? 0);
    $encabezado          = trim($_POST["encabezado"]            ?? "");
    $descripcion_problema = trim($_POST["descripcion_problema"] ?? "");
    $descripcion_solucion = trim($_POST["descripcion_solucion"] ?? "");
    $id_us               = $_SESSION["id"];

    // 1.- Validaciones
    $errores = [];
    if ($id_sol < 1)              $errores[] = "Debes seleccionar una solicitud.";
    if (empty($encabezado))       $errores[] = "El título es obligatorio.";
    if (strlen($descripcion_problema) <= 10) $errores[] = "La descripción del problema debe tener más de 10 caracteres.";
    if (strlen($descripcion_solucion) <= 10) $errores[] = "La descripción de la solución debe tener más de 10 caracteres.";

    if (!empty($errores)) {
        $_SESSION["error"] = implode(" | ", $errores);
        header("Location: ../Trabajador.php");
        exit();
    }

    // 2.- Manejo de imágenes
    $evidencia = null;
    if (!empty($_FILES["fotos"]["name"][0])) {
        $carpeta = "../uploads/evidencias/";
        if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);

        $rutas = [];
        foreach ($_FILES["fotos"]["tmp_name"] as $i => $tmpName) {
            $ext = strtolower(pathinfo($_FILES["fotos"]["name"][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, ["jpg", "jpeg", "png", "webp"])) continue;
            $nombre = "reporte_{$id_sol}_" . time() . "_$i.$ext";
            if (move_uploaded_file($tmpName, $carpeta . $nombre)) {
                $rutas[] = "uploads/evidencias/" . $nombre;
            }
        }
        $evidencia = implode(",", $rutas) ?: null;
    }

    // 3.- Insertar en bitacora
    $stmt = $conexion->prepare(
        "INSERT INTO bitacora (id_sol, id_us, encabezado, descripcion_problema, descripcion_solucion, evidencia)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iissss", $id_sol, $id_us, $encabezado, $descripcion_problema, $descripcion_solucion, $evidencia);

    if ($stmt->execute()) {
        $stmt->close();

        // 4.- Marcar asignación como completada
        // En lugar de completar la asignación, poner la solicitud en "En revisión"
        // El trabajador queda ocupado hasta que el solicitante apruebe
        $stmtRevision = $conexion->prepare(
            "UPDATE solicitud SET id_estado = 4 WHERE id_sol = ?"
        );
        $stmtRevision->bind_param("i", $id_sol);
        $stmtRevision->execute();
        $stmtRevision->close();

        $_SESSION["exito"] = "Reporte enviado. Esperando aprobación del solicitante.";
    } else {
        $errorMsg = $stmt->error;
        $stmt->close();
        $_SESSION["error"] = "Error al guardar el reporte: $errorMsg";
    }

    header("Location: ../Trabajador.php");
    exit();
}
}

$rutasGuardadas = [];

if (!empty($_FILES["fotos"]["name"][0])) {
    $carpeta = "../uploads/evidencias/";

    // Crea la carpeta si no existe
    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0755, true);
    }

    foreach ($_FILES["fotos"]["tmp_name"] as $i => $tmpName) {
        $nombreOriginal = basename($_FILES["fotos"]["name"][$i]);
        $extension      = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

        // Solo permite imágenes
        if (!in_array($extension, ["jpg", "jpeg", "png", "webp"])) continue;

        // Nombre único para evitar colisiones
        $nombreFinal = "reporte_{$id_sol}_" . time() . "_$i.$extension";
        $rutaFinal   = $carpeta . $nombreFinal;

        if (move_uploaded_file($tmpName, $rutaFinal)) {
            $rutasGuardadas[] = "uploads/evidencias/" . $nombreFinal;
        }
    }
}

// Guardar en BD como string separado por comas (o una fila por imagen)
$evidencia = implode(",", $rutasGuardadas);

header("Location: ../Trabajador.php");
exit();
?>