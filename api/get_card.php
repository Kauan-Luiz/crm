<?php
session_start();
require_once '../config/db.php';

header("Content-Type: application/json");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); echo json_encode(['erro' => 'Não autorizado']); exit;
}

$card_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    // Busca Card Principal
    $stmt = $pdo->prepare("SELECT c.*, u.nome as responsavel_nome FROM cards c LEFT JOIN usuarios u ON c.responsavel_id = u.id WHERE c.id = :id");
    $stmt->execute([':id' => $card_id]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);

    // Busca Campos Extras
    $stmtVal = $pdo->prepare("SELECT campo_chave, campo_valor FROM card_values WHERE card_id = :id");
    $stmtVal->execute([':id' => $card_id]);
    $valores = $stmtVal->fetchAll(PDO::FETCH_ASSOC);

    // NOVO: Busca o Histórico!
    $stmtHist = $pdo->prepare("SELECT h.*, u.nome as usuario_nome FROM card_history h LEFT JOIN usuarios u ON h.usuario_id = u.id WHERE h.card_id = :id ORDER BY h.id DESC");
    $stmtHist->execute([':id' => $card_id]);
    $historico = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'card' => $card,
        'valores' => $valores,
        'historico' => $historico // Mandando pro Javascript!
    ]);

} catch (Exception $e) {
    http_response_code(500); echo json_encode(['erro' => $e->getMessage()]);
}
?>