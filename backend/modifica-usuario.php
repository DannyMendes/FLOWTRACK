<?php
require 'config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] === 'deletar') {
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
        // Processamento da atualização do usuário
        $usuario_id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $nome = filter_var($_POST['nome'], FILTER_SANITIZE_STRING);
        $funcao = filter_var($_POST['funcao'], FILTER_SANITIZE_STRING);
        $tipo_acesso = filter_var($_POST['tipo_acesso'], FILTER_SANITIZE_STRING);
        $usuario = filter_var($_POST['usuario'], FILTER_SANITIZE_STRING);
        $nova_senha = $_POST['nova_senha'];
        $confirmar_senha = $_POST['confirmar_senha'];

        if (empty($nome) || empty($usuario)) {
            $_SESSION['erro_atualizar_usuario'] = "Nome e Nome de Usuário são obrigatórios.";
            header("Location: /FLOWTRACK/Frontend/gerir-usuarios/editar-usuario.php?id=" . $usuario_id);
            exit();
        }

        try {
            $sql = "UPDATE usuarios SET nome = ?, funcao = ?, tipo_acesso = ?, usuario = ?";
            $params = [$nome, $funcao, $tipo_acesso, $usuario];

            if (!empty($nova_senha)) {
                if ($nova_senha !== $confirmar_senha) {
                    $_SESSION['erro_atualizar_usuario'] = "A nova senha e a confirmação não coincidem.";
                    header("Location: /FLOWTRACK/Frontend/gerir-usuarios/editar-usuario.php?id=" . $usuario_id);
                    exit();
                }
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $sql .= ", senha = ?";
                $params[] = $senha_hash;
            }
            $sql .= " WHERE id = ?";
            $params[] = $usuario_id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $_SESSION['sucesso_atualizar_usuario'] = "Utilizador atualizado com sucesso.";
            header("Location: /FLOWTRACK/Frontend/gerir-usuarios/usuarios.php");
            exit();

        } catch (PDOException $e) {
            $_SESSION['erro_atualizar_usuario'] = "Erro ao atualizar utilizador: " . $e->getMessage();
            header("Location: /FLOWTRACK/Frontend/gerir-usuarios/editar-usuario.php?id=" . $usuario_id);
            exit();
        }
    }
} else {
    // Acesso direto não permitido
    header("Location: /FLOWTRACK/Frontend/gerir-usuarios/usuarios.php");
    exit();
}
?>