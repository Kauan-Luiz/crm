<?php
// api/move_card.php
session_start();
require_once '../config/db.php';

header("Content-Type: application/json");

// 1. Segurança: Só aceita se estiver logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado']);
    exit;
}

// 2. Recebe os dados do Javascript
$input = json_decode(file_get_contents('php://input'), true);
$card_id = $input['card_id'] ?? null;
$new_phase_id = $input['new_phase_id'] ?? null;

if (!$card_id || !$new_phase_id) {
    http_response_code(400);
    echo json_encode(['erro' => 'Dados incompletos']);
    exit;
}

try {
    // 3. Atualiza a fase no Banco de Dados
    // (Futuramente podemos validar se a fase pertence ao mesmo pipe para segurança extra)
    $sql = "UPDATE cards SET phase_id = :phase_id WHERE id = :card_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':phase_id', $new_phase_id);
    $stmt->bindParam(':card_id', $card_id);
    $stmt->execute();

    echo json_encode(['status' => 'sucesso']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
?>