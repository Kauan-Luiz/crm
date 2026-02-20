<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificação de segurança (Apenas clientes logados)
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['empresa_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../includes/header.php';       // CSS Global
require_once '../includes/sidebar-client.php'; // Menu do Cliente
?>

<div class="main-content">
    
    <div style="margin-bottom: 30px;">
        <h1 style="color: var(--roxo-grow);">Olá, <?php echo $_SESSION['usuario_nome']; ?>!</h1>
        <p style="color: var(--texto-muted);">Bem-vindo ao workspace da sua empresa.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        
        <div class="card">
            <h3 style="color: var(--texto-muted); font-size: 14px;">PIPES ATIVOS</h3>
            <p style="font-size: 32px; font-weight: bold; margin-top: 10px;">0</p>
        </div>

        <div class="card">
            <h3 style="color: var(--texto-muted); font-size: 14px;">CARDS NA MINHA FILA</h3>
            <p style="font-size: 32px; font-weight: bold; margin-top: 10px;">0</p>
        </div>

        <div class="card" style="border: 1px dashed var(--roxo-grow); display: flex; align-items: center; justify-content: center; cursor: pointer;">
            <a href="kanban.php" style="text-decoration: none; color: var(--roxo-grow); font-weight: bold;">
                + Criar Novo Processo
            </a>
        </div>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>