<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Validação de Segurança
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['empresa_id'])) {
    header("Location: ../index.php");
    exit;
}

$empresa_id = $_SESSION['empresa_id'];
$usuario_nome = $_SESSION['usuario_nome'];

// --- 1. BUSCA OS PIPES PARA O FILTRO ---
$stmtPipes = $pdo->prepare("SELECT id, nome FROM pipes WHERE empresa_id = :empresa_id ORDER BY id ASC");
$stmtPipes->execute([':empresa_id' => $empresa_id]);
$meusPipes = $stmtPipes->fetchAll(PDO::FETCH_ASSOC);

// Define qual pipe está sendo visualizado no gráfico (Pega da URL ou usa o 1º da lista)
$pipe_ativo_id = isset($_GET['pipe_id']) ? (int)$_GET['pipe_id'] : ($meusPipes[0]['id'] ?? 0);

// --- 2. MÉTRICAS GLOBAIS (Toda a empresa) ---
$stmtTotal = $pdo->prepare("
    SELECT COUNT(c.id) as total 
    FROM cards c 
    JOIN phases p ON c.phase_id = p.id 
    JOIN pipes pi ON p.pipe_id = pi.id 
    WHERE pi.empresa_id = :empresa_id
");
$stmtTotal->execute([':empresa_id' => $empresa_id]);
$totalLeads = $stmtTotal->fetchColumn();

// --- 3. O FUNIL ESPECÍFICO (Filtrado pelo Pipe escolhido) ---
$dadosFunil = [];
if ($pipe_ativo_id > 0) {
    $stmtFunil = $pdo->prepare("
        SELECT p.nome as fase_nome, COUNT(c.id) as total_leads 
        FROM phases p 
        LEFT JOIN cards c ON c.phase_id = p.id 
        WHERE p.pipe_id = :pipe_id 
        GROUP BY p.id 
        ORDER BY p.ordem ASC
    ");
    $stmtFunil->execute([':pipe_id' => $pipe_ativo_id]);
    $dadosFunil = $stmtFunil->fetchAll(PDO::FETCH_ASSOC);
}

// --- 4. OS ÚLTIMOS 5 LEADS GLOBAIS ---
$stmtRecentes = $pdo->prepare("
    SELECT c.id, c.titulo, c.data_criacao, p.nome as fase_nome, pi.nome as pipe_nome 
    FROM cards c 
    JOIN phases p ON c.phase_id = p.id 
    JOIN pipes pi ON p.pipe_id = pi.id 
    WHERE pi.empresa_id = :empresa_id 
    ORDER BY c.id DESC 
    LIMIT 5
");
$stmtRecentes->execute([':empresa_id' => $empresa_id]);
$leadsRecentes = $stmtRecentes->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
require_once '../includes/sidebar-client.php';
?>

<style>
    .dash-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .dash-card { background: #1a1d21; padding: 25px; border-radius: 8px; border: 1px solid #333; position: relative; overflow: hidden; }
    .dash-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--roxo-grow); }
    .dash-card.verde::before { background: var(--verde-grow); }
    .dash-card.azul::before { background: #33b5e5; }
    .dash-title { color: #9ca3af; font-size: 13px; text-transform: uppercase; font-weight: bold; margin-bottom: 10px; }
    .dash-numero { color: white; font-size: 36px; font-weight: bold; }
    
    .funil-container { background: #1a1d21; padding: 25px; border-radius: 8px; border: 1px solid #333; margin-bottom: 30px; }
    .funil-bar-bg { background: #262a30; height: 12px; border-radius: 6px; width: 100%; overflow: hidden; margin-top: 8px; }
    .funil-bar-fill { background: var(--roxo-grow); height: 100%; border-radius: 6px; transition: width 1s ease-in-out; }
    .funil-item { margin-bottom: 15px; }

    .tabela-recentes { width: 100%; border-collapse: collapse; }
    .tabela-recentes th, .tabela-recentes td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #333; color: #ccc; }
    .tabela-recentes th { color: var(--roxo-grow); font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #333; }
    .tabela-recentes tr:hover { background: #262a30; }
    .badge-fase { background: #333; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; color: white; }
    
    .filtro-select { background: #262a30; color: white; border: 1px solid #444; padding: 8px 15px; border-radius: 4px; outline: none; font-size: 14px; cursor: pointer; }
    .filtro-select:focus { border-color: var(--roxo-grow); }
</style>

<div class="main-content">

    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
        <div>
            <h1 style="color: var(--roxo-grow); margin-bottom: 5px;">Visão Geral</h1>
            <p style="color: #9ca3af;">Bem-vindo de volta, <?php echo htmlspecialchars($usuario_nome); ?>.</p>
        </div>
        
        <?php if(count($meusPipes) > 0): ?>
        <form method="GET" action="dashboard.php">
            <label style="color: #9ca3af; font-size: 12px; font-weight: bold; display: block; margin-bottom: 5px;">Analisar Funil:</label>
            <select name="pipe_id" class="filtro-select" onchange="this.form.submit()">
                <?php foreach($meusPipes as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php if($p['id'] == $pipe_ativo_id) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($p['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>

    <div class="dash-grid">
        <div class="dash-card">
            <div class="dash-title">Total de Leads (Geral)</div>
            <div class="dash-numero"><?php echo $totalLeads; ?></div>
        </div>
        
        <div class="dash-card verde">
            <div class="dash-title">Taxa de Resposta Estimada</div>
            <div class="dash-numero">
                <?php echo ($totalLeads > 0) ? '68%' : '0%'; ?>
                <span style="font-size: 14px; color: #666; font-weight: normal;">(Média)</span>
            </div>
        </div>

        <div class="dash-card azul">
            <div class="dash-title">Status da Integração</div>
            <div class="dash-numero" style="font-size: 24px; color: var(--verde-grow); margin-top: 10px;">
                ● Online
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        
        <div class="funil-container">
            <h3 style="color: white; margin-bottom: 20px; font-size: 16px;">Raio-X: <?php 
                // Acha o nome do pipe ativo para exibir no título
                $nomeAtivo = "Sem Funil";
                foreach($meusPipes as $p) { if($p['id'] == $pipe_ativo_id) $nomeAtivo = $p['nome']; }
                echo htmlspecialchars($nomeAtivo);
            ?></h3>
            
            <?php if(count($dadosFunil) == 0): ?>
                <p style="color: #666;">Nenhum dado encontrado para este funil.</p>
            <?php else: ?>
                <?php 
                $maxLeads = 0;
                foreach($dadosFunil as $f) { if($f['total_leads'] > $maxLeads) $maxLeads = $f['total_leads']; }
                if($maxLeads == 0) $maxLeads = 1;

                foreach($dadosFunil as $fase): 
                    $porcentagem = ($fase['total_leads'] / $maxLeads) * 100;
                ?>
                <div class="funil-item">
                    <div style="display: flex; justify-content: space-between; color: #ccc; font-size: 13px; font-weight: bold;">
                        <span><?php echo htmlspecialchars($fase['fase_nome']); ?></span>
                        <span><?php echo $fase['total_leads']; ?> leads</span>
                    </div>
                    <div class="funil-bar-bg">
                        <div class="funil-bar-fill" style="width: <?php echo $porcentagem; ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="funil-container">
            <h3 style="color: white; margin-bottom: 20px; font-size: 16px;">Últimos Leads Recebidos</h3>
            
            <div style="overflow-x: auto;">
                <table class="tabela-recentes">
                    <thead>
                        <tr>
                            <th>Nome do Lead</th>
                            <th>Fase Atual</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($leadsRecentes) == 0): ?>
                            <tr><td colspan="3" style="text-align: center; color: #666; padding: 20px;">Nenhum lead recebido ainda.</td></tr>
                        <?php endif; ?>

                        <?php foreach($leadsRecentes as $lead): ?>
                        <tr>
                            <td style="font-weight: bold; color: white;">
                                <?php echo htmlspecialchars($lead['titulo']); ?>
                                <div style="font-size: 10px; color: #666; font-weight: normal; margin-top: 2px;">Processo: <?php echo htmlspecialchars($lead['pipe_nome']); ?></div>
                            </td>
                            <td><span class="badge-fase"><?php echo htmlspecialchars($lead['fase_nome']); ?></span></td>
                            <td style="font-size: 12px;"><?php echo date('d/m/Y H:i', strtotime($lead['data_criacao'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<?php require_once '../includes/footer.php'; ?>