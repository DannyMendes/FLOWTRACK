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
$search_tema = isset($_GET['search_tema']) ? htmlspecialchars($_GET['search_tema']) : '';

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

    <!-- Header -->
    <div class="header">
        <i class="fas fa-bars menu-icon"></i>
        <span class="title">Dashboard / Gestão</span>
    </div>

    <!-- Navegação -->
    <div class="navegacao">
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/dashboard-gestao/dashboard.php'">Tarefas</button>
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/adicionar-tarefas/adicionar-tarefa.php'">Inserir tarefa</button>
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/gerir-usuarios/usuarios.php'">Gerir usuário</button>
        <button class="botao-navegacao ativo" onclick="window.location.href='/FLOWTRACK/Frontend/dashboard-relatorios/relatorio.php'">Relatório</button>
    </div>

    <!-- Data e hora atuais -->
    <div class="date-time" id="current-date"></div>

    <!-- Barra superior -->
    

    <!-- Conteúdo principal -->
    <div class="content">
        <?php
        // Exibe mensagens de erro de acesso, se houver
        if (isset($_SESSION['erro_acesso'])) {
            echo '<div class="mensagem-erro">' . htmlspecialchars($_SESSION['erro_acesso']) . '</div>';
            unset($_SESSION['erro_acesso']);
        }
        ?>

        <!-- Filtro de tarefas -->
        <!-- O formulário não terá mais um 'action' direto, será processado via JS -->
        <form id="filter-form" class="filter-container">
            <!-- Campo de pesquisa movido para DENTRO do formulário -->
            <div class="search-container">
                <input type="text" id="search-input" name="search_tema" placeholder="Pesquisar por Tema" value="<?php echo $search_tema; ?>">
                <i class="fas fa-times clear-icon"></i>
            </div>
            <div class="top-bar">
                <button class="generate-report-button" id="generate-report-button">
                Gerar relatório <i class="far fa-file-alt report-icon"></i>
            </button>
    </div>

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

        <!-- A tabela principal não será mais exibida aqui diretamente -->
        <!-- O conteúdo da tabela será carregado no modal -->
    </div>

    <!-- Container principal -->
    <div class="main-container">
        <!-- Sidebar com legenda -->
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

        <!-- Conteúdo principal com gráfico -->
        <div class="main-content">
            <div class="progress-chart">
                <canvas id="taskProgressChart" width="150" height="150"></canvas>
                <div class="chart-number" id="chart-percentage"></div>
            </div>
        </div>
    </div>

    <!-- Modal para a Tabela de Relatório -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Relatório de Tarefas Filtradas</h3>
            <div id="modal-table-container" class="tasks-table-container">
                <!-- A tabela será carregada aqui via JavaScript -->
            </div>
            <!-- NOVO: Rodapé do Modal com Botão de Imprimir -->
            <div class="modal-footer">
                <button id="print-pdf-button" class="print-pdf-button">
                    <i class="fas fa-file-pdf"></i> Imprimir em PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
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

        // Dados PHP para gráfico (mantido, pois não afeta o modal)
        <?php
        // A conexão com o banco de dados já foi feita no início do arquivo
        try {
            $totalTarefasStmt = $pdo->query("SELECT COUNT(*) FROM tarefas");
            $totalTarefas = $totalTarefasStmt->fetchColumn();

            // ATENÇÃO: Verifique o ID do status 'FINALIZADO' na sua tabela status_tarefas
            // Se 'FINALIZADO' não for um status, ajuste a consulta.
            // É mais robusto buscar o ID do status pelo nome:
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
            // Em um ambiente de produção, você pode logar o erro em vez de exibi-lo
            // console.error("Erro ao buscar dados do gráfico: " + e.getMessage()); // Linha corrigida
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
            const printPdfButton = document.getElementById('print-pdf-button'); // Referência ao botão de impressão

            // NOVOS ELEMENTOS PARA PESQUISA
            const searchInput = document.getElementById('search-input'); // Seleciona o campo de pesquisa
            const clearIcon = document.querySelector('.search-container .clear-icon'); // Seleciona o ícone de limpar

            // Event listener para limpar o campo de pesquisa
            if (clearIcon) {
                clearIcon.addEventListener('click', function() {
                    searchInput.value = ''; // Limpa o valor do input
                    // Opcional: Você pode chamar fetchAndDisplayReport() aqui se quiser que o filtro seja aplicado imediatamente após limpar.
                    // fetchAndDisplayReport();
                });
            }

            // Função para buscar e exibir a tabela no modal
            async function fetchAndDisplayReport() {
                const formData = new FormData(filterForm);
                const queryString = new URLSearchParams(formData).toString();

                try {
                    // Faz a requisição para o novo endpoint PHP
                    const response = await fetch(`../../backend/fetch_relatorio_tabela.php?${queryString}`);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const tableHtml = await response.text();
                    modalTableContainer.innerHTML = tableHtml; // Insere o HTML da tabela no modal
                    reportModal.style.display = 'block'; // Exibe o modal
                } catch (error) {
                    console.error('Erro ao buscar o relatório:', error);
                    // Você pode exibir uma mensagem de erro para o usuário aqui
                    alert('Erro ao gerar o relatório. Por favor, tente novamente.');
                }
            }

            // Event Listener para o botão "Filtrar Tarefas"
            applyFilterButton.addEventListener('click', function(event) {
                event.preventDefault(); // Impede o envio padrão do formulário
                fetchAndDisplayReport();
            });

            // Event Listener para o botão "Gerar relatório" (na barra superior)
            generateReportButton.addEventListener('click', function() {
                fetchAndDisplayReport();
            });

            // Event Listener para o botão "Imprimir em PDF" (AGORA É IMPRIMIR VIA NAVEGADOR)
            printPdfButton.addEventListener('click', function() {
                // Pega o conteúdo do modal
                const modalContent = document.getElementById('reportModal').querySelector('.modal-content');

                // Cria uma nova janela de impressão
                const printWindow = window.open('', '_blank');

                // Clona o modalContent para manipular sem afetar o DOM original
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = modalContent.innerHTML;

                // Remove o botão de fechar do conteúdo a ser impresso
                const closeBtnInPrint = tempDiv.querySelector('.close-button');
                if (closeBtnInPrint) {
                    closeBtnInPrint.remove();
                }
                // Remove o botão de imprimir do conteúdo a ser impresso
                const printBtnInPrint = tempDiv.querySelector('#print-pdf-button');
                if (printBtnInPrint) {
                    printBtnInPrint.remove();
                }
                // Remove o footer do modal se ele só contiver o botão de imprimir (ou se estiver vazio)
                const modalFooterInPrint = tempDiv.querySelector('.modal-footer');
                if (modalFooterInPrint) { // Verifica se o footer existe
                    // Se o footer não tiver mais filhos após remover o botão, remova o próprio footer
                    if (modalFooterInPrint.children.length === 0) {
                        modalFooterInPrint.remove();
                    }
                }

                const contentToPrint = tempDiv.innerHTML;

                printWindow.document.write('<html><head><title>Relatório de Tarefas</title>');
                // Inclui o CSS para que a impressão tenha o estilo correto.
                // Use um caminho absoluto se o `relatorio.css` não estiver na raiz do seu site
                // Por exemplo: printWindow.document.write('<link rel="stylesheet" href="/FLOWTRACK/Frontend/dashboard-relatorios/relatorio.css">');
                printWindow.document.write('<link rel="stylesheet" href="relatorio.css">'); // Mantenha este se o caminho relativo funcionar

                // Adicione estilos específicos para impressão aqui
                printWindow.document.write('<style>');
                printWindow.document.write(`
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    h3 { text-align: center; color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }

                    /* Estilos para garantir que o conteúdo do modal apareça na impressão */
                    @media print {
                        * {
                            visibility: visible !important;
                            display: block !important;
                        }
                        body { margin: 0; }
                        
                        .modal-content .close-button,
                        .modal-footer {
                            display: none !important;
                        }
                        .modal-content {
                            display: block !important;
                            width: auto !important;
                            max-width: none !important;
                            height: auto !important;
                            max-height: none !important;
                            background-color: transparent !important;
                            box-shadow: none !important;
                            padding: 0 !important;
                        }
                        .tasks-table-container, .tasks-table {
                            width: 100% !important;
                            overflow: visible !important;
                        }
                    }
                `);
                printWindow.document.write('</style>');
                printWindow.document.write('</head><body>');
                printWindow.document.write(contentToPrint); // Adiciona o conteúdo do modal (sem botões)
                printWindow.document.write('</body></html>');
                printWindow.document.close(); // Fecha o documento para que o navegador comece a carregar
                printWindow.focus(); // Foca na nova janela
                
                // Aguarda um curto período para o navegador renderizar o CSS e o HTML
                // antes de acionar a impressão.
                setTimeout(() => {
                    printWindow.print(); // Aciona a caixa de diálogo de impressão do navegador
                    printWindow.close(); // Opcional: fechar a janela após a impressão (usuário pode preferir mantê-la aberta)
                }, 750); // 750ms é um bom atraso, ajuste se necessário
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