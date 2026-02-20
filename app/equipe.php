<?php
// Exibir erros na tela provisoriamente para facilitar
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// 1. Segurança Geral
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['empresa_id'])) {
    header("Location: ../index.php");
    exit;
}

// 2. Segurança de Nível (Tranca Vendedores)
if (!isset($_SESSION['nivel']) || ($_SESSION['nivel'] !== 'admin_cliente' && $_SESSION['nivel'] !== 'super_admin')) {
    header("Location: kanban.php");
    exit;
}

$empresa_id = $_SESSION['empresa_id'];
$erro = '';
$sucesso = '';

// --- 1. LÓGICA DE CADASTRO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_membro'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);
    $nivel = 'usuario'; // Nível padrão

    if (empty($nome) || empty($email) || empty($senha)) {
        $erro = "Preencha todos os campos!";
    } else {
        try {
            // Verifica se o email já existe
            $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
            $check->execute([':email' => $email]);
            
            if ($check->rowCount() > 0) {
                $erro = "Este e-mail já está em uso por outro usuário.";
            } else {
                // Insere vinculando à empresa
                $sql = "INSERT INTO usuarios (empresa_id, nome, email, senha, nivel) VALUES (:empresa, :nome, :email, :senha, :nivel)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':empresa' => $empresa_id, 
                    ':nome' => $nome, 
                    ':email' => $email, 
                    ':senha' => $senha, 
                    ':nivel' => $nivel
                ]);
                
                $sucesso = "Membro adicionado com sucesso!";
            }
        } catch (PDOException $e) {
            // SE O BANCO RECLAMAR DE ALGO, CAI AQUI E NÃO DERRUBA O SITE!
            $erro = "Erro no Banco de Dados: " . $e->getMessage();
        } catch (Exception $e) {
            $erro = "Erro Crítico: " . $e->getMessage();
        }
    }
}

// --- 2. LÓGICA DE EXCLUSÃO ---
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    try {
        $delStmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id AND empresa_id = :empresa_id");
        $delStmt->execute([':id' => $del_id, ':empresa_id' => $empresa_id]);
        header("Location: equipe.php");
        exit;
    } catch (PDOException $e) {
        $erro = "Erro ao excluir: " . $e->getMessage();
    }
}

// --- 3. BUSCA A EQUIPE DA EMPRESA ---
try {
    $stmtEquipe = $pdo->prepare("SELECT * FROM usuarios WHERE empresa_id = :empresa_id ORDER BY id DESC");
    $stmtEquipe->execute([':empresa_id' => $empresa_id]);
    $equipe = $stmtEquipe->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar equipe: " . $e->getMessage();
    $equipe = [];
}

require_once '../includes/header.php';
require_once '../includes/sidebar-client.php';
?>

<div class="main-content">
    <h1 style="color: var(--roxo-grow); margin-bottom: 20px;">Minha Equipe</h1>

    <?php if ($erro): ?>
        <div style="background: #ff4444; color: white; padding: 15px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; word-break: break-all;">
            <?php echo $erro; ?>
        </div>
    <?php endif; ?>
    <?php if ($sucesso): ?>
        <div style="background: #00C851; color: white; padding: 15px; border-radius: 5px; margin-bottom: 15px; font-weight: bold;">
            <?php echo $sucesso; ?>
        </div>
    <?php endif; ?>

    <div style="background: #1a1d21; padding: 20px; border-radius: 8px; border: 1px solid #333; margin-bottom: 30px;">
        <h3 style="color: white; margin-bottom: 15px;">Adicionar Novo Vendedor</h3>
        
        <form method="POST" action="equipe.php" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label style="color: #ccc; font-size: 12px; display: block; margin-bottom: 5px;">Nome Completo</label>
                <input type="text" name="nome" required style="width: 100%; padding: 10px; background: #262a30; border: 1px solid #444; color: white; border-radius: 4px;">
            </div>
            
            <div style="flex: 1; min-width: 200px;">
                <label style="color: #ccc; font-size: 12px; display: block; margin-bottom: 5px;">E-mail de Acesso</label>
                <input type="email" name="email" required style="width: 100%; padding: 10px; background: #262a30; border: 1px solid #444; color: white; border-radius: 4px;">
            </div>

            <div style="flex: 1; min-width: 150px;">
                <label style="color: #ccc; font-size: 12px; display: block; margin-bottom: 5px;">Senha</label>
                <input type="password" name="senha" required style="width: 100%; padding: 10px; background: #262a30; border: 1px solid #444; color: white; border-radius: 4px;">
            </div>

            <button type="submit" name="add_membro" style="background: var(--roxo-grow); color: white; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; height: 40px;">
                + Adicionar
            </button>
        </form>
    </div>

    <div style="background: #1a1d21; border-radius: 8px; border: 1px solid #333; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; color: white;">
            <thead>
                <tr style="background: #262a30; border-bottom: 1px solid #444;">
                    <th style="padding: 15px;">Nome</th>
                    <th style="padding: 15px;">E-mail</th>
                    <th style="padding: 15px;">Nível</th>
                    <th style="padding: 15px; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($equipe) == 0): ?>
                    <tr><td colspan="4" style="padding: 15px; text-align: center; color: #666;">Nenhum membro cadastrado.</td></tr>
                <?php endif; ?>

                <?php foreach($equipe as $membro): ?>
                <tr style="border-bottom: 1px solid #333;">
                    <td style="padding: 15px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 30px; height: 30px; border-radius: 50%; background: var(--roxo-grow); display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                <?php echo strtoupper(substr($membro['nome'], 0, 1)); ?>
                            </div>
                            <?php echo $membro['nome']; ?>
                        </div>
                    </td>
                    <td style="padding: 15px; color: #ccc;"><?php echo $membro['email']; ?></td>
                    <td style="padding: 15px;">
                        <span style="background: #333; padding: 4px 8px; border-radius: 4px; font-size: 12px; color: #aaa;">
                            <?php echo ucfirst($membro['nivel']); ?>
                        </span>
                    </td>
                    <td style="padding: 15px; text-align: center;">
                        <?php if($membro['id'] != $_SESSION['usuario_id']): ?>
                            <a href="equipe.php?delete=<?php echo $membro['id']; ?>" onclick="return confirm('Excluir este vendedor? Ele perderá o acesso.')" style="color: #ff4444; text-decoration: none; font-size: 14px;">🗑️ Remover</a>
                        <?php else: ?>
                            <span style="color: #666; font-size: 12px;">Você</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>