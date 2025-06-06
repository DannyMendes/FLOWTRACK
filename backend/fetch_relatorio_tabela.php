<?php
session_start(); // Inicia a sessão para controle de acesso

// --- Controle de Acesso (Importante!) ---
// Apenas usuários logados e com tipo de acesso 'Administrador' devem poder acessar esta página.
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['acesso_usuario']) || $_SESSION['acesso_usuario'] !== 'Administrador') {
    $_SESSION['erro_acesso'] = "Você não tem permissão para acessar esta página.";
    header("Location: /FLOWTRACK/Frontend/pagina-login/index.php"); // Redireciona para a página de login
    exit();
}

// Inclui o arquivo de conexão com o banco de dados
require '../../backend/config/database.php'; // Ajuste o caminho se necessário

// Variáveis para manter os valores do filtro na página após submissão
$filter_date = isset($_GET['filter_date']) ? htmlspecialchars($_GET['filter_date']) : '';
$filter_status = isset($_GET['filter_status']) ? htmlspecialchars($_GET['filter_status']) : '';
$filter_period = isset($_GET['filter_period']) ? htmlspecialchars($_GET['filter_period']) : '';
$search_tema = isset($_GET['search_tema']) ? htmlspecialchars($_GET['search_tema']) : '';

// Variável para controlar a visibilidade da tabela
$show_table = false;
$tarefas = []; // Inicializa a variável $tarefas

// Lógica de filtragem e busca de dados (similar ao fetch_relatorio_tabela.php, mas agora aqui)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['apply_filter']) || isset($_GET['search_tema']))) {
    $show_table = true; // Mostra a tabela se um filtro foi aplicado ou pesquisa realizada

    $where = "WHERE 1=1";
    $params = [];

    if (!empty($filter_date)) {
        $where .= " AND t.data_estimada = :filter_date";
        $params[':filter_date'] = $filter_date;
    }

    if (!empty($filter_status)) {
        $where .= " AND t.status = :filter_status";
        $params[':filter_status'] = $filter_status;
    }

    if (!empty($filter_period)) {
        if ($filter_period == 'semana') {
            $where .= " AND t.data_estimada >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AND t.data_estimada <= DATE_ADD(CURDATE(), INTERVAL (6 - WEEKDAY(CURDATE())) DAY)";
        } elseif ($filter_period == 'mes') {
            $where .= " AND YEAR(t.data_estimada) = YEAR(CURDATE()) AND MONTH(t.data_estimada) = MONTH(CURDATE())";
        }
    }

    if (!empty($search_tema)) {
        $search_term = '%' . $search_tema . '%';
        $where .= " AND t.tema LIKE :search_tema";
        $params[':search_tema'] = $search_term;
    }

    $sql = "
        SELECT
            t.id,
            t.data_estimada,
            t.tema,
            t.descricao,
            t.local,
            t.prioridade,
            st.nome AS status_nome,
            t.status,
            t.data_criacao,
            t.data_alteracao,
            uc.nome AS usuario_criacao_nome,
            ua.nome AS usuario_alteracao_nome,
            (SELECT hs.motorista FROM historico_status hs WHERE hs.tarefa_id = t.id ORDER BY hs.data_hora DESC LIMIT 1) AS motorista_latest,
            (SELECT hs.matricula_carrinha FROM historico_status hs WHERE hs.tarefa_id = t.id ORDER BY hs.data_hora DESC LIMIT 1) AS matricula_carrinha_latest,
            (SELECT hs.descricao_trabalho FROM historico_status hs WHERE hs.tarefa_id = t.id ORDER BY hs.data_hora DESC LIMIT 1) AS descricao_trabalho_latest,
            (SELECT hs.resposta_pergunta1 FROM historico_status hs WHERE hs.tarefa_id = t.id ORDER BY hs.data_hora DESC LIMIT 1) AS resposta_pergunta1_latest,
            (SELECT hs.resposta_pergunta2 FROM historico_status hs WHERE hs.tarefa_id = t.id ORDER BY hs.data_hora DESC LIMIT 1) AS resposta_pergunta2_latest,
            GROUP_CONCAT(DISTINCT ft.caminho_arquivo ORDER BY ft.id SEPARATOR '|||') AS fotos_caminhos
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
    } catch (PDOException $e) {
        $log_dir = __DIR__ . '/../backend/logs'; // Caminho ajustado para o log
        if (!file_exists($log_dir) && !is_dir($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        $log_file = $log_dir . '/pdo_errors.log';
        $error_message = date('Y-m-d H:i:s') . " - PDOException (relatorio.php): " . $e->getMessage() . " na linha " . $e->getLine() . "\n";
        error_log($error_message, 3, $log_file);
        // Não exibe o erro completo para o usuário final, apenas uma mensagem genérica.
        echo '<div class="mensagem-erro">Erro ao buscar tarefas. Por favor, tente novamente mais tarde.</div>';
        $tarefas = []; // Garante que $tarefas seja um array vazio em caso de erro
    }
}

// Dados PHP para gráfico (mantido)
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