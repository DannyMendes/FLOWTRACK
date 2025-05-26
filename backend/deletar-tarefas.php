<?php
require 'config/database.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $tarefaIdToDelete = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

    try {
        $stmt = $pdo->prepare("DELETE FROM tarefas WHERE id = :id");
        $stmt->bindParam(':id', $tarefaIdToDelete, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo 'success';
        } else {
            echo 'Tarefa não encontrada.';
        }

    } catch (PDOException $e) {
        echo 'Erro ao deletar a tarefa: ' . $e->getMessage();
    }

} else {
    echo 'ID da tarefa inválido.';
}
?>