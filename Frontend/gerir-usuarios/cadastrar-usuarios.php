<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard / Gestão - Cadastrar Usuário</title>
    <link rel="stylesheet" href="cadastrar-usuarios.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="cabecalho">
        <i class="fas fa-bars icone-menu"></i>
        <span class="titulo">Dashboard / Gestão</span>
    </div>
    <div class="navegacao">
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/dashboard-gestao/dashboard.php'">Tarefas</button>
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/adicionar-tarefas/adicionar-tarefa.php'">Inserir tarefa</button>
        <button class="botao-navegacao ativo" onclick="window.location.href='/FLOWTRACK/Frontend/adicionar-usuario/adicionar-usuario.php'">Cadastrar usuário</button>
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/gerir-usuarios/usuarios.php'">Gerir usuário</button>
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/dashboard-relatorios/relatorio.php'">Relatório</button>
    </div>
    <div class="conteudo">
        <h2 class="titulo-pagina">Cadastrar Novo Usuário</h2>

        <?php
        // A chamada session_start() já está no início do backend/processar_cadastro_usuario.php.
        // Aqui no frontend, você também precisa dela para acessar as variáveis de sessão.
        // Se este arquivo é acessado diretamente, como um template, é bom mantê-la aqui também.
        session_start(); // Garante que a sessão seja iniciada para exibir mensagens

        if (isset($_SESSION['erro_cadastro'])) {
            echo '<div class="mensagem-erro">' . htmlspecialchars($_SESSION['erro_cadastro']) . '</div>';
            unset($_SESSION['erro_cadastro']); // Limpa a variável de sessão após exibir a mensagem
        }
        if (isset($_SESSION['sucesso_cadastro'])) {
            echo '<div class="mensagem-sucesso">' . htmlspecialchars($_SESSION['sucesso_cadastro']) . '</div>';
            unset($_SESSION['sucesso_cadastro']); // Limpa a variável de sessão após exibir a mensagem
        }
        // Mensagem de erro de acesso, caso o usuário não tenha permissão
        if (isset($_SESSION['erro_acesso'])) {
            echo '<div class="mensagem-erro">' . htmlspecialchars($_SESSION['erro_acesso']) . '</div>';
            unset($_SESSION['erro_acesso']);
        }
        ?>

        <form class="formulario-adicionar-tarefa" action="/FLOWTRACK/backend/processar_cadastro_usuario.php" method="post">
            <div class="grupo-formulario">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div class="grupo-formulario">
                <label for="funcao">Função (ex: Técnico, Supervisor):</label>
                <input type="text" id="funcao" name="funcao">
            </div>
            <div class="grupo-formulario">
                <label for="tipo_acesso">Tipo de Acesso:</label>
                <select id="tipo_acesso" name="tipo_acesso" required>
                    <option value="">Selecione</option>
                    <option value="Administrador">Administrador</option>
                    <option value="Comum">Comum</option>
                </select>
            </div>
            <div class="grupo-formulario">
                <label for="usuario">Nome de Usuário (para login):</label>
                <input type="text" id="usuario" name="usuario" required>
            </div>
            <div class="grupo-formulario">
                <label for="senha">Senha:(8 digitos)</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <div class="grupo-formulario">
                <label for="confirmar_senha">Confirmar Senha:</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required>
            </div>
            <div class="acoes">
                <button type="submit" class="botao-acao adicionar">
                    <i class="fas fa-plus-circle"></i> Cadastrar Usuário
                </button>
                <button type="button" class="botao-acao cancelar" onclick="window.location.href='/FLOWTRACK/Frontend/gerir-usuarios/usuarios.php'">
                    <i class="fas fa-times-circle"></i> Cancelar
                </button>
            </div>
        </form>
    </div>
</body>
</html>