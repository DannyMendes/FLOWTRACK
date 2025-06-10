<?php
// Inicia a sessão no início do script, antes de qualquer saída
session_start(); 
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $tarefaIdToDelete = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);

    if ($tarefaIdToDelete !== false) {
        try {
            // Inicia uma transação. Isso garante que, se qualquer consulta falhar,
            // todas as operações dentro do bloco de transação serão revertidas.
            $pdo->beginTransaction();

            // 1. Primeiro, deleta todos os registros relacionados na tabela historico_status
            // que fazem referência à tarefa que será deletada.
            $stmt_delete_historico = $pdo->prepare("DELETE FROM historico_status WHERE tarefa_id = :tarefa_id");
            $stmt_delete_historico->bindParam(':tarefa_id', $tarefaIdToDelete, PDO::PARAM_INT);
            $stmt_delete_historico->execute();

            // 2. Agora que os registros filhos foram removidos, podemos deletar a tarefa da tabela 'tarefas'.
            $stmt_delete_tarefa = $pdo->prepare("DELETE FROM tarefas WHERE id = :id");
            $stmt_delete_tarefa->bindParam(':id', $tarefaIdToDelete, PDO::PARAM_INT);
            $stmt_delete_tarefa->execute();

            // Verifica se a tarefa foi realmente deletada (mesmo que não tenha histórico, deve funcionar)
            if ($stmt_delete_tarefa->rowCount() > 0) {
                // Confirma a transação se tudo correu bem
                $pdo->commit();
                $_SESSION['sucesso_deletar_tarefa'] = "Tarefa e seu histórico deletados com sucesso.";
            } else {
                // Se a tarefa não foi encontrada para deletar (mesmo depois de tentar o histórico),
                // reverta a transação (embora para este caso específico, não haveria nada para reverter
                // se o registro principal não existia).
                $pdo->rollBack();
                $_SESSION['erro_deletar_tarefa'] = "Tarefa não encontrada para deletar.";
            }
            header("Location: /FLOWTRACK/Frontend/dashboard-gestao/dashboard.php");
            exit();

        } catch (PDOException $e) {
            // Em caso de qualquer erro, reverte todas as operações da transação.
            $pdo->rollBack();
            // Log do erro completo para depuração (opcional, pode ser removido em produção)
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
