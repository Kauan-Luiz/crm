<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verificação de segurança
if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'super_admin') {
    header("Location: ../index.php");
    exit;
}

require_once '../includes/header.php'; // Carrega CSS global
require_once '../includes/sidebar.php'; // Carrega o Menu Lateral
?>

<div class="main-content">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h1 style="color: var(--roxo-grow);">Visão Geral</h1>
            <p style="color: var(--texto-muted);">Bem-vindo ao painel mestre, <?php echo $_SESSION['usuario_nome']; ?>.</p>
        </div>
        <button class="btn btn-success">+ Nova Instância</button>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        
        <div class="card">
            <h3 style="color: var(--texto-muted); font-size: 14px;">CLIENTES ATIVOS</h3>
            <p style="font-size: 32px; font-weight: bold; margin-top: 10px;">
                <?php 
                    // Contagem rápida para dar vida ao dashboard
                    echo $pdo->query("SELECT count(*) FROM empresas WHERE status='ativo'")->fetchColumn(); 
                ?>
            </p>
        </div>

        <div class="card">
            <h3 style="color: var(--texto-muted); font-size: 14px;">USUÁRIOS TOTAIS</h3>
            <p style="font-size: 32px; font-weight: bold; margin-top: 10px;">
                 <?php echo $pdo->query("SELECT count(*) FROM usuarios")->fetchColumn(); ?>
            </p>
        </div>

        <div class="card" style="border-color: var(--roxo-grow);">
            <h3 style="color: var(--texto-muted); font-size: 14px;">FATURAMENTO (ESTIMADO)</h3>
            <p style="font-size: 32px; font-weight: bold; margin-top: 10px; color: var(--verde-grow);">R$ 0,00</p>
        </div>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>