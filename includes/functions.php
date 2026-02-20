<?php
// Função para gravar a mensagem na sessão
function setFlash($key, $message, $type = 'success') {
    $_SESSION['flash'][$key] = [
        'message' => $message,
        'type' => $type
    ];
}

// Função para exibir e apagar a mensagem
function getFlash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]); // Limpa para não aparecer de novo ao recarregar
        
        // Define a classe CSS baseada no tipo (sucesso ou erro)
        $cssClass = ($msg['type'] == 'error') ? 'alert-error' : 'alert-success';
        
        return "<div class='alert $cssClass'>{$msg['message']}</div>";
    }
    return '';
}

// Função para registrar o histórico do card
function registrarHistorico($pdo, $card_id, $usuario_id, $acao, $detalhes = '') {
    $sql = "INSERT INTO card_history (card_id, usuario_id, acao, detalhes) VALUES (:card_id, :usuario_id, :acao, :detalhes)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':card_id' => $card_id,
        ':usuario_id' => $usuario_id,
        ':acao' => $acao,
        ':detalhes' => $detalhes
    ]);
}

?>