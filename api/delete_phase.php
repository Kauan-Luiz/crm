<?php
// api/delete_phase.php
session_start();
require_once '../config/db.php';
header("Content-Type: application/json");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); echo json_encode(['erro' => 'Login necessário']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$phase_id = $input['phase_id'];

if (!$phase_id) { echo json_encode(['erro' => 'ID inválido']); exit; }

try {
    $pdo->beginTransaction();

    // 1. Apaga todos os cards desta fase primeiro (Para evitar erro de chave estrangeira)
    $stmtCards = $pdo->prepare("DELETE FROM cards WHERE phase_id = :id");
    $stmtCards->execute([':id' => $phase_id]);

    // 2. Apaga a fase
    $stmtPhase = $pdo->prepare("DELETE FROM phases WHERE id = :id");
    $stmtPhase->execute([':id' => $phase_id]);

    $pdo->commit();
    echo json_encode(['status' => 'sucesso']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500); echo json_encode(['erro' => $e->getMessage()]);
}
?>