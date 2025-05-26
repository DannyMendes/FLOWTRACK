<?php
// backend/listar_tarefas.php

header('Content-Type: application/json');
require 'config/database.php'; 

try {
      $stmt = $pdo->query("SELECT tarefa.data_estimada, tarefa.tema, tarefa.descricao, tarefa.prioridade, status.nome AS status FROM tarefas AS tarefa INNER JOIN status_tarefas AS status ON tarefa.status = status.id");
    $tarefas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($tarefas);
} catch (PDOException $e) {
    echo json_encode(['erro' => 'Erro ao buscar tarefas: ' . $e->getMessage()]);
    http_response_code(500); // Define o status de erro interno do servidor
}
?>