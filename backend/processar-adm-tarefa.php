<?php
// backend/processar_adm_tarefa.php

session_start(); // acessar ID logado


if ($_SERVER["REQUEST_METHOD"] == "POST") { 
    $tema = filter_input(INPUT_POST, 'tema', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $local = filter_input(INPUT_POST, 'local', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null;
    $prioridade = filter_input(INPUT_POST, 'prioridade', FILTER_SANITIZE_FULL_SPECIAL_CHARS); 
    $data_estimada = filter_input(INPUT_POST, 'data');
    $usuario_criacao = $_SESSION['id_usuario'] ?? null; 

    $erro_campos = false;
    $mensagem_erro = "";

    // Verifica se todos os campos obrigatórios foram preenchidos
    if (empty($tema) || empty($descricao) || empty($prioridade) || empty($data_estimada)) {
        $erro_campos = true;
        $mensagem_erro = "Preencher todos os campos obrigatórios.";
    }

    if (!$erro_campos) {
        require 'config/database.php';

        try {
            // Define ID do status "Pendente" e Consulte BD
            $stmt_status = $pdo->prepare("SELECT id FROM status_tarefas WHERE nome = 'Pendente'");
            $stmt_status->execute();
            $status_pendente = $stmt_status->fetch(PDO::FETCH_ASSOC);

            if ($status_pendente) {
                $status_id_pendente = $status_pendente['id'];

                // Prepare a query de inserção
                $stmt = $pdo->prepare("INSERT INTO tarefas (tema, descricao, local, prioridade, data_estimada, status, data_criacao, usuario_criacao) VALUES (:tema, :descricao, :local, :prioridade, :data_estimada, :status, NOW(), :usuario_criacao)");
                $stmt->bindParam(':tema', $tema);
                $stmt->bindParam(':descricao', $descricao);
                $stmt->bindParam(':local', $local);
                $stmt->bindParam(':prioridade', $prioridade);
                $stmt->bindParam(':data_estimada', $data_estimada);
                $stmt->bindParam(':status', $status_id_pendente, PDO::PARAM_INT); // Usar o ID de "Pendente"
                $stmt->bindParam(':usuario_criacao', $usuario_criacao, PDO::PARAM_INT); // Assumindo que usuario_criacao é um inteiro
                $stmt->execute();

                header("Location: /FLOWTRACK/Frontend/adicionar-tarefas/adicionar-tarefa.php?mensagem=tarefa_adicionada"); // Redirecionar de volta com mensagem de sucesso
                exit();
            } else {
                $mensagem_erro = "Erro: Status 'Pendente' não encontrado.";
            }

        } catch (PDOException $e) { //add pagina de erro
            $mensagem_erro = "Erro ao adicionar a tarefa: " . $e->getMessage();
        }
    }

    //  erro (campos vazios ou erro no banco), armazena na sessão
    $_SESSION['erro_cadastro_tarefa'] = $mensagem_erro;
    header("Location: /FLOWTRACK/Frontend/adicionar-tarefas/adicionar-tarefa.php"); // Redireciona de volta ao formulário
    exit();

} else {
    // Se acessar diretamente sem ser por POST
    header("Location: /FLOWTRACK/Frontend/adicionar-tarefas/adicionar-tarefa.php");
    exit();
}
?>