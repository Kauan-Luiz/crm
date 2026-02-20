<?php
session_start();
require_once '../config/db.php';
header("Content-Type: application/json");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); echo json_encode(['erro' => 'Login necessário']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$pipe_id = $input['pipe_id'];
$nome = $input['nome'];

if (!$pipe_id || !$nome) { echo json_encode(['erro' => 'Dados incompletos']); exit; }

try {
    // Descobre a última ordem para colocar no final
    $sqlOrdem = "SELECT MAX(ordem) as max_ordem FROM phases WHERE pipe_id = :pipe";
    $stmt = $pdo->prepare($sqlOrdem);
    $stmt->execute([':pipe' => $pipe_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $novaOrdem = $row['max_ordem'] + 1;

    // Cria a fase
    $sql = "INSERT INTO phases (pipe_id, nome, ordem) VALUES (:pipe, :nome, :ordem)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':pipe' => $pipe_id, ':nome' => $nome, ':ordem' => $novaOrdem]);

    echo json_encode(['status' => 'sucesso']);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['erro' => $e->getMessage()]);
}
?>