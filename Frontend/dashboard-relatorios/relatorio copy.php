<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard / Gestão</title>
    <link rel="stylesheet" href="relatorio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>

</head>
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
        <script>
        async function generatePDF() {
        // Crie uma instância do jsPDF
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        // Adicione um título ao relatório
        doc.setFontSize(16);
        doc.text("Relatório de Tarefas", 10, 10);

        // Pegue os dados da tabela
        const table = document.getElementById("tasks-table");
        const rows = table.querySelectorAll("tbody tr");

        if (rows.length === 0) {
            alert("Nenhuma tarefa para gerar relatório!");
            return;
        }

        let yOffset = 20; // Posição inicial no PDF

        // Adicione o cabeçalho da tabela
        doc.setFontSize(12);
        const headers = Array.from(table.querySelectorAll("thead th")).map(
            (header) => header.textContent
        );
        doc.text(headers.join(" | "), 10, yOffset);

        // Adicione as linhas da tabela
        rows.forEach((row) => {
            yOffset += 10;
            const cols = Array.from(row.querySelectorAll("td")).map(
                (col) => col.textContent.trim()
            );
            doc.text(cols.join(" | "), 10, yOffset);
        });

        // Salve o PDF
        doc.save("relatorio-tarefas.pdf");
    }
    </script>
<body>
    <div class="header">
        <i class="fas fa-bars menu-icon"></i>
        <span class="title">Dashboard / Gestão</span>
    </div>
    <div class="navegacao">
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/dashboard-gestao/dashboard.php'">Tarefas</button>
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/adicionar-tarefas/adicionar-tarefa.php'">Inserir tarefa</button>
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/gerir-usuarios/usuarios.php'">Gerir usuário</button>
        <button class="botao-navegacao ativo" onclick="window.location.href='/FLOWTRACK/Frontend/dashboard-relatorios/relatorio.php'">Relatório</button>
    </div>
    <div class="date-time" id="current-date"></div>
    <div class="top-bar">
        <div class="search-container">
            <input type="text" placeholder="Pesquisar">
            <i class="fas fa-times clear-icon"></i>
        </div>
        <button class="generate-report-button" onclick="generatePDF()">
            Gerar relatório <i class="far fa-file-alt report-icon"></i>
        </button>
    </div>
    <div class="content">
        <form method="GET" class="filter-container">
            <input type="date" id="filter-date" name="filter_date" value="<?php echo isset($_GET['filter_date']) ? htmlspecialchars($_GET['filter_date']) : ''; ?>">

            <select id="filter-status" name="filter_status">
                <option value="">Status</option>
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

            <select id="filter-period" name="filter_period">
                <option value="">Filtro personalizado</option>
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
    
    <div class="main-container">
        <div class="sidebar">
            <div class="legend-item">
                <div class="legend-color em-falta"></div>
                <span>Em falta</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </div>
            <div class="legend-item">
                <div class="legend-color em-progresso"></div>
                <span>Em progresso</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </div>
            <div class="legend-item">
                <div class="legend-color concluida"></div>
                <span>Concluída</span>
                <i class="fas fa-chevron-right arrow-icon"></i>
            </div>
        </div>
        <div class="main-content">
            <div class="progress-chart">
                <canvas id="taskProgressChart" width="150" height="150"></canvas>
                <div class="chart-number" id="chart-percentage"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function updateDate() {
            const now = new Date();
            const days = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
            const dayOfWeek = days[now.getDay()];
            const day = String(now.getDate()).padStart(2, '0');
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const year = now.getFullYear();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');

            const formattedDate = `${day}-${month}-${year} ${hours}:${minutes} ${dayOfWeek}`;
            document.getElementById('current-date').textContent = formattedDate;
        }

        setInterval(updateDate, 1000); // Update every second

        <?php
        require '../../backend/config/database.php'; // Ajuste o caminho

        try { 
            $totalTarefasStmt = $pdo->query("SELECT COUNT(*) FROM tarefas");
            $totalTarefas = $totalTarefasStmt->fetchColumn();

            $concluidasStmt = $pdo->query("SELECT COUNT(*) FROM tarefas WHERE status = 'FINALIZADO'");
            $tarefasConcluidas = $concluidasStmt->fetchColumn();

            $porcentagemRealizada = ($totalTarefas > 0) ? round(($tarefasConcluidas / $totalTarefas) * 100) : 0;
            $porcentagemRestante = 100 - $porcentagemRealizada;

            echo "const tarefasRealizadas = " . json_encode($tarefasConcluidas) . ";";
            echo "const totalTarefas = " . json_encode($totalTarefas) . ";";
            echo "const porcentagemRealizada = " . json_encode($porcentagemRealizada) . ";";
            echo "const porcentagemRestante = " . json_encode($porcentagemRestante) . ";";

        } catch (PDOException $e) {
            echo "console.error('Erro ao buscar dados do relatório: " . $e->getMessage() . "');";
            echo "const tarefasRealizadas = 0;";
            echo "const totalTarefas = 0;";
            echo "const porcentagemRealizada = 0;";
            echo "const porcentagemRestante = 100;";
        }
        ?>

        const ctx = document.getElementById('taskProgressChart').getContext('2d');
        const taskProgressChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [porcentagemRealizada, porcentagemRestante],
                    backgroundColor: ['#1890ff', '#e6f7ff'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: false,
                maintainAspectRatio: false,
                cutout: '60%', // Cria o efeito de "donut"
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (context.dataIndex === 0) {
                                    return `${context.parsed}% Concluídas`;
                                } else {
                                    return `${context.parsed}% Pendentes`;
                                }
                            }
                        }
                    }
                }
            }
        });

        // Atualizar o texto central com a porcentagem
        document.getElementById('chart-percentage').textContent = `${porcentagemRealizada}%`;
    </script>
</body>
</html>