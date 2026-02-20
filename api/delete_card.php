<?php
session_start();
require_once '../config/db.php';
header("Content-Type: application/json");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); echo json_encode(['erro' => 'Login necessário']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$card_id = $input['card_id'];

if (!$card_id) { echo json_encode(['erro' => 'ID inválido']); exit; }

try {
    // Deleta o card (O banco já deleta os card_values automaticamente por causa do CASCADE)
    $stmt = $pdo->prepare("DELETE FROM cards WHERE id = :id");
    $stmt->execute([':id' => $card_id]);

    echo json_encode(['status' => 'sucesso']);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['erro' => $e->getMessage()]);
}
?>