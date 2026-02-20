<?php
// api/webhook.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Permite requisições de fora (Make)
header("Access-Control-Allow-Methods: POST");

require_once '../config/db.php';

// 1. Captura o JSON enviado pelo Make
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// 2. Valida se os dados mínimos chegaram
if (empty($data['token_empresa']) || empty($data['pipe_id']) || empty($data['titulo'])) {
    http_response_code(400); // Bad Request
    echo json_encode(["erro" => "Faltando dados obrigatórios (token_empresa, pipe_id, titulo)"]);
    exit;
}

try {
    // 3. Autenticação: Verifica se o Token existe e é válido
    $stmtEmpresa = $pdo->prepare("SELECT id FROM empresas WHERE api_token = :token AND status = 'ativo' LIMIT 1");
    $stmtEmpresa->bindParam(':token', $data['token_empresa']);
    $stmtEmpresa->execute();
    $empresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);

    if (!$empresa) {
        http_response_code(401); // Unauthorized
        echo json_encode(["erro" => "Token inválido ou empresa suspensa."]);
        exit;
    }

    // 4. Segurança: Verifica se o Pipe pertence mesmo a essa empresa
    // (Impede que a Empresa A insira dados no Pipe da Empresa B usando o ID errado)
    $stmtPipe = $pdo->prepare("SELECT id FROM pipes WHERE id = :id AND empresa_id = :empresa_id LIMIT 1");
    $stmtPipe->bindParam(':id', $data['pipe_id']);
    $stmtPipe->bindParam(':empresa_id', $empresa['id']);
    $stmtPipe->execute();

    if ($stmtPipe->rowCount() == 0) {
        throw new Exception("Pipe não encontrado ou não pertence a esta empresa.");
    }

    // 5. Define a Fase Inicial (Se não vier 'fase_id' no JSON, pega a primeira do Pipe)
    if (!empty($data['fase_id'])) {
        $fase_id = $data['fase_id'];
    } else {
        $stmtFase = $pdo->prepare("SELECT id FROM phases WHERE pipe_id = :pipe_id ORDER BY ordem ASC LIMIT 1");
        $stmtFase->bindParam(':pipe_id', $data['pipe_id']);
        $stmtFase->execute();
        $fase = $stmtFase->fetch(PDO::FETCH_ASSOC);
        
        if (!$fase) throw new Exception("Este pipe não tem fases cadastradas.");
        $fase_id = $fase['id'];
    }

    // 6. Inicia Transação (Para garantir que salva tudo ou nada)
    $pdo->beginTransaction();

    // Insere o Card
    $sqlCard = "INSERT INTO cards (pipe_id, phase_id, titulo) VALUES (:pipe, :fase, :titulo)";
    $stmtCard = $pdo->prepare($sqlCard);
    $stmtCard->bindParam(':pipe', $data['pipe_id']);
    $stmtCard->bindParam(':fase', $fase_id);
    $stmtCard->bindParam(':titulo', $data['titulo']);
    $stmtCard->execute();
    
    $card_id = $pdo->lastInsertId();

    // 7. Salva os Campos Dinâmicos (Loop)
    // No Make você mandará um objeto "campos": { "email": "x", "tel": "y" }
    if (isset($data['campos']) && is_array($data['campos'])) {
        $sqlVal = "INSERT INTO card_values (card_id, campo_chave, campo_valor) VALUES (:card, :chave, :valor)";
        $stmtVal = $pdo->prepare($sqlVal);

        foreach ($data['campos'] as $chave => $valor) {
            // Garante que é string (caso venha número ou array do Make)
            $valorStr = is_array($valor) ? json_encode($valor) : (string)$valor;
            
            $stmtVal->bindParam(':card', $card_id);
            $stmtVal->bindParam(':chave', $chave);
            $stmtVal->bindParam(':valor', $valorStr);
            $stmtVal->execute();
        }
    }

    $pdo->commit();
    
    // Resposta de Sucesso para o Make (Status 200)
    echo json_encode([
        "status" => "sucesso", 
        "mensagem" => "Card criado com sucesso",
        "card_id" => $card_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500); // Server Error
    echo json_encode(["erro" => $e->getMessage()]);
}
?>