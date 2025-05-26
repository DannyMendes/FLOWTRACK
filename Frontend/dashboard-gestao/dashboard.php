<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard / Gestão</title>
    <link rel="stylesheet" href="dashboard-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="cabecalho">
        <i class="fas fa-bars icone-menu"></i>
        <span class="titulo">Dashboard / Gestão</span>
    </div>
    <div class="navegacao">
        <button class="botao-navegacao ativo" onclick="window.location.href='/FLOWTRACK/Frontend/dashboard-gestao/dashboard.php'">Tarefas</button>
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/adicionar-tarefas/adicionar-tarefa.php'">Inserir tarefa</button>
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/gerir-usuarios/usuarios.php'">Gerir usuário</button>
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/dashboard-relatorios/relatorio.php'">Relatório</button>
    </div>
    <div class="conteudo">
        <div class="barra-acoes">
            <button class="botao-filtrar">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            <button class="botao-adicionar-tarefa" onclick="window.location.href='/FLOWTRACK/Frontend/adicionar-tarefas/adicionar-tarefa.php'">
                <i class="fas fa-plus-circle"></i> Adicionar tarefa
            </button>
        </div>
        <h2 class="titulo-pagina">Lista de Tarefas</h2>
        <div class="container-tabela-tarefas">
            <table class="tabela-tarefas">
                <thead>
                    <tr>
                        <th>Data Estimada</th>
                        <th>Tema</th>
                        <th>Descrição</th>
                        <th>Prioridade</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    require '../../backend/config/database.php';

                    try {
                        $stmt = $pdo->query("SELECT tarefa.data_estimada, tarefa.tema, tarefa.descricao, tarefa.prioridade, status.nome AS status FROM tarefas AS tarefa INNER JOIN status_tarefas AS status ON tarefa.status = status.id");
                        $tarefas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($tarefas as $tarefa): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tarefa['data_estimada']); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['tema']); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['descricao']); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['prioridade']); ?></td>
                                <td><?php echo htmlspecialchars
                                ($tarefa['status']); ?></td>
                                <td>
                                    <button class="open-button" onclick="window.location.href='/FLOWTRACK/Frontend/detalhe-tarefas/tarefa.php?id=<?php echo htmlspecialchars($tarefa['id']); ?>'"><i class="fas fa-folder-open"></i> </button>
 
                                    <button class="botaoacao" type="submit" name="acao" value="deletar" onclick="return confirm('isso excluirá permanetemente a tarefa Tem CERTEZA?')"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach;

                    } catch (PDOException $e) {
                        echo "<tr><td colspan='5'>Erro ao buscar tarefas: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>