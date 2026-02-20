<?php
// auth.php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $senha = trim($_POST['password']);

    if (empty($email) || empty($senha)) {
        setFlash('login', 'Preencha todos os campos!', 'danger');
        header("Location: index.php");
        exit;
    }

    try {
        // Busca o usuário no banco
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica se achou e se a senha está correta
        if ($usuario && $senha === $usuario['senha']) {
            
            // --- CRIANDO AS SESSÕES (INCLUSIVE O NÍVEL) ---
            $_SESSION['usuario_id']   = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['empresa_id']   = $usuario['empresa_id'];
            $_SESSION['nivel']        = $usuario['nivel'];

            // --- MAGICA DO REDIRECIONAMENTO POR NÍVEL ---
            if ($usuario['nivel'] === 'super_admin') {
                // Se for o dono do sistema (Você), vai para o painel geral
                header("Location: admin/dashboard.php");
            } else {
                // Se for cliente (admin_cliente) ou funcionário (usuario), vai pro CRM
                header("Location: app/kanban.php");
            }
            exit;
            
        } else {
            setFlash('login', 'E-mail ou senha incorretos!', 'danger');
            header("Location: index.php");
            exit;
        }
    } catch (Exception $e) {
        setFlash('login', 'Erro no banco de dados: ' . $e->getMessage(), 'danger');
        header("Location: index.php");
        exit;
    }
} else {
    // Se alguém tentar acessar auth.php direto pela URL sem mandar POST
    header("Location: index.php"); 
    exit;
}
?>