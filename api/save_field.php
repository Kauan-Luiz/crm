<?php
// api/save_field.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

header("Content-Type: application/json");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); echo json_encode(['erro' => 'Login necessário']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$card_id = $input['card_id'] ?? null;
$chave   = $input['chave'] ?? null;
$valor   = $input['valor'] ?? '';

if (!$card_id || !$chave) {
    http_response_code(400); echo json_encode(['erro' => 'Dados inválidos']); exit;
}

try {
    // Apaga anterior para evitar duplicidade
    $stmtDel = $pdo->prepare("DELETE FROM card_values WHERE card_id = :card AND campo_chave = :chave");
    $stmtDel->execute([':card' => $card_id, ':chave' => $chave]);

    // Insere novo se houver valor
    if ($valor !== '') {
        $stmtInsert = $pdo->prepare("INSERT INTO card_values (card_id, campo_chave, campo_valor) VALUES (:card, :chave, :valor)");
        $stmtInsert->execute([':card' => $card_id, ':chave' => $chave, ':valor' => $valor]);
    }

    echo json_encode(['status' => 'sucesso']);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['erro' => $e->getMessage()]);
}
?>