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
$pipe_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

require_once '../includes/header.php';
require_once '../includes/sidebar-client.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<style>
    /* --- CSS GERAL E MODAL --- */
    .modal {
        display: none; position: fixed; z-index: 2000; 
        left: 0; top: 0; width: 100%; height: 100%; 
        background-color: rgba(0,0,0,0.8); backdrop-filter: blur(5px);
    }
    .modal-content {
        background-color: #1a1d21; margin: 5% auto; padding: 0;
        border: 1px solid #464646; border-radius: 10px;
        width: 90%; max-width: 600px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        animation: modalSlideIn 0.3s;
    }
    @keyframes modalSlideIn {
        from {transform: translateY(-50px); opacity: 0;}
        to {transform: translateY(0); opacity: 1;}
    }
    .modal-header {
        padding: 20px; border-bottom: 1px solid #333;
        display: flex; justify-content: space-between; align-items: center;
    }
    .modal-body { padding: 30px; max-height: 70vh; overflow-y: auto; }
    
    .close-btn { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
    .close-btn:hover { color: white; }

    /* Estilo dos Campos Editáveis */
    .campo-item {
        background: #262a30; padding: 12px; margin-bottom: 10px;
        border-radius: 6px; border-left: 3px solid var(--roxo-grow);
    }
    .campo-label { 
        font-size: 11px; text-transform: uppercase; color: #9ca3af; 
        margin-bottom: 4px; font-weight: bold; display: block;
    }
    .campo-input {
        background: transparent; border: none; color: white; width: 100%;
        font-size: 14px; border-bottom: 1px solid #444; padding: 4px 0;
        transition: 0.2s;
    }
    .campo-input:focus { border-bottom: 1px solid var(--verde-grow); outline: none; }
    
    .btn-wpp {
        background: #25D366; color: white; border-radius: 50%; width: 20px; height: 20px;
        display: inline-flex; align-items: center; justify-content: center; text-decoration: none;
        font-size: 10px; margin-left: 8px; vertical-align: middle;
    }
    
    .btn-novo-campo {
        background: var(--roxo-grow); border: none; color: white; 
        padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px;
        transition: 0.2s;
    }
    .btn-novo-campo:hover { filter: brightness(1.2); }

    /* Estilo para arrastar fases */
    .phase-header { cursor: grab; }
    .phase-header:active { cursor: grabbing; }

    /* Etiquetas (Tags) */
    .color-option {
        width: 30px; height: 30px; border-radius: 50%; cursor: pointer;
        border: 2px solid #333; transition: 0.2s;
    }
    .color-option:hover { transform: scale(1.1); border-color: white; }
    .config-container { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; background: #222; padding: 15px; border-radius: 8px; align-items: center; }
    .label-tag { font-size: 12px; color: #ccc; margin-right: 10px; font-weight: bold; }
    
    /* Select Personalizado */
    .select-responsavel {
        background: #333; color: white; border: 1px solid #555; padding: 6px 10px; border-radius: 4px; outline: none; cursor: pointer;
    }
    .select-responsavel:focus { border-color: var(--roxo-grow); }

    /* --- TIMELINE DO HISTÓRICO --- */
    .timeline { position: relative; padding-left: 20px; margin-top: 15px; }
    .timeline::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 2px; background: #333; }
    .timeline-item { position: relative; margin-bottom: 15px; }
    .timeline-item::before { content: ''; position: absolute; left: -24px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: var(--roxo-grow); border: 2px solid #1a1d21; }
    .timeline-date { font-size: 11px; color: #666; margin-bottom: 2px; }
    .timeline-text { font-size: 13px; color: #ccc; }
    .timeline-user { font-weight: bold; color: var(--verde-grow); }
</style>

<div class="main-content">

    <?php if (!$pipe_id): ?>
        <h1 style="color: var(--roxo-grow); margin-bottom: 20px;">Meus Processos</h1>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
            <?php
            $pipes = $pdo->query("SELECT * FROM pipes WHERE empresa_id = $empresa_id")->fetchAll(PDO::FETCH_ASSOC);
            
            if(count($pipes) == 0) echo "<p style='color:#666'>Nenhum pipe encontrado.</p>";

            foreach ($pipes as $pipe): 
            ?>
            <a href="kanban.php?id=<?php echo $pipe['id']; ?>" style="text-decoration: none;">
                <div class="card" style="transition: transform 0.2s; border-left: 5px solid var(--roxo-grow);">
                    <h3 style="color: white;"><?php echo $pipe['nome']; ?></h3>
                    <p style="color: var(--texto-muted); font-size: 13px; margin-top: 5px;">Abrir quadro</p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <?php
        // 1. Busca infos do Pipe
        $stmt = $pdo->prepare("SELECT * FROM pipes WHERE id = :id AND empresa_id = :empresa_id");
        $stmt->bindParam(':id', $pipe_id);
        $stmt->bindParam(':empresa_id', $empresa_id);
        $stmt->execute();
        $pipe = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pipe) { echo "<h2 style='color:red'>Pipe não encontrado.</h2>"; exit; }

        // 2. Busca Fases do Pipe
        $fases = $pdo->prepare("SELECT * FROM phases WHERE pipe_id = :pipe_id ORDER BY ordem ASC");
        $fases->bindParam(':pipe_id', $pipe_id);
        $fases->execute();
        $listaFases = $fases->fetchAll(PDO::FETCH_ASSOC);

        // BUSCA O TOKEN DA EMPRESA PARA O WEBHOOK
        $stmtEmpresa = $pdo->prepare("SELECT api_token FROM empresas WHERE id = :empresa_id");
        $stmtEmpresa->execute([':empresa_id' => $empresa_id]);
        $dadosEmpresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);
        $meuToken = $dadosEmpresa['api_token'] ?? '';
        
        // MONTA A URL DE PRODUÇÃO
        $urlWebhook = "https://growfastmarketing.com.br/grow-crm/api/webhook.php?token={$meuToken}&pipe_id={$pipe['id']}";

        // 3. Busca a Equipe (Usuários desta empresa para o Dropdown)
        $usuariosStmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE empresa_id = :empresa_id OR empresa_id IS NULL");
        $usuariosStmt->execute([':empresa_id' => $empresa_id]);
        $listaUsuarios = $usuariosStmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

       <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <h1 style="color: var(--roxo-grow); margin: 0;"><?php echo $pipe['nome']; ?></h1>
                
                <button onclick="copiarWebhook()" style="background: rgba(80, 0, 108, 0.2); border: 1px solid var(--roxo-grow); color: var(--roxo-grow); padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; transition: 0.2s;" title="Copiar link para receber leads">
                    🔗 Copiar Webhook
                </button>
            </div>
            
            <a href="kanban.php" class="btn" style="background: #333; color: white; font-size: 12px;">Voltar</a>
        </div>

        <div id="kanban-container" style="display: flex; gap: 15px; overflow-x: auto; padding-bottom: 20px; height: calc(100vh - 150px);">
            
            <?php foreach ($listaFases as $fase): ?>
            <div class="draggable-phase" data-id="<?php echo $fase['id']; ?>" style="min-width: 300px; width: 300px; background: #1a1d21; border-radius: 8px; border: 1px solid #333; display: flex; flex-direction: column;">
                
                <div class="phase-header" style="padding: 15px; border-bottom: 1px solid #333; font-weight: bold; color: var(--verde-grow); display:flex; justify-content:space-between; align-items:center; cursor: move;">
                    <span><?php echo $fase['nome']; ?></span>
                    
                    <div style="display: flex; gap: 5px;">
                        <button onclick="excluirFase('<?php echo $fase['id']; ?>')" title="Excluir esta Coluna"
                                style="background: transparent; border: 1px solid #444; color: #ff4444; width: 25px; height: 25px; border-radius: 4px; cursor: pointer; font-size: 10px; display: flex; align-items: center; justify-content: center;">
                            🗑️
                        </button>
                        <button onclick="adicionarCard('<?php echo $fase['id']; ?>')" title="Adicionar Card Manualmente" 
                                style="background: #333; border: none; color: white; width: 25px; height: 25px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                            +
                        </button>
                    </div>
                </div>

                <div class="kanban-coluna" data-phase-id="<?php echo $fase['id']; ?>" style="padding: 10px; flex: 1; overflow-y: auto; min-height: 50px;">
                    <?php
                    // --- SQL ATUALIZADO: Busca Etiqueta E Responsável (u.nome) ---
                    $sql = "SELECT c.*, cv.campo_valor as etiqueta_cor, u.nome as responsavel_nome 
                            FROM cards c 
                            LEFT JOIN card_values cv ON c.id = cv.card_id AND cv.campo_chave = 'etiqueta'
                            LEFT JOIN usuarios u ON c.responsavel_id = u.id
                            WHERE c.phase_id = :fase_id 
                            ORDER BY c.id DESC";

                    $cardsStmt = $pdo->prepare($sql);
                    $cardsStmt->execute([':fase_id' => $fase['id']]);
                    $cards = $cardsStmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($cards as $card):
                        $corFaixa = !empty($card['etiqueta_cor']) ? $card['etiqueta_cor'] : 'transparent';
                    ?>
                        <div class="kanban-card" onclick="abrirCard(<?php echo $card['id']; ?>)" data-card-id="<?php echo $card['id']; ?>" 
                            style="background: #262a30; padding: 15px; margin-bottom: 10px; border-radius: 6px; border: 1px solid #464646; cursor: grab; box-shadow: 0 2px 5px rgba(0,0,0,0.2); position: relative; overflow: hidden;">
                            
                            <div class="card-tag-visual" id="tag-visual-<?php echo $card['id']; ?>" style="position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background-color: <?php echo $corFaixa; ?>;"></div>
                            
                            <div style="font-weight: bold; color: white; margin-bottom: 8px; padding-left: 8px; padding-right: 25px;"><?php echo $card['titulo']; ?></div>
                            <div style="font-size: 11px; color: #9ca3af; padding-left: 8px;">#<?php echo $card['id']; ?></div>

                            <?php if(!empty($card['responsavel_nome'])): 
                                $inicial = strtoupper(substr($card['responsavel_nome'], 0, 1));
                            ?>
                                <div title="Responsável: <?php echo $card['responsavel_nome']; ?>" 
                                     style="position: absolute; right: 10px; bottom: 10px; width: 22px; height: 22px; border-radius: 50%; background: var(--roxo-grow); color: white; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold;">
                                    <?php echo $inicial; ?>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div style="min-width: 300px; width: 300px; display: flex; align-items: flex-start;">
                <button onclick="adicionarFase()" style="width: 100%; padding: 15px; background: rgba(255,255,255,0.05); border: 2px dashed #444; color: #999; border-radius: 8px; cursor: pointer; text-align: left; font-weight: bold;">
                    + Adicionar nova fase
                </button>
            </div>

        </div>
    <?php endif; ?>

</div>

<div id="cardModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle" style="color: var(--verde-grow); font-size: 18px;">Carregando...</h2>
            <div style="display:flex; gap:10px; align-items:center;">
                <button onclick="adicionarCampoManual()" class="btn-novo-campo">+ Campo</button>
                <button onclick="excluirCardAtual()" title="Excluir Card" style="background:transparent; border:1px solid #ff4444; color:#ff4444; width:30px; height:30px; border-radius:4px; cursor:pointer;">🗑️</button>
                <span class="close-btn" onclick="fecharModal()">&times;</span>
            </div>
        </div>
        <div class="modal-body">
            
            <div class="config-container">
                
                <div style="display:flex; align-items:center; margin-right: 20px;">
                    <span class="label-tag">👤 Responsável:</span>
                    <select id="selectResponsavel" class="select-responsavel" onchange="salvarResponsavel(this.value)">
                        <option value="">Ninguém</option>
                        <?php if(isset($listaUsuarios)): ?>
                            <?php foreach($listaUsuarios as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo $user['nome']; ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div style="display:flex; align-items:center;">
                    <span class="label-tag">🎨 Etiqueta:</span>
                    <div class="color-option" style="background-color: #ff4444;" onclick="salvarEtiqueta('#ff4444')" title="Urgente"></div>
                    <div class="color-option" style="background-color: #ffbb33;" onclick="salvarEtiqueta('#ffbb33')" title="Atenção"></div>
                    <div class="color-option" style="background-color: #00C851;" onclick="salvarEtiqueta('#00C851')" title="Bom"></div>
                    <div class="color-option" style="background-color: #33b5e5;" onclick="salvarEtiqueta('#33b5e5')" title="Frio"></div>
                    <div class="color-option" style="background-color: transparent; border: 2px dashed #666;" onclick="salvarEtiqueta('')" title="Sem etiqueta"></div>
                </div>

            </div>

            <div style="margin-bottom: 20px; color: #666; font-size: 12px;">
                Criado em: <span id="modalData">--/--</span>
            </div>
            
            <h3 style="color: var(--roxo-grow); font-size: 14px; margin-bottom: 15px; text-transform: uppercase;">Dados do Cliente</h3>
            <div id="modalCamposArea"></div>

            <h3 style="color: var(--roxo-grow); font-size: 14px; margin-top: 30px; margin-bottom: 15px; text-transform: uppercase;">Histórico de Atividades</h3>
            <div id="modalHistoricoArea" class="timeline"></div>
        </div>
    </div>
</div>

<script>
    // --- 1. Lógica do Drag & Drop de CARDS ---
    const colunas = document.querySelectorAll('.kanban-coluna');
    colunas.forEach(coluna => {
        new Sortable(coluna, {
            group: 'cards', animation: 150, ghostClass: 'blue-background-class', 
            onEnd: function (evt) {
                const itemEl = evt.item;
                const novaColuna = evt.to;
                if (evt.from !== evt.to) {
                    fetch('../api/move_card.php', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ card_id: itemEl.getAttribute('data-card-id'), new_phase_id: novaColuna.getAttribute('data-phase-id') })
                    });
                }
            }
        });
    });

    // --- 2. Lógica do Drag & Drop de FASES ---
    const containerFases = document.getElementById('kanban-container');
    if (containerFases) {
        new Sortable(containerFases, {
            animation: 150, handle: '.phase-header', draggable: '.draggable-phase',
            onEnd: function (evt) {
                const ordenados = Array.from(containerFases.querySelectorAll('.draggable-phase')).map(el => el.getAttribute('data-id'));
                fetch('../api/reorder_phases.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ordem: ordenados })
                });
            }
        });
    }

    // --- 3. Lógica do Modal Inteligente ---
    let cardAtualId = null;

    function abrirCard(id) {
        cardAtualId = id;
        const modal = document.getElementById('cardModal');
        const titleEl = document.getElementById('modalTitle');
        const dataEl = document.getElementById('modalData');
        const areaCampos = document.getElementById('modalCamposArea');
        const selectResp = document.getElementById('selectResponsavel');
        const areaHistorico = document.getElementById('modalHistoricoArea'); // Variável da Timeline

        modal.style.display = 'block';
        areaCampos.innerHTML = '<div style="text-align:center; padding:20px; color:#666">Buscando informações...</div>';
        areaHistorico.innerHTML = ''; // Limpa antes de carregar

        fetch('../api/get_card.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if(data.erro) { alert(data.erro); return; }

                titleEl.innerText = data.card.titulo;
                dataEl.innerText = new Date(data.card.data_criacao).toLocaleString('pt-BR');
                
                // Preenche o Select do Responsável
                selectResp.value = data.card.responsavel_id || "";

                areaCampos.innerHTML = ''; 
                
                if (data.valores.length > 0) {
                    data.valores.forEach(item => {
                        if(item.campo_chave !== 'etiqueta') {
                            criarLinhaCampo(item.campo_chave, item.campo_valor);
                        }
                    });
                } else {
                    areaCampos.innerHTML = '<p id="msgVazio" style="color:#666; font-style:italic;">Nenhum dado extra.</p>';
                }

                // --- MONTA A TIMELINE DO HISTÓRICO ---
                if (data.historico && data.historico.length > 0) {
                    data.historico.forEach(hist => {
                        const dataFormatada = new Date(hist.data_hora).toLocaleString('pt-BR');
                        const userNome = hist.usuario_nome ? hist.usuario_nome : 'Sistema/Webhook';

                        const htmlHist = `
                            <div class="timeline-item">
                                <div class="timeline-date">${dataFormatada}</div>
                                <div class="timeline-text">
                                    <span class="timeline-user">${userNome}</span> ${hist.acao}
                                    <div style="font-size: 12px; color: #999; margin-top: 2px;">${hist.detalhes}</div>
                                </div>
                            </div>
                        `;
                        areaHistorico.insertAdjacentHTML('beforeend', htmlHist);
                    });
                } else {
                    areaHistorico.innerHTML = '<p style="color:#666; font-size: 12px; font-style:italic;">Nenhuma movimentação registrada.</p>';
                }
            })
            .catch(err => console.error(err));
    }

    // --- Funções de Salvar e Copiar ---
    function salvarResponsavel(userId) {
        if(!cardAtualId) return;

        fetch('../api/assign_user.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                card_id: cardAtualId,
                responsavel_id: userId
            })
        }).then(r => r.json()).then(d => {
            if(d.status === 'sucesso') {
                location.reload(); 
            } else {
                alert("Erro ao atribuir responsável!");
            }
        });
    }

    function salvarEtiqueta(cor) {
        if(!cardAtualId) return;
        fetch('../api/save_field.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ card_id: cardAtualId, chave: 'etiqueta', valor: cor })
        }).then(r => console.log('Cor salva!'));

        const visualTag = document.getElementById('tag-visual-' + cardAtualId);
        if(visualTag) visualTag.style.backgroundColor = cor ? cor : 'transparent';
    }

    function fecharModal() { document.getElementById('cardModal').style.display = 'none'; }
    window.onclick = function(event) { if (event.target == document.getElementById('cardModal')) fecharModal(); }

    // --- 4. Funções de Edição (Campos do Modal) ---
    function criarLinhaCampo(chave, valor) {
        const area = document.getElementById('modalCamposArea');
        const msgVazio = document.getElementById('msgVazio');
        if(msgVazio) msgVazio.remove();

        let wppBtn = '';
        if (['telefone', 'celular', 'whatsapp', 'tel', 'phone'].includes(chave.toLowerCase())) {
            let num = valor.replace(/\D/g, '');
            wppBtn = `<a href="https://wa.me/55${num}" target="_blank" class="btn-wpp" title="Abrir WhatsApp">W</a>`;
        }

        const html = `
            <div class="campo-item">
                <div style="display:flex; justify-content:space-between;">
                    <label class="campo-label">${chave}</label>
                    ${wppBtn}
                </div>
                <input type="text" class="campo-input" value="${valor}" onchange="salvarCampo('${chave}', this.value)">
            </div>
        `;
        area.insertAdjacentHTML('beforeend', html);
    }

    function salvarCampo(chave, novoValor) {
        fetch('../api/save_field.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ card_id: cardAtualId, chave: chave, valor: novoValor })
        });
    }

    function adicionarCampoManual() {
        const nome = prompt("Nome do novo campo (ex: CPF):");
        if (nome) criarLinhaCampo(nome, '');
    }

    // --- 5. Funções de Criação e Exclusão ---
    function adicionarFase() {
        const nome = prompt("Qual o nome da nova fase?");
        if (!nome) return;
        const urlParams = new URLSearchParams(window.location.search);
        fetch('../api/add_phase.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ pipe_id: urlParams.get('id'), nome: nome })
        }).then(r => r.json()).then(d => { if(d.status === 'sucesso') location.reload(); });
    }

    function excluirFase(id) {
        if(confirm("⚠️ Tem certeza que deseja apagar esta fase e TODOS os cards dela?")) {
            fetch('../api/delete_phase.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ phase_id: id })
            }).then(r => r.json()).then(d => { if(d.status === 'sucesso') location.reload(); });
        }
    }

    function adicionarCard(phaseId) {
        const titulo = prompt("Nome do Cliente ou Tarefa:");
        if (!titulo) return;
        const urlParams = new URLSearchParams(window.location.search);
        fetch('../api/add_card_manual.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ pipe_id: urlParams.get('id'), phase_id: phaseId, titulo: titulo })
        }).then(r => r.json()).then(d => { if(d.status === 'sucesso') location.reload(); });
    }

    function excluirCardAtual() {
        if(!cardAtualId) return;
        if(confirm("Tem certeza que deseja EXCLUIR este card?")) {
            fetch('../api/delete_card.php', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ card_id: cardAtualId })
            }).then(r => r.json()).then(d => {
                if(d.status === 'sucesso') {
                    const cardVisual = document.querySelector(`.kanban-card[data-card-id="${cardAtualId}"]`);
                    if(cardVisual) cardVisual.remove();
                    fecharModal();
                }
            });
        }
    }

    // --- Função para Copiar o Webhook ---
    function copiarWebhook() {
        const url = "<?php echo $urlWebhook ?? ''; ?>";
        if(url) {
            navigator.clipboard.writeText(url).then(() => {
                alert("✅ Link do Webhook copiado com sucesso!\n\nCole este link no seu Elementor, Make ou Typeform para receber leads direto neste Pipe.");
            }).catch(err => {
                alert("Erro ao copiar o link. Tente copiar manualmente: " + url);
            });
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>