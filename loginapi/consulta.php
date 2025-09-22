<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin = $_GET['fecha_fin'] ?? '';
$piloto_id = $_GET['piloto_id'] ?? '';

if (empty($fechaInicio) || empty($fechaFin)) {
    echo json_encode(["success" => false, "message" => "Fechas requeridas"]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=loginapp;charset=utf8mb4", "root", "admin");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta principal: obtener formularios
    $sql = "
        SELECT 
            f.id,
            u.username AS piloto,
            f.cliente_nombre AS cliente,
            f.lugar_entrega AS lugar,
            f.tipo_documento,
            f.numero_documento,
            f.confirmado,
            f.fecha_registro
        FROM formularios f
        JOIN usuarios u ON f.piloto_id = u.id
        WHERE f.fecha_registro BETWEEN ? AND ?
    ";

    $params = ["$fechaInicio 00:00:00", "$fechaFin 23:59:59"];

    if (!empty($piloto_id) && is_numeric($piloto_id)) {
        $sql .= " AND u.id = ?";
        $params[] = $piloto_id;
    }

    $sql .= " ORDER BY f.fecha_registro DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $formularios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Para cada formulario, obtener los nombres de los acompañantes
    foreach ($formularios as &$formulario) {
        $stmt2 = $pdo->prepare("
            SELECT a.nombre 
            FROM formularios_acompanantes fa
            JOIN acompanantes a ON fa.acompanante_id = a.id
            WHERE fa.formulario_id = ?
        ");
        $stmt2->execute([$formulario['id']]); // ❌ Error: 'id' no está en el SELECT
        $acompanantes = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        $formulario['acompanante'] = implode(", ", $acompanantes);
    }

    echo json_encode([
        "success" => true,
        "data" => $formularios
    ]);

} catch (PDOException $e) {
    error_log("SQL Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Error en BD: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>