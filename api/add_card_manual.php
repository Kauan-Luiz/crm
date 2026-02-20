<?php
session_start();
require_once '../config/db.php';
header("Content-Type: application/json");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); echo json_encode(['erro' => 'Login necessário']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$pipe_id = $input['pipe_id'];
$phase_id = $input['phase_id'];
$titulo = $input['titulo'];

if (!$pipe_id || !$phase_id || !$titulo) { echo json_encode(['erro' => 'Dados incompletos']); exit; }

try {
    $sql = "INSERT INTO cards (pipe_id, phase_id, titulo) VALUES (:pipe, :phase, :titulo)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':pipe' => $pipe_id, ':phase' => $phase_id, ':titulo' => $titulo]);
    
    echo json_encode(['status' => 'sucesso', 'id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['erro' => $e->getMessage()]);
}
?>