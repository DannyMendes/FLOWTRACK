<?php
require '../../backend/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tema = filter_input(INPUT_POST, 'tema', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $prioridade = filter_input(INPUT_POST, 'prioridade', FILTER_SANITIZE_FULL_SPECIAL_CHARS); // ou posso validar com um array de opções
    $data_estimada = filter_input(INPUT_POST, 'data_estimada', FILTER_SANITIZE_FULL_SPECIAL_CHARS); //opç FILTER_VALIDATE_REGEXP  .formato data
    $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ($tema && $prioridade && $data_estimada && $descricao) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tarefas (tema, prioridade, data_estimada, descricao, data_criacao, status) VALUES (:tema, :prioridade, :data_estimada, :descricao, NOW(), 'pendente')");
            $stmt->bindParam(':tema', $tema);
            $stmt->bindParam(':prioridade', $prioridade);
            $stmt->bindParam(':data_estimada', $data_estimada);
            $stmt->bindParam(':descricao', $descricao);

            if ($stmt->execute()) {
                header("Location: /FLOWTRACK/Frontend/dashboard-gestao/dashboard.php?mensagem=Tarefa adicionada com sucesso!");
                exit();
            } else {
                header("Location: /FLOWTRACK/Frontend/adicionar-tarefas/adicionar-tarefa.php?erro=Erro ao adicionar tarefa.");
                exit();
            }
        } catch (PDOException $e) {
            header("Location: /FLOWTRACK/Frontend/adicionar-tarefas/adicionar-tarefa.php?erro=Erro no banco de dados: " . urlencode($e->getMessage()));
            exit();
        }
    } else {
        header("Location: /FLOWTRACK/Frontend/adicionar-tarefas/adicionar-tarefa.php?erro=Por favor, preencha todos os campos.");
        exit();
    }
} else {
    header("Location: /FLOWTRACK/Frontend/adicionar-tarefas/adicionar-tarefa.php?erro=Método de requisição inválido.");
    exit();
}
?>