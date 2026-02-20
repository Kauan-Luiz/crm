<?php
// api/webhook.php
require_once '../config/db.php';

// Permite receber requisições de qualquer lugar (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

// 1. Pega os parâmetros da URL
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
$pipe_id_url = filter_input(INPUT_GET, 'pipe_id', FILTER_VALIDATE_INT); // NOVO: Pega o pipe_id

if (!$token) {
    http_response_code(401);
    echo json_encode(['erro' => 'Token não fornecido.']);
    exit;
}

try {
    // 2. Valida o Token e descobre a Empresa
    $stmtEmp = $pdo->prepare("SELECT id FROM empresas WHERE api_token = :token AND status = 'ativo'");
    $stmtEmp->execute([':token' => $token]);
    $empresa = $stmtEmp->fetch(PDO::FETCH_ASSOC);

    if (!$empresa) {
        http_response_code(401);
        echo json_encode(['erro' => 'Token inválido ou empresa inativa.']);
        exit;
    }

    $empresa_id = $empresa['id'];

    // 3. Lê os dados que chegaram
    $payload = file_get_contents('php://input');
    $dados = json_decode($payload, true);
    
    if (!$dados) {
        $dados = $_POST;
    }

    if (empty($dados)) {
        http_response_code(400);
        echo json_encode(['erro' => 'Nenhum dado recebido.']);
        exit;
    }

    $pdo->beginTransaction();

    // 4. DESCOBRE ONDE SALVAR O LEAD (A MÁGICA DO PIPE_ID)
    $pipe = false;

    // A. Tenta usar o pipe_id passado na URL (Validando se pertence à empresa!)
    if ($pipe_id_url) {
        $stmtPipeUrl = $pdo->prepare("SELECT id FROM pipes WHERE id = :pipe_id AND empresa_id = :empresa_id");
        $stmtPipeUrl->execute([':pipe_id' => $pipe_id_url, ':empresa_id' => $empresa_id]);
        $pipe = $stmtPipeUrl->fetch(PDO::FETCH_ASSOC);
    }

    // B. Se não passou na URL ou passou um ID inválido, usa o primeiro pipe que a empresa tiver (Fallback de segurança)
    if (!$pipe) {
        $stmtPipe = $pdo->prepare("SELECT id FROM pipes WHERE empresa_id = :empresa_id ORDER BY id ASC LIMIT 1");
        $stmtPipe->execute([':empresa_id' => $empresa_id]);
        $pipe = $stmtPipe->fetch(PDO::FETCH_ASSOC);
    }

    // Se a empresa for nova e não tiver absolutamente nenhum Pipe, cria um genérico
    if (!$pipe) {
        $pdo->exec("INSERT INTO pipes (empresa_id, nome) VALUES ($empresa_id, 'Funil Principal')");
        $pipe_id = $pdo->lastInsertId();
        $pdo->exec("INSERT INTO phases (pipe_id, nome, ordem) VALUES ($pipe_id, 'Novos Leads', 1)");
        $phase_id = $pdo->lastInsertId();
    } else {
        $pipe_id = $pipe['id'];
        
        // Pega sempre a primeira fase (coluna mais à esquerda) do Pipe escolhido
        $stmtPhase = $pdo->prepare("SELECT id FROM phases WHERE pipe_id = :pipe_id ORDER BY ordem ASC LIMIT 1");
        $stmtPhase->execute([':pipe_id' => $pipe_id]);
        $phase = $stmtPhase->fetch(PDO::FETCH_ASSOC);
        
        if (!$phase) {
            $pdo->exec("INSERT INTO phases (pipe_id, nome, ordem) VALUES ($pipe_id, 'Caixa de Entrada', 1)");
            $phase_id = $pdo->lastInsertId();
        } else {
            $phase_id = $phase['id'];
        }
    }

    // 5. Tenta achar o nome do Lead
    $titulo_card = "Novo Lead";
    $chaves_nome = ['nome', 'name', 'first_name', 'Nome', 'Name', 'lead_name'];
    foreach ($chaves_nome as $chave) {
        if (!empty($dados[$chave])) {
            $titulo_card = $dados[$chave];
            break;
        }
    }

    // 6. Cria o Card no Banco
    $stmtCard = $pdo->prepare("INSERT INTO cards (pipe_id, phase_id, titulo) VALUES (:pipe, :phase, :titulo)");
    $stmtCard->execute([':pipe' => $pipe_id, ':phase' => $phase_id, ':titulo' => $titulo_card]);
    $card_id = $pdo->lastInsertId();

    // 7. Salva os outros dados na tabela dinâmica
    $stmtValor = $pdo->prepare("INSERT INTO card_values (card_id, campo_chave, campo_valor) VALUES (:card, :chave, :valor)");
    
    foreach ($dados as $chave => $valor) {
        if (is_array($valor) || is_object($valor)) {
            $valor = json_encode($valor);
        }
        $stmtValor->execute([
            ':card' => $card_id,
            ':chave' => substr($chave, 0, 100),
            ':valor' => $valor
        ]);
    }

    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'status' => 'sucesso', 
        'mensagem' => 'Lead recebido e processado!', 
        'pipe_destino' => $pipe_id,
        'card_id' => $card_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno ao processar webhook: ' . $e->getMessage()]);
}
?>