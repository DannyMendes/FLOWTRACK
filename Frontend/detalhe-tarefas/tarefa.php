<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarefas - Detalhe da Tarefa</title>
    <link rel="stylesheet" href="tarefa.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="barra-navegacao">
        <i class="fas fa-bars icone-menu"></i>
        <span class="titulo">Tarefas</span>
        <i class="fas fa-user-circle icone-usuario"></i>
    </div>
    <div class="container">
        <div class="detalhes-tarefa">
            <h2>Descrição da Tarefa</h2>
            <div class="info-tarefa">
                <?php
                require '../../backend/config/database.php';
                session_start();

                $tarefaId = null; // Inicializa fora do if para ser acessível depois
                if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                    $tarefaId = $_GET['id'];
                    try {
                        $stmt = $pdo->prepare("SELECT t.data_estimada, t.tema, t.prioridade, t.descricao, s.nome AS status_nome, t.status AS status_id_num, t.usuario_criacao AS usuario_criador_id FROM tarefas t JOIN status_tarefas s ON t.status = s.id WHERE t.id = :id");
                        $stmt->bindParam(':id', $tarefaId, PDO::PARAM_INT);
                        $stmt->execute();
                        $tarefa = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($tarefa) {
                            echo '<div class="item-info-tarefa"><strong>DATA</strong> <span id="tarefa-data">' . htmlspecialchars($tarefa['data_estimada']) . '</span></div>';
                            echo '<div class="item-info-tarefa"><strong>TEMA</strong> <span id="tarefa-tema">' . htmlspecialchars($tarefa['tema']) . '</span></div>';
                            echo '<div class="item-info-tarefa"><strong>PRIORIDADE</strong> <span id="tarefa-prioridade">' . htmlspecialchars($tarefa['prioridade']) . '</span></div>';
                            echo '<div class="item-info-tarefa"><strong>DESCRIÇÃO</strong> <span id="tarefa-descricao">' . htmlspecialchars($tarefa['descricao']) . '</span></div>';
                            echo '<div class="item-info-tarefa"><strong>STATUS</strong> <span id="tarefa-status">' . htmlspecialchars($tarefa['status_nome']) . '</span></div>';
                            // Estes hidden inputs são importantes para o JS e para o envio do formulário principal
                            echo '<input type="hidden" id="tarefa_id_hidden" name="tarefa_id_display" value="' . $tarefaId . '">';
                            echo '<input type="hidden" id="usuario_criador_id_hidden" name="usuario_criador_id_display" value="' . htmlspecialchars($tarefa['usuario_criador_id']) . '">';
                            echo '<input type="hidden" id="current_status_id_hidden" name="current_status_id_display" value="' . htmlspecialchars($tarefa['status_id_num']) . '">';
                        } else {
                            echo '<p class="mensagem-erro">Tarefa não encontrada.</p>';
                        }
                    } catch (PDOException $e) {
                        echo '<p class="mensagem-erro">Erro ao buscar detalhes da tarefa: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                } else {
                    echo '<p class="mensagem-erro">ID da tarefa inválido.</p>';
                }

                // Obter a lista de status da tabela status_tarefas
                try {
                    $stmt_status = $pdo->query("SELECT id, nome, ordem FROM status_tarefas ORDER BY ordem");
                    $status_opcoes = $stmt_status->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    echo '<p class="mensagem-erro">Erro ao buscar status: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    $status_opcoes = [];
                }

                // Não precisamos mais de $showRotaInfo, $showFinalizarInfo aqui no PHP para exibir
                // Isso será controlado pelo JavaScript
                ?>
            </div>
        </div>
        <div class="acoes-tarefa">
            <label for="status_id">Alterar Status da Tarefa:</label>
            <form id="update-status-form" method="POST" action="../../backend/atualizar-status-tarefa.php" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo isset($tarefaId) ? $tarefaId : ''; ?>">
                <input type="hidden" name="status_id" id="selected_status_id" value=""> <div id="status-opcoes-container">
                    <?php if (isset($tarefa) && isset($tarefa['status_id_num'])): ?>
                        <?php
                        $currentStatusId = $tarefa['status_id_num'];
                        foreach ($status_opcoes as $opcao):
                            $canSelect = false;
                            // As IDs dos status são importantes para o PHP e para o JS
                            // 11 = PENDENTE, 12 = EM ROTA, 13 = INICIADO, 14 = FINALIZADO
                            if ($currentStatusId == 11) { // PENDENTE
                                $canSelect = ($opcao['id'] == 12 || $opcao['id'] == 13); // EM ROTA ou INICIADO
                            } elseif ($currentStatusId == 12) { // EM ROTA
                                $canSelect = ($opcao['id'] == 13); // INICIADO
                            } elseif ($currentStatusId == 13) { // INICIADO
                                $canSelect = ($opcao['id'] == 14); // FINALIZADO
                            } elseif ($currentStatusId == 14) { // FINALIZADO
                                $canSelect = false; // Não pode ir para outro status depois de finalizado
                            }
                            // Permite re-selecionar o status atual, mas sem desabilitar o botão
                            if ($currentStatusId == $opcao['id']) {
                                $canSelect = true; // Permite o clique, mas o tratamento será no JS/Backend
                                $current_status_class = 'status-atual'; // Classe para destacar o status atual
                            } else {
                                $current_status_class = '';
                            }
                            ?>
                            <button type="button" 
                                    class="status-opcao <?php echo $current_status_class; ?>" 
                                    data-status-id="<?php echo htmlspecialchars($opcao['id']); ?>" 
                                    data-status-nome="<?php echo htmlspecialchars($opcao['nome']); ?>"
                                    <?php if (!$canSelect && $opcao['id'] != $currentStatusId) echo 'disabled style="opacity: 0.5; cursor: not-allowed;"'; ?>>
                                <?php echo htmlspecialchars($opcao['nome']); ?>
                            </button>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($status_opcoes as $opcao): ?>
                            <button type="button" 
                                    class="status-opcao" 
                                    data-status-id="<?php echo htmlspecialchars($opcao['id']); ?>" 
                                    data-status-nome="<?php echo htmlspecialchars($opcao['nome']); ?>">
                                <?php echo htmlspecialchars($opcao['nome']); ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="rota-info hidden">
                    <h3>Informações de Rota</h3>
                    <label for="matricula">Matrícula da Carrinha:</label>
                    <input type="text" id="matricula" name="matricula_carrinha">
                    <label for="motorista">Nome do Motorista:</label>
                    <input type="text" id="motorista" name="motorista">
                    <label for="materiais">
                        <input type="checkbox" id="materiais" name="verificacao_material" value="1"> Já verifiquei todos os materiais/ferramentas
                    </label>
                </div>
                <div class="iniciar-info hidden">
                    <label for="materiais">
                        <input type="checkbox" id="materiais" name="verificacao_material" value="1"> Já verifiquei todos os materiais/ferramentas
                    </label>
                </div>

                <div class="finalizar-info hidden">
                    <h3>Informações de Finalização</h3>
                    <label for="trabalho_conforme">O trabalho foi efetuado conforme o planejado?</label>
                    <select id="trabalho_conforme" name="resposta_pergunta1">
                        <option value="">Selecione</option>
                        <option value="sim">Sim</option>
                        <option value="nao">Não</option>
                    </select>
                    <label for="dificuldade">Nível de Dificuldade:</label>
                    <select id="dificuldade" name="resposta_pergunta2">
                        <option value="">Selecione</option>
                        <option value="razoavel">Razoável</option>
                        <option value="mediano">Mediano</option>
                        <option value="dificil">Difícil</option>
                    </select>
                    <label for="descricao_final" class="obrigatorio">Descrição do Trabalho:</label>
                    <textarea id="descricao_final" name="descricao_trabalho" rows="3"></textarea>
                    <label for="anexar_fotos" class="obrigatorio">Anexar Fotos:</label>
                    <input type="file" id="anexar_fotos" name="fotos[]" multiple>
                </div>

                <input type="hidden" name="acao" value="atualizar_status">
                <button type="submit" class="botao-concluir-alteracao" id="salvar-status-btn"><i class="fas fa-check"></i> Salvar Alteração de Status</button>
            </form>

            <div id="feedback-message" class="mensagem"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusButtons = document.querySelectorAll('.status-opcao');
            const rotaInfoDiv = document.querySelector('.rota-info');
            const iniciarInfoDiv = document.querySelector('.iniciar-info');
            const finalizarInfoDiv = document.querySelector('.finalizar-info');
            const selectedStatusInput = document.getElementById('selected_status_id');
            const form = document.getElementById('update-status-form');
            const feedbackMessageDiv = document.getElementById('feedback-message');
            const tarefaIdHiddenInput = document.getElementById('tarefa_id_hidden');
            const currentStatusIdHiddenInput = document.getElementById('current_status_id_hidden'); // O status ID atual da tarefa


            let currentSelectedStatusId = null; // Variável para armazenar o ID do status atualmente selecionado pelo usuário no frontend

            // Função para esconder todas as divs de informações extras
            function hideAllExtraInfoDivs() {
                rotaInfoDiv.classList.add('hidden');
                finalizarInfoDiv.classList.add('hidden');
            }

            // Função para atualizar a exibição das divs de informações extras
            function updateExtraInfoDisplay(statusId) {
                hideAllExtraInfoDivs(); // Esconde tudo primeiro

                // Se o status ID for 12 (EM ROTA)
                if (statusId === 12) {
                    rotaInfoDiv.classList.remove('hidden');
                }
                // Se o status ID for 12 (EM ROTA)
                if (statusId === 13) {
                    iniciarInfoDiv.classList.remove('hidden');
                }  
                // Se o status ID for 14 (FINALIZADO)
                else if (statusId === 14) {
                    finalizarInfoDiv.classList.remove('hidden');
                }
            }

            // Adiciona evento de clique a cada botão de status
            statusButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const statusId = parseInt(this.dataset.statusId);
                    const statusNome = this.dataset.statusNome;

                    // Remove a classe 'selecionado' de todos os botões
                    statusButtons.forEach(btn => btn.classList.remove('selecionado'));
                    // Adiciona a classe 'selecionado' ao botão clicado
                    this.classList.add('selecionado');

                    // Atualiza o input hidden com o ID do status selecionado
                    selectedStatusInput.value = statusId;
                    currentSelectedStatusId = statusId; // Armazena o ID selecionado

                    // Atualiza a exibição das divs de informações extras
                    updateExtraInfoDisplay(statusId);
                });
            });

            // Lógica para o envio do formulário via AJAX
            form.addEventListener('submit', async function(event) {
                event.preventDefault(); // Impede o envio padrão do formulário

                // Pega o ID da tarefa do input hidden
                const tarefaId = tarefaIdHiddenInput.value;
                // Pega o status ID selecionado pelo usuário
                const statusIdParaEnviar = selectedStatusInput.value;
                // Pega o status ID atual da tarefa no DB
                const currentStatusIdDB = parseInt(currentStatusIdHiddenInput.value);

                // Validação mínima antes de enviar
                if (!tarefaId || !statusIdParaEnviar) {
                    feedbackMessageDiv.textContent = 'Erro: ID da tarefa ou status não selecionado.';
                    feedbackMessageDiv.className = 'mensagem mensagem-erro';
                    return;
                }

                // Lógica de validação de transição de status no frontend (opcional, pois o backend também valida)
                // IDs: PENDENTE=11, EM ROTA=12, INICIADO=13, FINALIZADO=14
                let canProceed = false;
                if (statusIdParaEnviar == currentStatusIdDB) { // Pode "salvar" o mesmo status para atualizar info
                    canProceed = true;
                } else if (currentStatusIdDB === 11 && (statusIdParaEnviar == 12 || statusIdParaEnviar == 13)) { // PENDENTE -> EM ROTA ou INICIADO
                    canProceed = true;
                } else if (currentStatusIdDB === 12 && statusIdParaEnviar == 13) { // EM ROTA -> INICIADO
                    canProceed = true;
                } else if (currentStatusIdDB === 13 && statusIdParaEnviar == 14) { // INICIADO -> FINALIZADO
                    canProceed = true;
                } else if (currentStatusIdDB === 14) { // FINALIZADO
                    canProceed = false; // Não pode mais mudar
                }

                if (!canProceed) {
                    feedbackMessageDiv.textContent = 'Alteração de status não permitida.';
                    feedbackMessageDiv.className = 'mensagem mensagem-erro';
                    return;
                }
                
                // Validação específica para o status FINALIZADO
                if (parseInt(statusIdParaEnviar) === 14) { // Se o status selecionado for FINALIZADO
                    const descricaoFinal = document.getElementById('descricao_final').value.trim();
                    const anexarFotos = document.getElementById('anexar_fotos').files.length > 0;

                    if (!descricaoFinal) {
                        feedbackMessageDiv.textContent = 'Descrição do Trabalho é obrigatória para Finalizar.';
                        feedbackMessageDiv.className = 'mensagem mensagem-erro';
                        return;
                    }
                    if (!anexarFotos) {
                        feedbackMessageDiv.textContent = 'Anexar Fotos é obrigatório para Finalizar.';
                        feedbackMessageDiv.className = 'mensagem mensagem-erro';
                        return;
                    }
                }


                const formData = new FormData(form);
                formData.set('id', tarefaId); // Garante que o ID da tarefa está no FormData
                formData.set('status_id', statusIdParaEnviar); // Garante o status ID correto

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.text(); // Pega a resposta como texto

                    if (result.includes('success')) {
                        feedbackMessageDiv.textContent = 'Status atualizado com sucesso!';
                        feedbackMessageDiv.className = 'mensagem mensagem-sucesso';
                        // Opcional: recarregar a página para mostrar o novo status
                        setTimeout(() => {
                            window.location.reload(); 
                        }, 1000); 
                    } else if (result.includes('alteracao_nao_permitida')) {
                        feedbackMessageDiv.textContent = 'Esta alteração de status não é permitida.';
                        feedbackMessageDiv.className = 'mensagem mensagem-erro';
                    } else if (result.includes('descricao_final_obrigatoria')) {
                        feedbackMessageDiv.textContent = 'Descrição do Trabalho é obrigatória para Finalizar.';
                        feedbackMessageDiv.className = 'mensagem mensagem-erro';
                    } else if (result.includes('permissao_negada')) {
                        feedbackMessageDiv.textContent = 'Você não tem permissão para finalizar esta tarefa.';
                        feedbackMessageDiv.className = 'mensagem mensagem-erro';
                    } else if (result.includes('erro_upload_foto')) {
                        feedbackMessageDiv.textContent = 'Erro ao fazer upload das fotos. Verifique o tamanho ou formato.';
                        feedbackMessageDiv.className = 'mensagem mensagem-erro';
                    } else {
                        feedbackMessageDiv.textContent = 'Erro ao atualizar status: ' + result;
                        feedbackMessageDiv.className = 'mensagem mensagem-erro';
                        console.error('Resposta do servidor:', result);
                    }
                } catch (error) {
                    console.error('Erro na requisição AJAX:', error);
                    feedbackMessageDiv.textContent = 'Erro de rede ou servidor. Tente novamente.';
                    feedbackMessageDiv.className = 'mensagem mensagem-erro';
                }
            });

            // Inicializa a exibição das divs com base no status atual ao carregar a página
            // Esta parte garante que, se a página for recarregada e o status já for EM ROTA ou FINALIZADO,
            // as informações adicionais já apareçam.
            const initialStatusId = parseInt(currentStatusIdHiddenInput.value);
            if (!isNaN(initialStatusId)) {
                updateExtraInfoDisplay(initialStatusId);
                 // Adicionar a classe 'selecionado' ao botão do status atual
                statusButtons.forEach(button => {
                    if (parseInt(button.dataset.statusId) === initialStatusId) {
                        button.classList.add('selecionado');
                        selectedStatusInput.value = initialStatusId; // Define o valor inicial do hidden input
                    }
                });
            }
        });
    </script>
</body>
</html>