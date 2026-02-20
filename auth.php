<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php'; // Importa as funções de mensagem

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['password'];

    // Busca usuário
    $sql = "SELECT * FROM usuarios WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && $senha === $usuario['senha']) {
        // Login Sucesso
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_nivel'] = $usuario['nivel'];
        $_SESSION['empresa_id'] = $usuario['empresa_id'];

        if ($usuario['nivel'] == 'super_admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: app/dashboard.php");
        }
        exit;
    } else {
        // Login Falhou: Usa nossa nova função
        setFlash('login', 'E-mail ou senha incorretos!', 'error');
        header("Location: index.php");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}