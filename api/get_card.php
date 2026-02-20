<?php
// api/get_card.php
session_start();
require_once '../config/db.php';

header("Content-Type: application/json");

// 1. Segurança
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado']);
    exit;
}

$card_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$card_id) {
    echo json_encode(['erro' => 'ID inválido']);
    exit;
}

try {
    // 2. Busca Dados Principais do Card
    $stmt = $pdo->prepare("SELECT * FROM cards WHERE id = :id");
    $stmt->bindParam(':id', $card_id);
    $stmt->execute();
    $card = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$card) {
        echo json_encode(['erro' => 'Card não encontrado']);
        exit;
    }

    // 3. Busca Campos Dinâmicos (Vindos do Webhook)
    $stmtVal = $pdo->prepare("SELECT campo_chave, campo_valor FROM card_values WHERE card_id = :id");
    $stmtVal->bindParam(':id', $card_id);
    $stmtVal->execute();
    $valores = $stmtVal->fetchAll(PDO::FETCH_ASSOC);

    // 4. Retorna Tudo
    echo json_encode([
        'card' => $card,
        'valores' => $valores
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
?>