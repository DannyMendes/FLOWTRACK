<?php
// backend/fetch_relatorio_tabela.php

session_start();

// --- Controle de Acesso (Importante!) ---
// Apenas usuários logados e com tipo de acesso 'Administrador' devem poder acessar este endpoint.
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['acesso_usuario']) || $_SESSION['acesso_usuario'] !== 'Administrador') {
    http_response_code(403); // Acesso Proibido
    echo "Você não tem permissão para acessar este recurso.";
    exit();
}

require 'config/database.php'; // Caminho correto para o seu arquivo de conexão

// Inicia a construção da consulta SQL
$where = "WHERE 1=1"; // Cláusula WHERE inicial para facilitar a adição de filtros
$params = []; // Array para armazenar os parâmetros da consulta preparada

// Filtro por data estimada
if (isset($_GET['filter_date']) && !empty($_GET['filter_date'])) {
    $where .= " AND t.data_estimada = :filter_date";
    $params[':filter_date'] = $_GET['filter_date'];
}

// Filtro por status
if (isset($_GET['filter_status']) && !empty($_GET['filter_status'])) {
    $where .= " AND t.status = :filter_status";
    $params[':filter_status'] = $_GET['filter_status'];
}

// Filtro por período (semana/mês)
if (isset($_GET['filter_period']) && !empty($_GET['filter_period'])) {
    if ($_GET['filter_period'] == 'semana') {
        $where .= " AND t.data_estimada >= DATE(NOW() - INTERVAL (WEEKDAY(NOW())) DAY)
                     AND t.data_estimada < DATE(NOW() + INTERVAL (7 - WEEKDAY(NOW())) DAY)";
    } elseif ($_GET['filter_period'] == 'mes') {
        $where .= " AND YEAR(t.data_estimada) = YEAR(CURDATE()) 
                     AND MONTH(t.data_estimada) = MONTH(CURDATE())";
    }
}

// Filtro por Tema da Tarefa (agora vem do input da top-bar)
if (isset($_GET['search_tema']) && !empty($_GET['search_tema'])) {
    $search_term = '%' . $_GET['search_tema'] . '%';
    $where .= " AND t.tema LIKE :search_tema";
    $params[':search_tema'] = $search_term;
}

// Consulta SQL completa com todas as junções e subqueries para os novos campos
$sql = "
    SELECT
        t.id,
        t.data_estimada,
        t.tema,
        t.descricao,
        t.local,
        t.prioridade,
        st.nome AS status_nome,
        t.status, -- Adicionado para poder usar o status_id no CSS se necessário
        t.data_criacao,
        t.data_alteracao,
        uc.nome AS usuario_criacao_nome,
        ua.nome AS usuario_alteracao_nome,
        (SELECT hs.motorista FROM historico_status hs WHERE hs.tarefa_id = t.id ORDER BY hs.data_hora DESC LIMIT 1) AS motorista_latest,
        (SELECT hs.matricula_carrinha FROM historico_status hs WHERE hs.tarefa_id = t.id ORDER BY hs.data_hora DESC LIMIT 1) AS matricula_carrinha_latest,
        (SELECT hs.descricao_trabalho FROM historico_status hs WHERE hs.tarefa_id = t.id ORDER BY hs.data_hora DESC LIMIT 1) AS descricao_trabalho_latest,
        (SELECT hs.resposta_pergunta1 FROM historico_status hs WHERE hs.tarefa_id = t.id ORDER BY hs.data_hora DESC LIMIT 1) AS resposta_pergunta1_latest,
        (SELECT hs.resposta_pergunta2 FROM historico_status hs WHERE hs.tarefa_id = t.id ORDER BY hs.data_hora DESC LIMIT 1) AS resposta_pergunta2_latest,
        GROUP_CONCAT(DISTINCT ft.caminho_arquivo ORDER BY ft.id SEPARATOR '; ') AS fotos_caminhos
    FROM tarefas AS t
    INNER JOIN status_tarefas AS st ON t.status = st.id
    LEFT JOIN usuarios AS uc ON t.usuario_criacao = uc.id
    LEFT JOIN usuarios AS ua ON t.usuario_alteracao = ua.id
    LEFT JOIN historico_status AS hs_all ON t.id = hs_all.tarefa_id
    LEFT JOIN fotos_trabalho AS ft ON hs_all.id = ft.historico_status_id
    $where
    GROUP BY t.id
    ORDER BY t.data_estimada DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tarefas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Inicia a construção da tabela HTML
    echo '<table class="tasks-table" id="modal-tasks-table">'; 
    echo '<thead>';
    echo '<tr>';
    echo '<th>Data Est.</th>'; 
    echo '<th>Tema</th>';
    echo '<th>Descrição Tarefa</th>';
    echo '<th>Local</th>';
    echo '<th>Prioridade</th>';
    echo '<th>Status</th>';
    echo '<th>Criado Por</th>';
    echo '<th>Última Alt. Por</th>';
    echo '<th>Data Criação</th>';
    echo '<th>Data Última Alt.</th>';
    echo '<th>Motorista (Últ.)</th>';
    echo '<th>Matrícula (Últ.)</th>';
    echo '<th>Desc. Final Trab. (Últ.)</th>';
    echo '<th>Resposta 1 (Últ.)</th>';
    echo '<th>Resposta 2 (Últ.)</th>';
    echo '<th>Fotos (Caminhos)</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody id="modal-tasks-table-body">';

    if (count($tarefas) > 0) {
        foreach ($tarefas as $tarefa) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($tarefa['data_estimada']) . '</td>';
            echo '<td>' . htmlspecialchars($tarefa['tema']) . '</td>';
            echo '<td>' . htmlspecialchars($tarefa['descricao']) . '</td>';
            echo '<td>' . htmlspecialchars($tarefa['local']) . '</td>';
            echo '<td>' . htmlspecialchars($tarefa['prioridade']) . '</td>';
            echo '<td data-status-id="' . htmlspecialchars($tarefa['status'] ?? '') . '">' . htmlspecialchars($tarefa['status_nome'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($tarefa['usuario_criacao_nome'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($tarefa['usuario_alteracao_nome'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($tarefa['data_criacao']) . '</td>';
            echo '<td>' . htmlspecialchars($tarefa['data_alteracao']) . '</td>';
            echo '<td>' . htmlspecialchars($tarefa['motorista_latest'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($tarefa['matricula_carrinha_latest'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($tarefa['descricao_trabalho_latest'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($tarefa['resposta_pergunta1_latest'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($tarefa['resposta_pergunta2_latest'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($tarefa['fotos_caminhos'] ?? 'N/A') . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="16">Nenhuma tarefa encontrada com os filtros selecionados.</td></tr>';
    }

    echo '</tbody>';
    echo '</table>';

} catch (PDOException $e) {
    http_response_code(500); 
    $log_dir = __DIR__ . '/logs';
    if (!file_exists($log_dir) && !is_dir($log_dir)) {
        mkdir($log_dir, 0777, true); 
    }
    $log_file = $log_dir . '/pdo_errors.log';
    $error_message = date('Y-m-d H:i:s') . " - PDOException (fetch_relatorio_tabela): " . $e->getMessage() . " na linha " . $e->getLine() . "\n";
    error_log($error_message, 3, $log_file);
    echo "Erro ao buscar tarefas. Por favor, tente novamente mais tarde.";
}
?>