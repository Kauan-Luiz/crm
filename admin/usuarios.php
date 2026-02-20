<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php'; // Sistema de mensagens

// Proteção: Só Super Admin entra
if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'super_admin') {
    header("Location: ../index.php");
    exit;
}

// 1. Processar Formulário de Cadastro
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_usuario'])) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha']; 
    $empresa_id = $_POST['empresa_id'];
    $nivel = $_POST['nivel'];

    // Verifica se e-mail já existe
    $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
    $check->bindParam(':email', $email);
    $check->execute();

    if ($check->rowCount() > 0) {
        setFlash('global', 'Este e-mail já está cadastrado!', 'error');
    } else {
        $sql = "INSERT INTO usuarios (nome, email, senha, empresa_id, nivel) VALUES (:nome, :email, :senha, :empresa_id, :nivel)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senha);
        $stmt->bindParam(':empresa_id', $empresa_id);
        $stmt->bindParam(':nivel', $nivel);
        
        if ($stmt->execute()) {
            setFlash('global', 'Usuário criado com sucesso!');
            header("Location: usuarios.php");
            exit;
        }
    }
}

// 2. Buscar Empresas para o Select
$empresas = $pdo->query("SELECT id, nome_empresa FROM empresas WHERE status = 'ativo'")->fetchAll(PDO::FETCH_ASSOC);

// 3. Listar Usuários
$sql_users = "SELECT u.*, e.nome_empresa 
              FROM usuarios u 
              LEFT JOIN empresas e ON u.empresa_id = e.id 
              ORDER BY u.id DESC";
$usuarios = $pdo->query($sql_users)->fetchAll(PDO::FETCH_ASSOC);

// --- INICIO DA ESTRUTURA VISUAL ---
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<style>
    /* CSS ESPECÍFICO DESTA PÁGINA */
    
    .card-form { 
        background: #1a1d21; 
        padding: 25px; 
        border-radius: 8px; 
        border: 1px solid #464646; 
        margin-bottom: 30px; 
        display: grid; 
        grid-template-columns: 1fr 1fr; /* Divide em 2 colunas */
        gap: 20px;
    }
    
    .full-width { grid-column: span 2; }

    label { display: block; margin-bottom: 8px; color: #d1d5db; font-size: 14px; }
    
    input, select { 
        width: 100%; 
        padding: 12px; 
        border-radius: 5px; 
        border: 1px solid #464646; 
        background: #262a30; 
        color: white; 
        outline: none;
    }
    
    input:focus, select:focus { border-color: var(--roxo-grow); }

    .btn-add { 
        background: var(--verde-grow); 
        color: white; 
        border: none; 
        padding: 12px; 
        border-radius: 5px; 
        cursor: pointer; 
        font-weight: bold;
        grid-column: span 2; 
        margin-top: 10px;
        transition: 0.3s;
    }
    .btn-add:hover { filter: brightness(1.1); }

    /* Tabela */
    table { width: 100%; border-collapse: collapse; background: #1a1d21; border-radius: 8px; overflow: hidden; }
    th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #464646; }
    th { background: var(--roxo-grow); color: white; text-transform: uppercase; font-size: 13px; letter-spacing: 1px; }
    tr:hover { background: #262a30; }

    /* Badges (Etiquetas) */
    .badge { padding: 5px 10px; border-radius: 4px; font-size: 11px; text-transform: uppercase; font-weight: bold; }
    .badge-admin { background: rgba(80, 0, 108, 0.3); color: #d8b4fe; border: 1px solid var(--roxo-grow); }
    .badge-user { background: #374151; color: #9ca3af; border: 1px solid #4b5563; }
    .badge-super { background: rgba(255, 215, 0, 0.2); color: #ffd700; border: 1px solid #ffd700; }
</style>

<div class="main-content">

    <?php echo getFlash('global'); ?>

    <h1 style="color: var(--roxo-grow); margin-bottom: 20px;">Gestão de Usuários</h1>

    <form method="POST" class="card-form">
        <div>
            <label>Nome do Usuário</label>
            <input type="text" name="nome" placeholder="Ex: João Silva" required>
        </div>
        <div>
            <label>E-mail de Acesso</label>
            <input type="email" name="email" placeholder="email@cliente.com" required>
        </div>
        <div>
            <label>Senha Inicial</label>
            <input type="text" name="senha" placeholder="Crie uma senha forte" required>
        </div>
        <div>
            <label>Pertence à Empresa:</label>
            <select name="empresa_id" required>
                <option value="">Selecione...</option>
                <?php foreach ($empresas as $emp): ?>
                    <option value="<?php echo $emp['id']; ?>"><?php echo $emp['nome_empresa']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="full-width">
            <label>Nível de Acesso:</label>
            <select name="nivel" required>
                <option value="admin_cliente">Admin do Cliente (Pode criar pipes e configurar)</option>
                <option value="usuario">Usuário Comum (Apenas move cards)</option>
            </select>
        </div>
        <button type="submit" name="add_usuario" class="btn-add">Criar Usuário</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Empresa</th>
                <th>Nível</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $user): ?>
            <tr>
                <td><?php echo $user['nome']; ?></td>
                <td><?php echo $user['email']; ?></td>
                <td>
                    <?php echo $user['nome_empresa'] ? $user['nome_empresa'] : '<strong style="color:var(--verde-grow)">SISTEMA (Super Admin)</strong>'; ?>
                </td>
                <td>
                    <?php 
                    if ($user['nivel'] == 'super_admin') echo '<span class="badge badge-super">Mestre</span>';
                    elseif ($user['nivel'] == 'admin_cliente') echo '<span class="badge badge-admin">Admin Cliente</span>';
                    else echo '<span class="badge badge-user">Usuário</span>';
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>

<?php require_once '../includes/footer.php'; ?>