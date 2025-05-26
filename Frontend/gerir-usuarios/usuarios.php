<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard / Gestão - Gerir Utilizadores</title>
    <link rel="stylesheet" href="usuarios.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="header">
        <i class="fas fa-bars menu-icon"></i>
        <span class="title">Dashboard / Gestão</span>
    </div>
    <div class="navegacao">
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/dashboard-gestao/dashboard.php'">Tarefas</button>
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/adicionar-tarefas/adicionar-tarefa.php'">Inserir tarefa</button>
        <button class="botao-navegacao ativo" onclick="window.location.href='/FLOWTRACK/Frontend/gerir-usuarios/usuarios.php'">Gerir usuário</button>
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/dashboard-relatorios/relatorio.php'">Relatório</button>
    </div>
    <div class="conteudo">
        <h2 class="titulo-pagina">Utilizadores registados</h2>
        <div class="container-tabela-usuarios">
            <table class="tabela-usuarios">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>NOME</th>
                        <th>FUNÇÃO</th>
                        <th>TIPO DE ACESSO</th>
                    </tr>
                </thead>
                <tbody id="users-table-body">
                    <?php
                    require '../../backend/config/database.php';

                    try {
                        $stmt = $pdo->query("SELECT id, nome, funcao, tipo_acesso FROM usuarios");
                        while ($utilizador = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($utilizador['id']) . '</td>';
                            echo '<td>' . htmlspecialchars($utilizador['nome']) . '</td>';
                            echo '<td>' . htmlspecialchars($utilizador['funcao']) . '</td>';
                            echo '<td>' . htmlspecialchars($utilizador['tipo_acesso']) . '</td>';
                            echo '</tr>';
                        }
                    } catch (PDOException $e) {
                        echo '<tr><td colspan="4" class="mensagem-erro">Erro ao buscar utilizadores: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="acoes">
            <button class="botao-acao alterar">
                <i class="fas fa-pencil-alt"></i> Alterar registo
            </button>
            <button class="botao-acao adicionar" onclick="window.location.href='/FLOWTRACK/Frontend/gerir-usuarios/cadastrar-usuarios.php'">
                <i class="fas fa-plus-circle"></i> Adicionar utilizador
            </button>
        </div>
    </div>
</body>
</html>