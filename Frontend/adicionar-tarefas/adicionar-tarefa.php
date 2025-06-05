<!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard / Gestão - Inserir Tarefa</title>
        <link rel="stylesheet" href="adicionar-tarefa.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </head>
    <body>
        <div class="cabecalho">
            <i class="fas fa-bars icone-menu"></i>
            <span class="titulo">Dashboard / Gestão</span>
        </div>
        <div class="navegacao">
            <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/dashboard-gestao/dashboard.php'">Tarefas</button>
            <button class="botao-navegacao ativo" onclick="window.location.href='/FLOWTRACK/Frontend/adicionar-tarefas/adicionar-tarefa.php'">Inserir tarefa</button>
            <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/gerir-usuarios/usuarios.php'">Gerir usuário</button>
            <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/dashboard-relatorios/relatorio.php'">Relatório</button>
        </div>

        <div class="conteudo">
            <h2 class="titulo-pagina">Inserir Nova Tarefa</h2>
            <?php
                session_start();
                if (isset($_SESSION['erro_cadastro_tarefa'])) {
                    echo '<p class="mensagem-erro">' . $_SESSION['erro_cadastro_tarefa'] . '</p>';
                    unset($_SESSION['erro_cadastro_tarefa']); // Limpa a mensagem de erro da sessão
                }
                if (isset($_GET['mensagem']) && $_GET['mensagem'] === 'tarefa_adicionada') {
                    echo '<div class="mensagem-sucesso">Tarefa adicionada com sucesso!</div>';
                }
            ?>
            <form class="formulario-adicionar-tarefa" action="/FLOWTRACK/backend/processar-adm-tarefa.php" method="post">
                <div class="grupo-formulario">
                    <label for="data_estimada">Data Estimada:</label>
                    <input type="date" id="data_estimada" name="data">
                </div>
                <div class="grupo-formulario">
                    <label for="tema">Tema:</label>
                    <input type="text" id="tema" name="tema">
                </div>
                <div class="grupo-formulario">
                    <label for="prioridade">Prioridade:</label>
                    <select id="prioridade" name="prioridade">
                        <option value="">Selecione</option>
                        <option value="BAIXA">Baixa</option>
                        <option value="MEDIA">Média</option>
                        <option value="ALTA">Alta</option>
                    </select>
                </div>
                <div class="grupo-formulario">
                    <label for="local">Local:</label>
                    <input type="text" id="local" name="local">
                </div>
                <div class="grupo-formulario">
                    <label for="descricao">Descrição:</label>
                    <textarea id="descricao" name="descricao"></textarea>
                </div>
                <div class="acoes">
                    <button type="submit" class="botao-acao adicionar">
                        <i class="fas fa-plus-circle"></i> Adicionar tarefa
                    </button>
                    <button type="button" class="botao-acao cancelar" onclick="window.location.href='/FLOWTRACK/Frontend/dashboard-gestao/dashboard-gestao.php'">
                        <i class="fas fa-times-circle"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </body>
    </html>