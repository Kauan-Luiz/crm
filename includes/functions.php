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
?>