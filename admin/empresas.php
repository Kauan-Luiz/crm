<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php'; // Para usar o sistema de mensagens

// Proteção: Só Super Admin entra
if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'super_admin') {
    header("Location: ../index.php");
    exit;
}

// Lógica para Cadastrar Empresa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_empresa'])) {
    $nome = $_POST['nome_empresa'];
    
    // GERA O TOKEN ÚNICO (Prefixo grow_ + código aleatório)
    $token = 'grow_' . bin2hex(random_bytes(8)); 
    
    $sql = "INSERT INTO empresas (nome_empresa, api_token) VALUES (:nome, :token)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':token', $token);
    
    if($stmt->execute()) {
        setFlash('global', 'Empresa cadastrada! Token gerado com sucesso.');
        header("Location: empresas.php");
        exit;
    }
}

// Busca todas as empresas
$empresas = $pdo->query("SELECT * FROM empresas ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// --- INICIO DA ESTRUTURA VISUAL ---
require_once '../includes/header.php';  // Carrega CSS e estrutura inicial
require_once '../includes/sidebar.php'; // Carrega o Menu Lateral
?>

<style>
    /* CSS ESPECÍFICO DESTA PÁGINA */
    /* Nota: O background e fonte global já vêm do header.php */
    
    .card-form { 
        background: #1a1d21; 
        padding: 25px; 
        border-radius: 8px; 
        border: 1px solid #464646; 
        margin-bottom: 30px; 
        display: flex;
        gap: 10px;
        align-items: center;
    }

    input { 
        flex: 1; /* Ocupa todo espaço disponível */
        padding: 12px; 
        border-radius: 5px; 
        border: 1px solid #464646; 
        background: #262a30; 
        color: white; 
        outline: none;
    }
    
    input:focus { border-color: var(--roxo-grow); }

    .btn-add { 
        background: var(--verde-grow); /* Usa a variável global */
        color: white; 
        border: none; 
        padding: 12px 25px; 
        border-radius: 5px; 
        cursor: pointer; 
        font-weight: bold;
        transition: 0.3s;
    }

    .btn-add:hover { filter: brightness(1.1); }

    /* Tabela */
    table { width: 100%; border-collapse: collapse; background: #1a1d21; border-radius: 8px; overflow: hidden; margin-top: 20px; }
    th, td { padding: 15px; text-align: left; border-bottom: 1px solid #464646; }
    th { background: var(--roxo-grow); color: white; text-transform: uppercase; font-size: 14px; letter-spacing: 1px; }
    tr:hover { background: #262a30; }
    
    .status-ativo { color: var(--verde-grow); font-weight: bold; }
    .status-suspenso { color: #ff4444; font-weight: bold; }
</style>

<div class="main-content">

    <?php echo getFlash('global'); ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="color: var(--roxo-grow);">Gestão de Clientes</h1>
    </div>

    <div class="card-form">
        <form method="POST" style="width: 100%; display: flex; gap: 10px;">
            <input type="text" name="nome_empresa" placeholder="Nome da Nova Empresa Cliente" required>
            <button type="submit" name="add_empresa" class="btn-add">+ Cadastrar Empresa</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome da Empresa</th>
                <th>Token API (Webhook)</th> <th>Status</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($empresas as $emp): ?>
            <tr>
                <td>#<?php echo $emp['id']; ?></td>
                <td><?php echo $emp['nome_empresa']; ?></td>
                
                <td>
                    <code style="background: #333; padding: 4px; border-radius: 4px; color: #fab1a0;">
                        <?php echo $emp['api_token'] ? $emp['api_token'] : 'Sem Token'; ?>
                    </code>
                </td>
                
                <td class="<?php echo ($emp['status'] == 'ativo') ? 'status-ativo' : 'status-suspenso'; ?>">
                    <?php echo ucfirst($emp['status']); ?>
                </td>
                <td><?php echo date('d/m/Y', strtotime($emp['data_cadastro'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>

<?php require_once '../includes/footer.php'; ?>