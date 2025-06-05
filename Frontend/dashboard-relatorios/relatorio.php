<?php
session_start(); // Inicia a sessão para controle de acesso

// --- Controle de Acesso (Importante!) ---
// Apenas usuários logados e com tipo de acesso 'Administrador' devem poder acessar esta página.
// Ajuste 'Administrador' para o valor exato na sua coluna de banco de dados, se diferente.
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['acesso_usuario']) || $_SESSION['acesso_usuario'] !== 'Administrador') {
    $_SESSION['erro_acesso'] = "Você não tem permissão para acessar esta página.";
    header("Location: /FLOWTRACK/Frontend/pagina-login/index.php"); // Redireciona para a página de login
    exit();
}

// Inclui o arquivo de conexão com o banco de dados
require '../../backend/config/database.php';

// Variáveis para manter os valores do filtro na página após submissão (para o formulário principal)
$filter_date = isset($_GET['filter_date']) ? htmlspecialchars($_GET['filter_date']) : '';
$filter_status = isset($_GET['filter_status']) ? htmlspecialchars($_GET['filter_status']) : '';
$filter_period = isset($_GET['filter_period']) ? htmlspecialchars($_GET['filter_period']) : '';
// Mantém o valor do campo de pesquisa após o envio do formulário
$search_tema = isset($_GET['search_tema']) ? htmlspecialchars($_GET['search_tema']) : ''; // Este será movido para o form

// Não há mais a renderização da tabela aqui, pois será via AJAX
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard / Gestão - Relatórios</title>
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

    <div class="content">
        <?php
        // Exibe mensagens de erro de acesso, se houver
        if (isset($_SESSION['erro_acesso'])) {
            echo '<div class="mensagem-erro">' . htmlspecialchars($_SESSION['erro_acesso']) . '</div>';
            unset($_SESSION['erro_acesso']);
        }
        ?>

        <div class="top-bar">
            <div class="search-container">
                <input type="text" id="search-input" name="search_tema" placeholder="Pesquisar por Tema" value="<?php echo $search_tema; ?>">
                <i class="fas fa-times clear-icon"></i>
            </div>
            <button type="button" class="generate-report-button" id="generate-report-button">
                Gerar relatório <i class="far fa-file-alt report-icon"></i>
            </button>
        </div>
        
        <form id="filter-form" class="filter-container">
            <input type="date" id="filter-date" name="filter_date"
                value="<?php echo $filter_date; ?>">

            <select id="filter-status" name="filter_status">
                <option value="">Status</option>
                <?php
                try {
                    $stmt_status = $pdo->query("SELECT id, nome FROM status_tarefas");
                    $statuses = $stmt_status->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($statuses as $status) {
                        $selected = ($filter_status == $status['id']) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($status['id']) . "' $selected>" . htmlspecialchars($status['nome']) . "</option>";
                    }
                } catch (PDOException $e) {
                    echo "<option value='' disabled>Erro ao buscar status</option>";
                }
                ?>
            </select>

            <select id="filter-period" name="filter_period">
                <option value="">Filtro personalizado</option>
                <option value="semana" <?php echo ($filter_period == 'semana') ? 'selected' : ''; ?>>Esta Semana</option>
                <option value="mes" <?php echo ($filter_period == 'mes') ? 'selected' : ''; ?>>Este Mês</option>
            </select>

            <button type="submit" id="apply-filter-button">Filtrar Tarefas</button>
        </form>

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

    <div id="reportModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Relatório de Tarefas Filtradas</h3>
            <div id="modal-table-container" class="tasks-table-container">
                </div>
            <div class="modal-footer">
                <button id="print-pdf-button" class="print-pdf-button">
                    <i class="fas fa-file-pdf"></i> Imprimir em PDF
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Atualiza data e hora atuais
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
        setInterval(updateDate, 1000); // Atualiza a cada segundo

        // Dados PHP para gráfico (mantido)
        <?php
        try {
            $totalTarefasStmt = $pdo->query("SELECT COUNT(*) FROM tarefas");
            $totalTarefas = $totalTarefasStmt->fetchColumn();

            $stmt_finalizado_id = $pdo->prepare("SELECT id FROM status_tarefas WHERE nome = 'FINALIZADO'");
            $stmt_finalizado_id->execute();
            $finalizado_id = $stmt_finalizado_id->fetchColumn();

            $tarefasConcluidas = 0;
            if ($finalizado_id) {
                $concluidasStmt = $pdo->prepare("SELECT COUNT(*) FROM tarefas WHERE status = :finalizado_id");
                $concluidasStmt->bindParam(':finalizado_id', $finalizado_id);
                $concluidasStmt->execute();
                $tarefasConcluidas = $concluidasStmt->fetchColumn();
            }
            
            $porcentagemRealizada = ($totalTarefas > 0) ? round(($tarefasConcluidas / $totalTarefas) * 100) : 0;
        } catch (PDOException $e) {
            $porcentagemRealizada = 0;
        }
        ?>

        // Exibe porcentagem no centro do gráfico
        const ctx = document.getElementById('taskProgressChart').getContext('2d');
        const percentage = <?php echo $porcentagemRealizada; ?>;
        document.getElementById('chart-percentage').textContent = percentage + '%';

        // Configuração do gráfico
        const data = {
            labels: ['Concluído', 'Pendente'],
            datasets: [{
                data: [percentage, 100 - percentage],
                backgroundColor: ['#1890ff', '#ddd'],
                borderWidth: 0,
            }]
        };

        const options = {
            cutout: '80%',
            responsive: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            }
        };

        const taskProgressChart = new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: options
        });

        // --- Lógica do Modal e Requisição AJAX ---
        document.addEventListener('DOMContentLoaded', function() {
            const reportModal = document.getElementById('reportModal');
            const closeButton = document.querySelector('.close-button');
            const applyFilterButton = document.getElementById('apply-filter-button');
            const generateReportButton = document.getElementById('generate-report-button');
            const filterForm = document.getElementById('filter-form'); 
            const modalTableContainer = document.getElementById('modal-table-container');
            const printPdfButton = document.getElementById('print-pdf-button'); 

            // Elementos para pesquisa e limpar (agora na top-bar)
            const searchInput = document.getElementById('search-input'); 
            const clearIcon = document.querySelector('.search-container .clear-icon'); 

            // Event listener para limpar o campo de pesquisa
            if (clearIcon) {
                clearIcon.addEventListener('click', function() {
                    searchInput.value = ''; 
                });
            }

            // Função para buscar e exibir a tabela no modal
            async function fetchAndDisplayReport() {
                // Coleta os dados do formulário de filtros
                const formData = new FormData(filterForm); 
                // Adiciona o valor da pesquisa ao FormData
                formData.append('search_tema', searchInput.value); 
                const queryString = new URLSearchParams(formData).toString();

                try {
                    const response = await fetch(`../../backend/fetch_relatorio_tabela.php?${queryString}`);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const tableHtml = await response.text();
                    modalTableContainer.innerHTML = tableHtml; 
                    reportModal.style.display = 'block'; 
                } catch (error) {
                    console.error('Erro ao buscar o relatório:', error);
                    alert('Erro ao gerar o relatório. Por favor, tente novamente.');
                }
            }

            // Event Listener para o botão "Filtrar Tarefas" (submit do formulário)
            applyFilterButton.addEventListener('click', function(event) {
                event.preventDefault(); // Impede o envio padrão do formulário
                fetchAndDisplayReport();
            });

            // Event Listener para o botão "Gerar relatório" (na top-bar)
            generateReportButton.addEventListener('click', function() {
                fetchAndDisplayReport();
            });

            // Event Listener para o botão "Imprimir em PDF" (via navegador)
            printPdfButton.addEventListener('click', function() {
                const modalContent = document.getElementById('reportModal').querySelector('.modal-content');

                const printWindow = window.open('', '_blank');

                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = modalContent.innerHTML;

                const closeBtnInPrint = tempDiv.querySelector('.close-button');
                if (closeBtnInPrint) {
                    closeBtnInPrint.remove();
                }
                const printBtnInPrint = tempDiv.querySelector('#print-pdf-button');
                if (printBtnInPrint) {
                    printBtnInPrint.remove();
                }
                const modalFooterInPrint = tempDiv.querySelector('.modal-footer');
                if (modalFooterInPrint) {
                    if (modalFooterInPrint.children.length === 0) {
                        modalFooterInPrint.remove();
                    }
                }

                const contentToPrint = tempDiv.innerHTML;

                printWindow.document.write('<html><head><title>Relatório de Tarefas</title>');
                printWindow.document.write('<link rel="stylesheet" href="relatorio.css">'); 

                printWindow.document.write('<style>');
                printWindow.document.write(`
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    h3 { text-align: center; color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }

                    @media print {
                        body { margin: 0; }
                        
                        .modal-content .close-button,
                        .modal-footer {
                            display: none !important;
                        }
                        #reportModal, #reportModal .modal-content, #modal-table-container, .tasks-table {
                            visibility: visible !important;
                            display: block !important;
                            width: 100% !important;
                            max-width: none !important;
                            height: auto !important;
                            max-height: none !important;
                            background-color: transparent !important;
                            box-shadow: none !important;
                            padding: 0 !important;
                            position: static !important; 
                        }
                        .tasks-table-container {
                            overflow: visible !important; 
                        }
                    }
                `);
                printWindow.document.write('</style>');
                printWindow.document.write('</head><body>');
                printWindow.document.write(contentToPrint); 
                printWindow.document.write('</body></html>');
                printWindow.document.close(); 
                printWindow.focus(); 
                
                setTimeout(() => {
                    printWindow.print(); 
                }, 750); 
            });

            // Event Listener para fechar o modal
            closeButton.addEventListener('click', function() {
                reportModal.style.display = 'none';
            });

            // Fecha o modal se clicar fora dele
            window.addEventListener('click', function(event) {
                if (event.target == reportModal) {
                    reportModal.style.display = 'none';
                }
            });
        });
    </script>

</body>

</html>