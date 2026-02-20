<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Proteção corrigida: Só Super Admin entra
if (!isset($_SESSION['nivel']) || $_SESSION['nivel'] !== 'super_admin') {
    header("Location: ../index.php");
    exit;
}

// Lógica para Cadastrar Usuário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_usuario'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);
    $nivel = $_POST['nivel'];
    $empresa_id = !empty($_POST['empresa_id']) ? $_POST['empresa_id'] : null;

    // Verifica se email já existe
    $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
    $check->execute([':email' => $email]);

    if ($check->rowCount() > 0) {
        setFlash('global', 'Este e-mail já está cadastrado no sistema!', 'danger');
    } else {
        $sql = "INSERT INTO usuarios (empresa_id, nome, email, senha, nivel) VALUES (:empresa, :nome, :email, :senha, :nivel)";
        $stmt = $pdo->prepare($sql);
        if($stmt->execute([
            ':empresa' => $empresa_id,
            ':nome' => $nome,
            ':email' => $email,
            ':senha' => $senha,
            ':nivel' => $nivel
        ])) {
            setFlash('global', 'Usuário cadastrado com sucesso!');
            header("Location: usuarios.php");
            exit;
        }
    }
}

// Busca todos os usuários e o nome da empresa deles (usando LEFT JOIN)
$sqlUsuarios = "SELECT u.*, e.nome_empresa 
                FROM usuarios u 
                LEFT JOIN empresas e ON u.empresa_id = e.id 
                ORDER BY u.id DESC";
$usuarios = $pdo->query($sqlUsuarios)->fetchAll(PDO::FETCH_ASSOC);

// Busca a lista de empresas para o dropdown de cadastro
$empresasDropdown = $pdo->query("SELECT id, nome_empresa FROM empresas ORDER BY nome_empresa ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<style>
    .card-form { background: #1a1d21; padding: 25px; border-radius: 8px; border: 1px solid #464646; margin-bottom: 30px; }
    .input-row { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px; }
    .input-group { flex: 1; min-width: 200px; }
    label { color: #ccc; font-size: 12px; display: block; margin-bottom: 5px; }
    input, select { width: 100%; padding: 12px; border-radius: 5px; border: 1px solid #464646; background: #262a30; color: white; outline: none; }
    input:focus, select:focus { border-color: var(--roxo-grow); }
    .btn-add { background: var(--verde-grow); color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-weight: bold; width: 100%; }
    .btn-add:hover { filter: brightness(1.1); }
    table { width: 100%; border-collapse: collapse; background: #1a1d21; border-radius: 8px; overflow: hidden; margin-top: 20px; }
    th, td { padding: 15px; text-align: left; border-bottom: 1px solid #464646; color: white; }
    th { background: var(--roxo-grow); color: white; text-transform: uppercase; font-size: 14px; letter-spacing: 1px; }
    tr:hover { background: #262a30; }
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; background: #333; color: #ccc;}
</style>

<div class="main-content">
    <?php echo getFlash('global'); ?>

    <h1 style="color: var(--roxo-grow); margin-bottom: 20px;">Gestão de Usuários (Mestres)</h1>

    <div class="card-form">
        <form method="POST">
            <div class="input-row">
                <div class="input-group">
                    <label>Nome Completo</label>
                    <input type="text" name="nome" required>
                </div>
                <div class="input-group">
                    <label>E-mail</label>
                    <input type="email" name="email" required>
                </div>
                <div class="input-group">
                    <label>Senha</label>
                    <input type="text" name="senha" required>
                </div>
            </div>
            
            <div class="input-row">
                <div class="input-group">
                    <label>Nível de Acesso</label>
                    <select name="nivel" required>
                        <option value="admin_cliente">Admin Cliente (Dono da Agência)</option>
                        <option value="super_admin">Super Admin (Desenvolvedor)</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Vincular a qual Empresa?</label>
                    <select name="empresa_id">
                        <option value="">Nenhuma (Uso do Super Admin)</option>
                        <?php foreach($empresasDropdown as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"><?php echo $emp['nome_empresa']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" name="add_usuario" class="btn-add">+ Cadastrar Usuário</button>
                </div>
            </div>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Nível</th>
                <th>Empresa Vinculada</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usr): ?>
            <tr>
                <td>#<?php echo $usr['id']; ?></td>
                <td><?php echo $usr['nome']; ?></td>
                <td><?php echo $usr['email']; ?></td>
                <td><span class="badge"><?php echo strtoupper($usr['nivel']); ?></span></td>
                <td style="color: var(--verde-grow); font-weight: bold;">
                    <?php echo $usr['nome_empresa'] ? $usr['nome_empresa'] : 'Sem Empresa'; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>