<?php

session_start(); // Inicia a sessão
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $tarefaIdToDelete = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);

    if ($tarefaIdToDelete !== false) {
        try {
            // Inicia e se falhar são revertidas.
            $pdo->beginTransaction();

            // 1-deleta filhos
            $stmt_delete_historico = $pdo->prepare("DELETE FROM historico_status WHERE tarefa_id = :tarefa_id");
            $stmt_delete_historico->bindParam(':tarefa_id', $tarefaIdToDelete, PDO::PARAM_INT);
            $stmt_delete_historico->execute();

            //  deletar a tarefa 
            $stmt_delete_tarefa = $pdo->prepare("DELETE FROM tarefas WHERE id = :id");
            $stmt_delete_tarefa->bindParam(':id', $tarefaIdToDelete, PDO::PARAM_INT);
            $stmt_delete_tarefa->execute();

            // Ver se realmente deletada 
            if ($stmt_delete_tarefa->rowCount() > 0) {
                // Confirma ok
                $pdo->commit();
                $_SESSION['sucesso_deletar_tarefa'] = "Tarefa e seu histórico deletados com sucesso.";
            } else {
                // Se a tarefa não foi encontrada reverte
                $pdo->rollBack();
                $_SESSION['erro_deletar_tarefa'] = "Tarefa não encontrada pra deletar.";
            }
            header("Location: /FLOWTRACK/Frontend/dashboard-gestao/dashboard.php");
            exit();

        } catch (PDOException $e) {
            // Em caso de erro, reverte 
            $pdo->rollBack();
            // Log do erro completo para depuração 
            error_log("Erro ao deletar tarefa e histórico: " . $e->getMessage()); 
            $_SESSION['erro_deletar_tarefa'] = "Erro ao deletar a tarefa: " . $e->getMessage();
            header("Location: /FLOWTRACK/Frontend/dashboard-gestao/dashboard.php");
            exit();
        }

    } else {
        $_SESSION['erro_deletar_tarefa'] = "ID da tarefa inválido.";
        header("Location: /FLOWTRACK/Frontend/dashboard-gestao/dashboard.php");
        exit();
    }

} else {
    $_SESSION['erro_deletar_tarefa'] = "Requisição inválida para deletar a tarefa.";
    header("Location: /FLOWTRACK/Frontend/dashboard-gestao/dashboard.php");
    exit();
}
?>
