<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarefas - Lista de Tarefas Cadastradas</title>
    <link rel="stylesheet" href="detalhe.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="header">
        <div class="menu-icon">☰</div>
        <span class="title">Tarefas</span>
    </div>
    <div class="content">
        <h2 class="page-title">Lista de Tarefas cadastradas</h2>
        <div class="tasks-table-container">
            <table class="tasks-table">
                <thead>
                    <tr>
                        <th>DATA</th>
                        <th>TEMA</th>
                        <th>DESCRIÇÃO</th>
                        <th>PRIORIDADE</th>
                        <th>STATUS</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    require '../../backend/config/database.php';

                    try {
                        $stmt = $pdo->query("SELECT tarefa.id, tarefa.data_estimada, tarefa.tema, tarefa.descricao, tarefa.prioridade, status.nome AS status FROM tarefas AS tarefa INNER JOIN status_tarefas AS status ON tarefa.status = status.id");
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
                                        <button class="open-button" onclick="window.location.href='/FLOWTRACK/Frontend/detalhe-tarefas/tarefa.php?id=<?php echo htmlspecialchars($tarefa['id']); ?>'">Abrir</button>
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