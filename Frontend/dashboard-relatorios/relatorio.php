<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard / Gestão - Relatórios</title>
    <link rel="stylesheet" href="relatorio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Estilos para a tabela aparecer na mesma página */
        .tasks-table-section {
            width: 100%;
            max-width: 1200px;
            margin-top: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            box-sizing: border-box;
            position: relative; /* Para posicionar o botão fechar */
            <?php echo $show_table ? '' : 'display: none;'; ?> /* Controla a visibilidade via PHP */
        }

        .tasks-table-section .close-table-button {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 24px;
            color: #aaa;
            cursor: pointer;
            z-index: 10;
        }

        .tasks-table-section .close-table-button:hover {
            color: #333;
        }

        .tasks-table-section h3 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .tasks-table-section .tasks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .tasks-table-section .tasks-table th,
        .tasks-table-section .tasks-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            word-break: break-word; /* Quebra palavras longas */
        }

        .tasks-table-section .tasks-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .tasks-table-section .tasks-table td img {
            max-width: 50px; /* Tamanho pequeno para as imagens */
            max-height: 50px;
            display: block; /* Remove espaço extra abaixo da imagem */
            margin: 0 auto; /* Centraliza a imagem na célula */
            object-fit: cover; /* Garante que a imagem preencha o espaço sem distorcer */
            border-radius: 3px;
        }
    </style>
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

        <form id="filter-form" class="top-bar">
            <div class="search-container">
                <input type="text" id="search-input" name="search_tema" placeholder="Pesquisar por Tema" value="<?php echo $search_tema; ?>">
                <i class="fas fa-times clear-icon"></i>
            </div>
            <button type="button" class="generate-report-button" id="generate-report-button">
                Gerar relatório <i class="far fa-file-alt report-icon"></i>
            </button>
        </form>
        
        <form id="filter-form-details" class="filter-container">
            <input type="hidden" name="search_tema" value="<?php echo $search_tema; ?>"> <input type="date" id="filter-date" name="filter_date"
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

            <button type="submit" name="apply_filter" value="1" id="apply-filter-button">Filtrar Tarefas</button>
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

    <div id="tasks-table-section" class="tasks-table-section">
        <span class="close-table-button" id="close-table-button">&times;</span>
        <h3>Relatório de Tarefas Filtradas</h3>
        <div class="tasks-table-container">
            <table class="tasks-table">
                <thead>
                    <tr>
                        <th>Data Est.</th>
                        <th>Tema</th>
                        <th>Descrição Tarefa</th>
                        <th>Local</th>
                        <th>Prioridade</th>
                        <th>Status</th>
                        <th>Criado Por</th>
                        <th>Última Alt. Por</th>
                        <th>Data Criação</th>
                        <th>Data Última Alt.</th>
                        <th>Motorista (Últ.)</th>
                        <th>Matrícula (Últ.)</th>
                        <th>Desc. Final Trab. (Últ.)</th>
                        <th>Resposta 1 (Últ.)</th>
                        <th>Resposta 2 (Últ.)</th>
                        <th>Fotos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($tarefas) > 0): ?>
                        <?php foreach ($tarefas as $tarefa): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tarefa['data_estimada']); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['tema']); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['descricao']); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['local']); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['prioridade']); ?></td>
                                <td data-status-id="<?php echo htmlspecialchars($tarefa['status'] ?? ''); ?>"><?php echo htmlspecialchars($tarefa['status_nome'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['usuario_criacao_nome'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['usuario_alteracao_nome'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['data_criacao']); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['data_alteracao']); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['motorista_latest'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['matricula_carrinha_latest'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['descricao_trabalho_latest'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['resposta_pergunta1_latest'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['resposta_pergunta2_latest'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php
                                    if (!empty($tarefa['fotos_caminhos'])) {
                                        // Usa '|||' como delimitador para evitar problemas com vírgulas no caminho do arquivo
                                        $fotos_arr = explode('|||', $tarefa['fotos_caminhos']);
                                        foreach ($fotos_arr as $caminho_foto) {
                                            $caminho_foto = htmlspecialchars(trim($caminho_foto));
                                            if (!empty($caminho_foto)) {
                                                // Verifica se o caminho da foto é válido e o arquivo existe
                                                // IMPORTANTE: Ajuste o caminho base para as fotos conforme seu servidor
                                                $base_path = '/FLOWTRACK/uploads/'; // Exemplo: ajuste para o diretório de uploads
                                                $full_path = $base_path . basename($caminho_foto); // Use basename para evitar subir diretórios se o caminho completo for salvo

                                                // Você pode adicionar uma verificação de file_exists() aqui se os arquivos estiverem acessíveis pelo servidor
                                                // Ex: if (file_exists($_SERVER['DOCUMENT_ROOT'] . $full_path)) {
                                                echo '<img src="' . $full_path . '" alt="Foto da Tarefa" style="max-width: 50px; max-height: 50px; margin: 2px; border: 1px solid #ddd;">';
                                                // } else {
                                                //     echo '<span>[Imagem não encontrada]</span>';
                                                // }
                                            }
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="16">Nenhuma tarefa encontrada com os filtros selecionados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
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

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const clearIcon = document.querySelector('.search-container .clear-icon');
            const generateReportButton = document.getElementById('generate-report-button');
            const closeTableButton = document.getElementById('close-table-button');
            const tasksTableSection = document.getElementById('tasks-table-section');
            const filterFormTopBar = document.getElementById('filter-form'); // O formulário da top-bar (pesquisa)
            const filterFormDetails = document.getElementById('filter-form-details'); // O formulário dos filtros detalhados

            // Lógica para limpar o campo de pesquisa
            if (clearIcon) {
                clearIcon.addEventListener('click', function() {
                    searchInput.value = '';
                    // Opcional: Re-enviar o formulário para limpar a pesquisa ou fazer algo mais
                    // filterFormDetails.submit(); // Pode submeter para atualizar a tabela
                });
            }

            // O botão "Filtrar Tarefas" no filter-form-details já submete o formulário via PHP (GET)
            // e a tabela será exibida automaticamente se houver resultados, controlado pelo PHP.

            // Lógica para o botão "Gerar relatório" (imprime a página)
            if (generateReportButton) {
                generateReportButton.addEventListener('click', function() {
                    window.print(); // Imprime a página atual
                });
            }

            // Lógica para ocultar a tabela quando o "X" é clicado
            if (closeTableButton) {
                closeTableButton.addEventListener('click', function() {
                    tasksTableSection.style.display = 'none';
                    // Opcional: Remover os parâmetros de filtro da URL para 'resetar' o estado
                    const url = new URL(window.location.href);
                    url.searchParams.delete('filter_date');
                    url.searchParams.delete('filter_status');
                    url.searchParams.delete('filter_period');
                    url.searchParams.delete('search_tema');
                    url.searchParams.delete('apply_filter');
                    window.history.pushState({}, '', url);
                });
            }

            // Event listener para o formulário de pesquisa (top-bar)
            // Quando a pesquisa é feita aqui, ele deve enviar os filtros também.
            // Para isso, vamos fazer com que o botão Filtrar Tarefas seja o único a submeter o formulário principal.
            // E a barra de pesquisa atualiza o input hidden no formulário de detalhes.
            searchInput.addEventListener('input', function() {
                filterFormDetails.querySelector('input[name="search_tema"]').value = this.value;
            });

            // Se a página foi carregada com filtros, garante que a seção da tabela esteja visível
            // (Isso já está controlado pelo PHP com `$show_table`)
        });
    </script>

</body>

</html>