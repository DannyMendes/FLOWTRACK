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
                            echo '<input type="hidden" name="tarefa_id_display" value="' . $tarefaId . '">';
                            echo '<input type="hidden" name="usuario_criador_id_display" value="' . htmlspecialchars($tarefa['usuario_criador_id']) . '">';
                            echo '<input type="hidden" name="current_status_id_display" value="' . htmlspecialchars($tarefa['status_id_num']) . '">';
                        } else {
                            echo '<p class="mensagem-erro">Tarefa não encontrada.</p>';
                        }
                    } catch (PDOException $e) {
                        echo '<p class="mensagem-erro">Erro ao buscar detalhes da tarefa: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                } else {
                    echo '<p class="mensagem-erro">ID da tar efa inválido.</p>';
                }

                // Obter a lista de status da tabela status_tarefas
                try {
                    $stmt_status = $pdo->query("SELECT id, nome, ordem FROM status_tarefas ORDER BY ordem");
                    $status_opcoes = $stmt_status->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    echo '<p class="mensagem-erro">Erro ao buscar status: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    $status_opcoes = [];
                }

                $selectedStatusId = $_POST['status_id'] ?? null;
                $showRotaInfo = false;
                $showFinalizarInfo = false;

                if ($selectedStatusId) {
                    foreach ($status_opcoes as $opcao) {
                        if ($opcao['id'] == $selectedStatusId && $opcao['nome'] == 'EM ROTA') {
                            $showRotaInfo = true;
                            break;
                        } elseif ($opcao['id'] == $selectedStatusId && $opcao['nome'] == 'FINALIZADO') {
                            $showFinalizarInfo = true;
                            break;
                        }
                    }
                }
                ?>
            </div>
        </div>
        <div class="acoes-tarefa">
            <label for="status_id">Alterar Status da Tarefa:</label>
            <form method="POST" action="../../backend/atualizar-status-tarefa.php" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo isset($tarefaId) ? $tarefaId : ''; ?>">
                <div id="status-opcoes-container">
                    <?php if (isset($tarefa) && isset($tarefa['status_id_num'])): ?>
                        <?php
                        $currentStatusId = $tarefa['status_id_num'];
                        foreach ($status_opcoes as $opcao):
                            $canSelect = false;
                        if ($currentStatusId == 11) { // PENDENTE (ID agora é 11)
                        $canSelect = ($opcao['id'] == 12 || $opcao['id'] == 13); // EM ROTA (ID 12) ou INICIADO (ID 13)
                        } elseif ($currentStatusId == 12) { // EM ROTA (ID agora é 12)
                        $canSelect = ($opcao['id'] == 13); // INICIADO (ID 13)
                        } elseif ($currentStatusId == 13) { // INICIADO (ID agora é 13)
                        $canSelect = ($opcao['id'] == 14); // FINALIZADO (ID 14)
                        } elseif ($currentStatusId == 14) { // FINALIZADO (ID agora é 14)
                        $canSelect = false; // Não pode ir para outro status depois de finalizado
                    } elseif ($currentStatusId == $opcao['id']) {
                        $canSelect = true; // Pode re-selecionar o status atual
                    }
                            ?>
                            <button type="submit" name="status_id" value="<?php echo htmlspecialchars($opcao['id']); ?>" class="status-opcao <?php if ($selectedStatusId == $opcao['id']) echo 'selecionado'; ?>" <?php if (!$canSelect) echo 'disabled style="opacity: 0.5; cursor: not-allowed;"'; ?>>
                                <?php echo htmlspecialchars($opcao['nome']); ?>
                            </button>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($status_opcoes as $opcao): ?>
                            <button type="submit" name="status_id" value="<?php echo htmlspecialchars($opcao['id']); ?>" class="status-opcao <?php if ($selectedStatusId == $opcao['id']) echo 'selecionado'; ?>">
                                <?php echo htmlspecialchars($opcao['nome']); ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="rota-info" style="display: <?php echo $showRotaInfo ? 'block' : 'none'; ?>;">
                    <label for="matricula">Matrícula da Carrinha:</label>
                    <input type="text" id="matricula" name="matricula_carrinha" value="<?php echo htmlspecialchars($_POST['matricula_carrinha'] ?? ''); ?>">
                    <label for="motorista">Nome do Motorista:</label>
                    <input type="text" id="motorista" name="motorista" value="<?php echo htmlspecialchars($_POST['motorista'] ?? ''); ?>">
                    <label for="materiais">
                        <input type="checkbox" id="materiais" name="verificacao_material" value="1" <?php echo isset($_POST['verificacao_material']) ? 'checked' : ''; ?>> Já verifiquei todos os materiais/ferramentas
                    </label>
                </div>

                <div class="finalizar-info" style="display: <?php echo $showFinalizarInfo ? 'block' : 'none'; ?>;">
                    <label for="trabalho_conforme">O trabalho foi efetuado conforme o planejado?</label>
                    <select id="trabalho_conforme" name="resposta_pergunta1">
                        <option value="">Selecione</option>
                        <option value="sim" <?php echo ($_POST['resposta_pergunta1'] ?? '') == 'sim' ? 'selected' : ''; ?>>Sim</option>
                        <option value="nao" <?php echo ($_POST['resposta_pergunta1'] ?? '') == 'nao' ? 'selected' : ''; ?>>Não</option>
                    </select>
                    <label for="dificuldade">Nível de Dificuldade:</label>
                    <select id="dificuldade" name="resposta_pergunta2">
                        <option value="">Selecione</option>
                        <option value="razoavel" <?php echo ($_POST['resposta_pergunta2'] ?? '') == 'razoavel' ? 'selected' : ''; ?>>Razoável</option>
                        <option value="mediano" <?php echo ($_POST['resposta_pergunta2'] ?? '') == 'mediano' ? 'selected' : ''; ?>>Mediano</option>
                        <option value="dificil" <?php echo ($_POST['resposta_pergunta2'] ?? '') == 'dificil' ? 'selected' : ''; ?>>Difícil</option>
                    </select>
                    <label for="descricao_final" class="obrigatorio">Descrição do Trabalho:</label>
                    <textarea id="descricao_final" name="descricao_trabalho" rows="3"><?php echo htmlspecialchars($_POST['descricao_trabalho'] ?? ''); ?></textarea>
                    <label for="anexar_fotos" class="obrigatorio">Anexar Fotos:</label>
                    <input type="file" id="anexar_fotos" name="fotos[]" multiple>
                </div>

                <input type="hidden" name="acao" value="atualizar_status">
                <button type="submit" class="botao-concluir-alteracao"><i class="fas fa-check"></i> Salvar Alteração de Status</button>
            </form>

            <?php
            // Mensagem de feedback do backend (por enquanto, exibindo diretamente)
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status_id'])) {
                include '../../backend/atualizar-status-tarefa.php';
            }
            ?>
        </div>
    </div>
</body>
</html>