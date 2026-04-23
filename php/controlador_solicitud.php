<?php
session_start();
if (empty($_SESSION["id"]) || !is_numeric($_SESSION["id"]) || $_SESSION["id_rol"] != 1) {
    header("Location: ../index.php");
    exit();
}

require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1.- Variables
    $encabezado  = trim($_POST["titulo"]      ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $prioridad   = "Sin Asignar";
    $id_us       = $_SESSION["id"];

    // 2.- Validaciones
    $errores = [];
    if (empty($encabezado)) {
        $errores[] = "El título es obligatorio.";
    }
    if (strlen($descripcion) <= 10) {
        $errores[] = "La descripción debe tener más de 10 caracteres.";
    }
    if (!in_array($prioridad, ["Alta", "Media", "Baja", "Sin Asignar"])) {
        $errores[] = "Prioridad no válida.";
    }

    if (!empty($errores)) {
        $_SESSION["error"] = implode(" | ", $errores);
        header("Location: ../Solicitante.php");
        exit();
    }

    // 3.- Inserción (id_estado queda en 1 = Pendiente por defecto)
    $stmt = $conexion->prepare(
        "INSERT INTO solicitud (id_us, encabezado, descripcion, prioridad)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("isss", $id_us, $encabezado, $descripcion, $prioridad);

    if ($stmt->execute()) {
        $stmt->close();
        $_SESSION["exito"] = "Solicitud enviada correctamente.";
    } else {
        $errorMsg = $stmt->error;
        $stmt->close();
        $_SESSION["error"] = "Error al enviar la solicitud: $errorMsg";
    }
    header("Location: ../Solicitante.php");
    exit();
}

header("Location: ../Solicitante.php");
exit();
?>