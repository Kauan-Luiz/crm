<?php
session_start();
require_once '../config/db.php';
header("Content-Type: application/json");

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); echo json_encode(['erro' => 'Login necessário']); exit;
}

// Recebe a lista de IDs na nova ordem (ex: [5, 2, 8, 1])
$input = json_decode(file_get_contents('php://input'), true);
$ordemFases = $input['ordem'];

if (!is_array($ordemFases)) {
    echo json_encode(['erro' => 'Dados inválidos']); exit;
}

try {
    $pdo->beginTransaction();

    // Loop para atualizar a ordem de cada fase
    $sql = "UPDATE phases SET ordem = :nova_ordem WHERE id = :id";
    $stmt = $pdo->prepare($sql);

    foreach ($ordemFases as $index => $id_fase) {
        $stmt->bindValue(':nova_ordem', $index + 1); // +1 porque array começa em 0
        $stmt->bindValue(':id', $id_fase);
        $stmt->execute();
    }

    $pdo->commit();
    echo json_encode(['status' => 'sucesso']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500); echo json_encode(['erro' => $e->getMessage()]);
}
?>