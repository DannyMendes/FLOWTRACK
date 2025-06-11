<?php
session_start(); // Inicia a sessão para verificar o usuário logado, se necessário
require '../../backend/config/database.php'; // Inclui a conexão com o banco de dados

// Habilita a exibição de erros para depuração (REMOVER EM PRODUÇÃO)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$tarefaId = null;
$tarefa = null;
$historico = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $tarefaId = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

    try {
        // 1. Buscar detalhes da tarefa principal
        $stmt_tarefa = $pdo->prepare("SELECT t.data_estimada, t.tema, t.prioridade, t.descricao, s.nome AS status_nome, t.usuario_criacao AS usuario_criador_id FROM tarefas t JOIN status_tarefas s ON t.status = s.id WHERE t.id = :id");
        $stmt_tarefa->bindParam(':id', $tarefaId, PDO::PARAM_INT);
        $stmt_tarefa->execute();
        $tarefa = $stmt_tarefa->fetch(PDO::FETCH_ASSOC);

        if ($tarefa) {
            // 2. Buscar histórico de status da tarefa
            // Usando LEFT JOIN para garantir que mesmo que um usuário ou status seja nulo, a entrada apareça
            $stmt_historico = $pdo->prepare("
                SELECT 
                    hs.id AS historico_id, 
                    hs.data_hora, 
                    st.nome AS status_nome_historico,
                    u.nome AS usuario_responsavel_nome,
                    hs.matricula_carrinha,
                    hs.motorista,
                    hs.verificacao_material,
                    hs.descricao_trabalho,
                    hs.resposta_pergunta1,
                    hs.resposta_pergunta2
                FROM historico_status hs
                JOIN status_tarefas st ON hs.status_id = st.id
                LEFT JOIN usuarios u ON hs.usuario_id = u.id
                WHERE hs.tarefa_id = :tarefa_id
                ORDER BY hs.data_hora DESC
            ");
            $stmt_historico->bindParam(':tarefa_id', $tarefaId, PDO::PARAM_INT);
            $stmt_historico->execute();
            $historico = $stmt_historico->fetchAll(PDO::FETCH_ASSOC);

            // Para cada entrada no histórico, buscar as fotos associadas
            foreach ($historico as &$entrada) { // Usar & para modificar o array original
                $stmt_fotos = $pdo->prepare("SELECT caminho_arquivo FROM fotos_trabalho WHERE historico_status_id = :historico_id");
                $stmt_fotos->bindParam(':historico_id', $entrada['historico_id'], PDO::PARAM_INT);
                $stmt_fotos->execute();
                $entrada['fotos'] = $stmt_fotos->fetchAll(PDO::FETCH_COLUMN); // Retorna apenas a coluna caminho_arquivo
            }
            unset($entrada); // Desreferenciar a variável após o loop
        }

    } catch (PDOException $e) {
        $erro = "Erro ao buscar detalhes da tarefa: " . htmlspecialchars($e->getMessage());
        error_log($erro); // Registra o erro no log do servidor
    }
} else {
    $erro = "ID da tarefa inválido ou não fornecido.";
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes Completos da Tarefa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            background-color: #f4f4f4;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }
        .header {
            display: flex;
            align-items: center;
            background-color: #00a6d0;
            color: white;
            padding: 15px 20px;
            width: 100%;
            box-sizing: border-box;
            justify-content: flex-start;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        .header .title {
            font-size: 20px;
            font-weight: bold;
            text-align: left;
            margin-left: 10px;
        }
        .container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 800px;
            margin-top: 20px;
            padding: 20px;
            box-sizing: border-box;
        }
        h1, h2 {
            color: #00a6d0;
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .task-details, .history-section {
            margin-bottom: 30px;
        }
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-item strong {
            color: #333;
        }
        .detail-item span {
            color: #666;
            text-align: right;
        }
        .history-entry {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .history-entry h3 {
            color: #008bb0;
            margin-top: 0;
            margin-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 5px;
        }
        .history-entry p {
            margin: 5px 0;
            color: #555;
        }
        .history-entry strong {
            color: #333;
        }
        .photos-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .photos-container img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .message {
            text-align: center;
            padding: 20px;
            color: #888;
        }
        .error-message {
            color: red;
            font-weight: bold;
            text-align: center;
        }
        /* Estilo para o botão de imprimir */
        .print-button {
            display: block; /* Para ocupar a largura total e centralizar com margin auto */
            margin: 20px auto; /* Centraliza o botão e adiciona espaçamento */
            padding: 10px 20px;
            background-color: #00a6d0; /* Cor azul do tema */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .print-button:hover {
            background-color: #008bb0; /* Tom mais escuro no hover */
        }

        /* Regras para impressão: Esconder elementos não necessários na impressão */
        @media print {
            .header {
                display: none; /* Esconde a barra de navegação no topo */
            }
            .print-button {
                display: none; /* Esconde o próprio botão de imprimir */
            }
            body {
                background-color: #fff; /* Fundo branco para impressão */
            }
            .container {
                box-shadow: none; /* Remove a sombra do container */
                border: none; /* Remove bordas, se houver */
                width: 100%; /* Ocupa a largura total na impressão */
                max-width: none;
                margin-top: 0;
                padding: 0;
            }
            h1, h2 {
                color: #000; /* Cores escuras para melhor contraste na impressão */
                border-bottom: 1px solid #ccc; /* Linha de separação mais suave */
            }
            /* Garantir que as imagens sejam impressas */
            .photos-container img {
                max-width: 100%; /* Ajusta imagens grandes */
                height: auto;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <i class="fas fa-arrow-left" style="cursor: pointer;" onclick="history.back()"></i>
        <span class="title">Detalhes da Tarefa</span>
    </div>

    <div class="container">
        <?php if (isset($erro)): ?>
            <p class="error-message"><?php echo $erro; ?></p>
        <?php elseif (!$tarefa): ?>
            <p class="message">Tarefa não encontrada.</p>
        <?php else: ?>
            <h1>Relatório detalhado da Tarefa: <?php echo htmlspecialchars($tarefa['tema']); ?></h1>

            <div class="task-details">
                <div class="detail-item">
                    <strong>Data Estimada:</strong>
                    <span><?php echo htmlspecialchars($tarefa['data_estimada']); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Tema:</strong>
                    <span><?php echo htmlspecialchars($tarefa['tema']); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Prioridade:</strong>
                    <span><?php echo htmlspecialchars($tarefa['prioridade']); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Status Atual:</strong>
                    <span><?php echo htmlspecialchars($tarefa['status_nome']); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Descrição:</strong>
                    <span><?php echo nl2br(htmlspecialchars($tarefa['descricao'])); ?></span>
                </div>
            </div>

            <h2 class="history-section-title">Histórico de Status</h2>
            <div class="history-section">
                <?php if (empty($historico)): ?>
                    <p class="message">Nenhum histórico de status encontrado para esta tarefa.</p>
                <?php else: ?>
                    <?php foreach ($historico as $entrada): ?>
                        <div class="history-entry">
                            <h3>Status: <?php echo htmlspecialchars($entrada['status_nome_historico']); ?></h3>
                            <p><strong>Responsável:</strong> <?php echo htmlspecialchars($entrada['usuario_responsavel_nome'] ?? 'N/A'); ?></p>
                            <p><strong>Data/Hora:</strong> <?php echo htmlspecialchars($entrada['data_hora']); ?></p>

                            <?php if (!empty($entrada['matricula_carrinha'])): ?>
                                <p><strong>Matrícula Carrinha:</strong> <?php echo htmlspecialchars($entrada['matricula_carrinha']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($entrada['motorista'])): ?>
                                <p><strong>Motorista:</strong> <?php echo htmlspecialchars($entrada['motorista']); ?></p>
                            <?php endif; ?>
                            <?php if (($entrada['verificacao_material'] !== null) && ($entrada['status_nome_historico'] === 'Em Rota')): ?>
                                <p><strong>Verificação de Material:</strong> <?php echo $entrada['verificacao_material'] ? 'Sim' : 'Não'; ?></p>
                            <?php endif; ?>
                            <?php if (!empty($entrada['descricao_trabalho'])): ?>
                                <p><strong>Descrição do Trabalho:</strong> <?php echo nl2br(htmlspecialchars($entrada['descricao_trabalho'])); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($entrada['resposta_pergunta1'])): ?>
                                <p><strong>Trabalho Conforme Planejado:</strong> <?php echo htmlspecialchars($entrada['resposta_pergunta1']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($entrada['resposta_pergunta2'])): ?>
                                <p><strong>Nível de Dificuldade:</strong> <?php echo htmlspecialchars($entrada['resposta_pergunta2']); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($entrada['fotos'])): ?>
                                <h4>Fotos Anexadas:</h4>
                                <div class="photos-container">
                                    <?php foreach ($entrada['fotos'] as $fotoCaminho): ?>
                                        <img src="<?php echo htmlspecialchars($fotoCaminho); ?>" alt="Foto da tarefa" onerror="this.onerror=null;this.src='https://placehold.co/100x100/A0A0A0/FFFFFF?text=Sem+Imagem';">
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <!-- Novo botão de imprimir -->
            <button class="print-button" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir Relatório
            </button>
        <?php endif; ?>
    </div>
</body>
</html>
