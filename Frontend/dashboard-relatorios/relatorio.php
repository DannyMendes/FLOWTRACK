<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard / Gestão</title>
    <link rel="stylesheet" href="relatorio.css">
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
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/gerir-usuarios/usuarios.php'">Gerir usuário</button>
        <button class="botao-navegacao ativo" onclick="window.location.href='/FLOWTRACK/Frontend/dashboard-relatorios/relatorio.php'">Relatório</button>
    </div>
    <div class="date-time" id="current-date"></div>
    <div class="top-bar">
        <div class="search-container">
            <input type="text" placeholder="Pesquisar">
            <i class="fas fa-times clear-icon"></i>
        </div>
        <button class="generate-report-button">
            Gerar relatório <i class="far fa-file-alt report-icon"></i>
        </button>
    </div>
    <div class="filter-bar">
        <div class="filter-box">
            <i class="far fa-calendar-alt calendar-icon"></i>
            <span>Selecionar data</span>
        </div>
        <div class="filter-box">
            <span>Status</span>
        </div>
        <div class="filter-box">
            <span>Filtro personalizado</span>
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