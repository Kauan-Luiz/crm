<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php'; // Carrega nossa nova função!

header("Content-Type: application/json");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); echo json_encode(['erro' => 'Login necessário']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$card_id = $input['card_id'] ?? null;
$new_phase_id = $input['new_phase_id'] ?? null;
$usuario_id = $_SESSION['usuario_id'];

if (!$card_id || !$new_phase_id) {
    http_response_code(400); echo json_encode(['erro' => 'Dados inválidos']); exit;
}

try {
    // 1. Descobre a fase ANTIGA e a NOVA para escrever no histórico
    $stmtAntiga = $pdo->prepare("SELECT p.nome FROM cards c JOIN phases p ON c.phase_id = p.id WHERE c.id = :id");
    $stmtAntiga->execute([':id' => $card_id]);
    $faseAntiga = $stmtAntiga->fetchColumn();

    $stmtNova = $pdo->prepare("SELECT nome FROM phases WHERE id = :id");
    $stmtNova->execute([':id' => $new_phase_id]);
    $faseNova = $stmtNova->fetchColumn();

    // 2. Atualiza a fase do card
    $sql = "UPDATE cards SET phase_id = :phase WHERE id = :card";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':phase' => $new_phase_id, ':card' => $card_id]);

    // 3. REGISTRA O HISTÓRICO!
    if ($faseAntiga != $faseNova) {
        $detalhes = "Moveu de <b>{$faseAntiga}</b> para <b>{$faseNova}</b>";
        registrarHistorico($pdo, $card_id, $usuario_id, "Moveu o Card", $detalhes);
    }

    echo json_encode(['status' => 'sucesso']);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['erro' => $e->getMessage()]);
}
?>