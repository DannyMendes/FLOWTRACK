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

                if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                    $tarefaId = $_GET['id'];
                    try {
                        $stmt = $pdo->prepare("SELECT data_estimada, tema, prioridade, descricao, status FROM tarefas WHERE id = :id");
                        $stmt->bindParam(':id', $tarefaId, PDO::PARAM_INT);
                        $stmt->execute();
                        $tarefa = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($tarefa) {
                            echo '<div class="item-info-tarefa"><strong>DATA</strong> <span id="tarefa-data">' . htmlspecialchars($tarefa['data_estimada']) . '</span></div>';
                            echo '<div class="item-info-tarefa"><strong>TEMA</strong> <span id="tarefa-tema">' . htmlspecialchars($tarefa['tema']) . '</span></div>';
                            echo '<div class="item-info-tarefa"><strong>PRIORIDADE</strong> <span id="tarefa-prioridade">' . htmlspecialchars($tarefa['prioridade']) . '</span></div>';
                            echo '<div class="item-info-tarefa"><strong>DESCRIÇÃO</strong> <span id="tarefa-descricao">' . htmlspecialchars($tarefa['descricao']) . '</span></div>';
                            echo '<input type="hidden" id="tarefa-id" value="' . $tarefaId . '">'; 
                        } else {
                            echo '<p class="mensagem-erro">Tarefa não encontrada.</p>';
                        }
                    } catch (PDOException $e) {
                        echo '<p class="mensagem-erro">Erro ao buscar detalhes da tarefa: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                } else {
                    echo '<p class="mensagem-erro">ID da tarefa inválido.</p>';
                }
                ?>
            </div>
        </div>
        <div class="acoes-tarefa">
            <label for="status">Preencha as informações para alterar o status da tarefa:</label>
            <label for="status">STATUS:</label>
            <div class="container-botoes">
                <form action="tarefa-detalhe/atualizar_status_tarefa.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo isset($tarefaId) ? $tarefaId : ''; ?>">
                    <select id="status" name="status">
                        <option value="A FAZER" <?php if (isset($tarefa['status']) && $tarefa['status'] === 'A FAZER') echo 'selected'; ?>>a fazer</option>
                        <option value="EM ANDAMENTO" <?php if (isset($tarefa['status']) && $tarefa['status'] === 'EM ANDAMENTO') echo 'selected'; ?>>em andamento</option>
                        <option value="FINALIZADO" <?php if (isset($tarefa['status']) && $tarefa['status'] === 'FINALIZADO') echo 'selected'; ?>>concluída</option>
                    </select>
                    <button type="button" class="botao-adicionar-fotos"><i class="fas fa-plus"></i> Adicionar Fotos</button>
                </div>
                <label for="matricula">MATRÍCULA:</label>
                <input type="text" id="matricula" name="matricula" value="veículo">
                <label for="descricao_alteracao">Descrição:</label>
                <textarea id="descricao_alteracao" name="descricao_alteracao" rows="3"></textarea>
                <input type="hidden" name="acao" value="atualizar_status">
                <button type="submit" class="botao-concluir-alteracao"><i class="fas fa-check"></i> Concluir alteração</button>
            </form>
        </div>
    </div>
</body>
</html>