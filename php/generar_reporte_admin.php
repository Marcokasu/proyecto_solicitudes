<?php
session_start();
if (empty($_SESSION["id"]) || !is_numeric($_SESSION["id"]) || $_SESSION["id_rol"] != 3) {
    header("Location: ../index.php");
    exit();
}

require_once "conexion.php";

date_default_timezone_set('America/Mexico_City');

$fechaInicio = trim($_POST["fecha_inicio"] ?? "");
$fechaFin    = trim($_POST["fecha_fin"]    ?? "");

if (empty($fechaInicio) || empty($fechaFin)) {
    $_SESSION["error"] = "Debes seleccionar inicio y fin del período.";
    header("Location: ../Administrador.php");
    exit();
}

// Reportes aprobados dentro del rango (por fecha_fin de asignacion = fecha de aprobación)
$stmt = $conexion->prepare(
    "SELECT
        ar.nombre                                        AS area,
        CONCAT(u.nombre, ' ', u.app)                    AS trabajador,
        s.encabezado                                     AS solicitud,
        DATE_FORMAT(a.fecha_fin, '%d/%m/%Y %H:%i')      AS fecha_aprobacion
     FROM bitacora b
     JOIN solicitud s  ON s.id_sol        = b.id_sol
     JOIN area ar      ON ar.id_area      = s.id_area
     JOIN asignacion a ON a.id_sol        = s.id_sol
                      AND a.estado_asignacion = 'completada'
     JOIN usuario u    ON u.id_us         = a.id_trabajador
     WHERE b.aprobado = 1
       AND a.fecha_fin >= ?
       AND a.fecha_fin <= ?
     ORDER BY a.fecha_fin ASC"
);
$fechaFinFull = $fechaFin . " 23:59:59";
$stmt->bind_param("ss", $fechaInicio, $fechaFinFull);
$stmt->execute();
$reportes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── PDF ──────────────────────────────────────────────────────────────────────
$fpdfPath = __DIR__ . '/../lib/fpdf/fpdf.php';
if (!file_exists($fpdfPath)) {
    die("FPDF no encontrado en $fpdfPath");
}
require_once $fpdfPath;

$enc = fn(string $s): string => iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);

$pageW    = 215.9;
$marginL  = 15;
$marginR  = 15;
$marginT  = 15;
$contentW = $pageW - $marginL - $marginR;   // 185.9 mm

$fechaInicioFmt = date('d/m/Y', strtotime($fechaInicio));
$fechaFinFmt    = date('d/m/Y', strtotime($fechaFin));
$fechaHoy       = date('d/m/Y \a \l\a\s H:i:s');

$pieTexto = $enc("ITSRV SOPORTEC — Reporte de Período Escolar — Generado el $fechaHoy");

$pdf = new class($pieTexto, $marginL, $marginR, $pageW) extends FPDF {
    public string $pieTexto;
    public float  $mL;
    public float  $mR;
    public float  $pW;

    public function __construct(string $pie, float $mL, float $mR, float $pW) {
        parent::__construct('P', 'mm', 'Letter');
        $this->pieTexto = $pie;
        $this->mL       = $mL;
        $this->mR       = $mR;
        $this->pW       = $pW;
    }

    public function Footer(): void {
        $this->SetY(-16);
        $this->SetDrawColor(180, 180, 180);
        $this->SetLineWidth(0.2);
        $this->Line($this->mL, $this->GetY(), $this->pW - $this->mR, $this->GetY());
        $this->Ln(2);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 5, $this->pieTexto, 0, 0, 'C');
    }
};

$pdf->SetMargins($marginL, $marginT, $marginR);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

// ── CABECERA ─────────────────────────────────────────────────────────────────
$logoPath = __DIR__ . '/../img/logo_tec_.png';
$logoW    = 14;

if (file_exists($logoPath)) {
    $pdf->Image($logoPath, $marginL, $marginT, $logoW);
}

// Nombre e institución a la derecha del logo
$pdf->SetFont('Arial', 'B', 15);
$pdf->SetTextColor(27, 85, 45);
$pdf->SetXY($marginL + $logoW + 3, $marginT + 1);
$pdf->Cell(0, 7, $enc('ITSRV SOPORTEC'), 0, 1);

$pdf->SetFont('Arial', '', 8.5);
$pdf->SetTextColor(100, 100, 100);
$pdf->SetX($marginL + $logoW + 3);
$pdf->Cell(0, 5, $enc('Instituto Tecnológico Superior de Rioverde'), 0, 1);

$pdf->Ln(4);

// Línea divisoria verde
$pdf->SetDrawColor(27, 85, 45);
$pdf->SetLineWidth(0.5);
$pdf->Line($marginL, $pdf->GetY(), $pageW - $marginR, $pdf->GetY());
$pdf->Ln(5);

// Título del documento
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(20, 20, 20);
$pdf->Cell(0, 8, $enc('Reporte de Período Escolar'), 0, 1);

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(90, 90, 90);
$pdf->Cell(0, 5, $enc("Período: $fechaInicioFmt  —  $fechaFinFmt"), 0, 1);
$pdf->Cell(0, 5, $enc("Total de reportes aprobados: " . count($reportes)), 0, 1);
$pdf->Ln(5);

// ── TABLA ────────────────────────────────────────────────────────────────────
// Anchos de columna (total = 185.9 mm)
$colW = [34, 42, 52, 27.9, 30];
$colH = ['Area', 'Tecnico', 'Solicitud', 'Fecha de aprobacion', 'Firma'];
$aln  = ['L',   'L',       'L',         'C',                   'C'];

// Fila de encabezado
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(27, 85, 45);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetDrawColor(200, 200, 200);
$pdf->SetLineWidth(0.2);

$headers = [
    $enc('Área'),
    $enc('Técnico'),
    $enc('Nombre de la Solicitud'),
    $enc('Fecha'),
    $enc('Firma'),
];
foreach ($headers as $i => $h) {
    $pdf->Cell($colW[$i], 8, $h, 1, 0, 'C', true);
}
$pdf->Ln();

// Filas de datos
$pdf->SetFont('Arial', '', 7.5);
$pdf->SetTextColor(30, 30, 30);
$fill = false;

if (empty($reportes)) {
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(130, 130, 130);
    $pdf->SetFillColor(250, 250, 250);
    $pdf->Cell(array_sum($colW), 9,
        $enc('No hay reportes aprobados en el período seleccionado.'), 1, 1, 'C', true);
} else {
    foreach ($reportes as $r) {
        $pdf->SetFillColor($fill ? 243 : 255, $fill ? 247 : 255, $fill ? 243 : 255);
        $valores = [
            $enc($r['area']),
            $enc($r['trabajador']),
            $enc($r['solicitud']),
            $enc($r['fecha_aprobacion']),
            '',
        ];
        foreach ($valores as $i => $v) {
            $pdf->Cell($colW[$i], 8, $v, 1, 0, $aln[$i], true);
        }
        $pdf->Ln();
        $fill = !$fill;
    }
}

// ── SALIDA ───────────────────────────────────────────────────────────────────
$nombre = "reporte-periodo_{$fechaInicio}_{$fechaFin}.pdf";
$pdf->Output('D', $nombre);
exit();
