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
}

header("Location: ../Trabajador.php");
exit();
?>