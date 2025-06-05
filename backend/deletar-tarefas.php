<?php
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $tarefaIdToDelete = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);

    if ($tarefaIdToDelete !== false) {
        try {
            $stmt = $pdo->prepare("DELETE FROM tarefas WHERE id = :id");
            $stmt->bindParam(':id', $tarefaIdToDelete, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                session_start();
                $_SESSION['sucesso_deletar_tarefa'] = "Tarefa deletada com sucesso.";
            } else {
                session_start();
                $_SESSION['erro_deletar_tarefa'] = "Tarefa não encontrada.";
            }
            header("Location: /FLOWTRACK/Frontend/dashboard-gestao/dashboard.php");
            exit();

        } catch (PDOException $e) {
            session_start();
            $_SESSION['erro_deletar_tarefa'] = "Erro ao deletar a tarefa: " . $e->getMessage();
            header("Location: /FLOWTRACK/Frontend/dashboard-gestao/dashboard.php");
            exit();
        }

    } else {
        session_start();
        $_SESSION['erro_deletar_tarefa'] = "ID da tarefa inválido.";
        header("Location: /FLOWTRACK/Frontend/dashboard-gestao/dashboard.php");
        exit();
    }

} else {
    session_start();
    $_SESSION['erro_deletar_tarefa'] = "Requisição inválida para deletar a tarefa.";
    header("Location: /FLOWTRACK/Frontend/dashboard-gestao/dashboard.php");
    exit();
}
?>