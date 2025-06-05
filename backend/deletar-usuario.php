<?php
require 'config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);

    if (!empty($usuario_id)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_id]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['sucesso_deletar_usuario'] = "Utilizador deletado com sucesso.";
            } else {
                $_SESSION['erro_deletar_usuario'] = "Utilizador não encontrado ou erro ao deletar.";
            }
            header("Location: /FLOWTRACK/Frontend/gerir-usuarios/usuarios.php");
            exit();

        } catch (PDOException $e) {
            $_SESSION['erro_deletar_usuario'] = "Erro ao deletar utilizador: " . $e->getMessage();
            header("Location: /FLOWTRACK/Frontend/gerir-usuarios/usuarios.php");
            exit();
        }
    } else {
        $_SESSION['erro_deletar_usuario'] = "ID de utilizador inválido para exclusão.";
        header("Location: /FLOWTRACK/Frontend/gerir-usuarios/usuarios.php");
        exit();
    }
} else {
    // Acesso direto ao script não permitido
    header("Location: /FLOWTRACK/Frontend/gerir-usuarios/usuarios.php");
    exit();
}
?>