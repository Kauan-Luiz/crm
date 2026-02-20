<?php
// api/assign_user.php
session_start();
require_once '../config/db.php';
header("Content-Type: application/json");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); echo json_encode(['erro' => 'Login necessário']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$card_id = $input['card_id'] ?? null;
// Se vier vazio, transforma em NULL para o banco (significa "Sem responsável")
$responsavel_id = !empty($input['responsavel_id']) ? $input['responsavel_id'] : null;

if (!$card_id) {
    echo json_encode(['erro' => 'ID do card não informado']); exit;
}

try {
    $sql = "UPDATE cards SET responsavel_id = :resp WHERE id = :card";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':resp' => $responsavel_id,
        ':card' => $card_id
    ]);

    echo json_encode(['status' => 'sucesso']);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['erro' => $e->getMessage()]);
}
?>