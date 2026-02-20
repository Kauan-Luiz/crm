<?php
$paginaAtual = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* Reaproveitando o CSS da Sidebar do Admin */
    .d-flex { display: flex; }
    
    .sidebar {
        width: 260px;
        background-color: var(--cinza-dark);
        border-right: 1px solid var(--cinza-border);
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        display: flex;
        flex-direction: column;
        z-index: 1000;
    }

    .sidebar-header {
        padding: 30px;
        text-align: center;
        border-bottom: 1px solid var(--cinza-border);
    }

    .sidebar-header h2 {
        color: var(--roxo-grow);
        font-size: 24px;
        letter-spacing: 2px;
        text-transform: uppercase;
    }

    .sidebar-menu { padding: 20px; flex: 1; }

    .menu-item {
        display: block;
        padding: 12px 15px;
        margin-bottom: 8px;
        color: var(--texto-muted);
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-weight: 500;
        border-left: 3px solid transparent;
    }

    .menu-item:hover {
        background-color: var(--cinza-medium);
        color: white;
        padding-left: 20px;
    }

    .menu-item.active {
        background-color: rgba(80, 0, 108, 0.2);
        color: var(--roxo-grow);
        border-left-color: var(--roxo-grow);
    }

    .sidebar-footer {
        padding: 20px;
        border-top: 1px solid var(--cinza-border);
    }
    
    .btn-logout {
        display: block; width: 100%; text-align: center; padding: 10px;
        background: transparent; border: 1px solid #ff4444; color: #ff4444;
        border-radius: 6px; text-decoration: none; transition: 0.3s;
    }
    .btn-logout:hover { background: #ff4444; color: white; }

    /* Importante para empurrar o conteúdo */
    .main-content {
        margin-left: 260px;
        padding: 40px;
        width: calc(100% - 260px);
    }
</style>

<nav class="sidebar">
    <div class="sidebar-header">
        <h2>CRM</h2> 
    </div>

    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo $paginaAtual == 'dashboard.php' ? 'active' : ''; ?>">
            📊 Visão Geral
        </a>
        
        <a href="kanban.php" class="menu-item <?php echo $paginaAtual == 'kanban.php' ? 'active' : ''; ?>">
            🚀 Meus Pipes (Processos)
        </a>

        <?php if (isset($_SESSION['nivel']) && ($_SESSION['nivel'] === 'admin_cliente' || $_SESSION['nivel'] === 'super_admin')): ?>
            <a href="equipe.php" class="menu-item <?php echo $paginaAtual == 'equipe.php' ? 'active' : ''; ?>">
                👥 Minha Equipe
            </a>
        <?php endif; ?>

        <a href="#" class="menu-item" style="opacity: 0.5;">
            ✅ Minhas Tarefas (Breve)
        </a>
        
        <a href="#" class="menu-item" style="opacity: 0.5;">
            ⚙️ Configurações
        </a>
    </div>
</nav>