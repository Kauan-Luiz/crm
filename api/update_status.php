<?php
// api/update_status.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

header("Content-Type: application/json");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); echo json_encode(['erro' => 'Não autorizado']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$card_id = $input['card_id'] ?? null;
$status = $input['status'] ?? null;
$usuario_id = $_SESSION['usuario_id'];

if (!$card_id || !in_array($status, ['aberto', 'ganho', 'perdido'])) {
    http_response_code(400); echo json_encode(['erro' => 'Dados inválidos']); exit;
}

try {
    $sql = "UPDATE cards SET status = :status WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':status' => $status, ':id' => $card_id]);

    // Registra no histórico a glória (ou a derrota)
    $acao = ($status == 'ganho') ? "🎉 Marcou como Venda Fechada (GANHO)" : "❌ Marcou como PERDIDO";
    registrarHistorico($pdo, $card_id, $usuario_id, $acao);

    echo json_encode(['status' => 'sucesso']);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['erro' => $e->getMessage()]);
}
?>