<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarefas - Lista de Tarefas Cadastradas</title>
    <link rel="stylesheet" href="detalhe.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            justify-content: center;
            align-items: center;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            border: 1px solid #eee;
        }

        .filter-container label {
            font-size:x-small;
            letter-spacing: 0.5px;
        }

        .filter-container select,
        .filter-container input[type="date"] {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            flex-grow: 1;
            min-width: 150px;
        }

        .filter-container button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            background-color: #00bcd4;
            color: white;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
            flex-shrink: 0;
        }

        .filter-container button:hover {
            background-color: #008ba7;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="menu-icon">☰</div>
        <span class="title">Tarefas</span>
    </div>
    <div class="content">
        <h2 class="page-title">Lista de Tarefas Cadastradas</h2>

        <form method="GET" class="filter-container">
            <label for="filter-date">Filtrar por Data:</label>
            <input type="date" id="filter-date" name="filter_date" value="<?php echo isset($_GET['filter_date']) ? htmlspecialchars($_GET['filter_date']) : ''; ?>">

            <label for="filter-status">Filtrar por Status:</label>
            <select id="filter-status" name="filter_status">
                <option value="">Todos</option>
                <?php
                require '../../backend/config/database.php';
                try {
                    $stmt_status = $pdo->query("SELECT id, nome FROM status_tarefas");
                    $statuses = $stmt_status->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($statuses as $status) {
                        $selected = (isset($_GET['filter_status']) && $_GET['filter_status'] == $status['id']) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($status['id']) . "' " . $selected . ">" . htmlspecialchars($status['nome']) . "</option>";
                    }
                } catch (PDOException $e) {
                    echo "<option value='' disabled>Erro ao buscar status</option>";
                }
                ?>
            </select>

            <label for="filter-period">Filtrar por Período:</label>
            <select id="filter-period" name="filter_period">
                <option value="">Todos</option>
                <option value="semana" <?php echo (isset($_GET['filter_period']) && $_GET['filter_period'] == 'semana') ? 'selected' : ''; ?>>Esta Semana</option>
                <option value="mes" <?php echo (isset($_GET['filter_period']) && $_GET['filter_period'] == 'mes') ? 'selected' : ''; ?>>Este Mês</option>
            </select>

            <button type="submit">Filtrar Tarefas</button>
        </form>

        <div class="tasks-table-container" id="tasks-table-container">
            <table class="tasks-table" id="tasks-table">
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
                <tbody id="tasks-table-body">
                    <?php
                    try {
                        $where = "WHERE 1=1";
                        $params = [];

                        if (isset($_GET['filter_date']) && !empty($_GET['filter_date'])) {
                            $where .= " AND tarefa.data_estimada = :filter_date";
                            $params[':filter_date'] = $_GET['filter_date'];
                        }

                        if (isset($_GET['filter_status']) && !empty($_GET['filter_status'])) {
                            $where .= " AND tarefa.status = :filter_status";
                            $params[':filter_status'] = $_GET['filter_status'];
                        }

                        if (isset($_GET['filter_period']) && !empty($_GET['filter_period'])) {
                            if ($_GET['filter_period'] == 'semana') {
                                $where .= " AND tarefa.data_estimada >= DATE(NOW() - INTERVAL (WEEKDAY(NOW())) DAY) AND tarefa.data_estimada < DATE(NOW() + INTERVAL (6 - WEEKDAY(NOW())) DAY)";
                            } elseif ($_GET['filter_period'] == 'mes') {
                                $where .= " AND YEAR(tarefa.data_estimada) = YEAR(CURDATE()) AND MONTH(tarefa.data_estimada) = MONTH(CURDATE())";
                            }
                        }

                        $sql = "
                            SELECT
                                tarefa.id,
                                tarefa.data_estimada,
                                tarefa.tema,
                                tarefa.descricao,
                                tarefa.prioridade,
                                status.nome AS status_nome,
                                status.id AS status_id
                            FROM tarefas AS tarefa
                            INNER JOIN status_tarefas AS status ON tarefa.status = status.id
                            $where
                            ORDER BY tarefa.data_estimada DESC
                        ";

                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        $tarefas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($tarefas) > 0):
                            foreach ($tarefas as $tarefa): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($tarefa['data_estimada']); ?></td>
                                    <td><?php echo htmlspecialchars($tarefa['tema']); ?></td>
                                    <td><?php echo htmlspecialchars($tarefa['descricao']); ?></td>
                                    <td><?php echo htmlspecialchars($tarefa['prioridade']); ?></td>
                                    <td data-status-id="<?php echo htmlspecialchars($tarefa['status_id']); ?>">
                                        <?php echo htmlspecialchars($tarefa['status_nome']); ?>
                                    </td>
                                    <td>
                                        <button class="open-button" onclick="window.location.href='/FLOWTRACK/Frontend/detalhe-tarefas/tarefa.php?id=<?php echo htmlspecialchars($tarefa['id']); ?>'">Abrir</button>
                                    </td>
                                </tr>
                            <?php endforeach;
                        else: ?>
                            <tr><td colspan="6">Nenhuma tarefa encontrada com os filtros selecionados.</td></tr>
                        <?php endif;

                    } catch (PDOException $e) {
                        echo "<tr><td colspan='6'>Erro ao buscar tarefas: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>