<?php
session_start();
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = filter_var($_POST['usuario'], FILTER_SANITIZE_STRING);
    $senha = $_POST['senha'];

    try {
        $stmt = $pdo->prepare("SELECT id, nome, senha FROM usuarios WHERE nome = ?");
        $stmt->execute([$usuario]);
        $dados_usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dados_usuario && password_verify($senha, $dados_usuario['senha'])) {
            // Login bem-sucedido
            $_SESSION['id_usuario'] = $dados_usuario['id'];
            $_SESSION['nome_usuario'] = $dados_usuario['nome'];
            header("Location: /FLOWTRACK/Frontend/dashboard-gestao/dashboard.php"); // Redireciona para o dashboard
            exit();
        } else {
            // Falha no login
            $_SESSION['erro_login'] = "Usuário ou senha incorretos.";
            header("Location: /FLOWTRACK/Frontend/pagina-login/index.php"); // Redireciona de volta para a página de login
            exit();
        }

    } catch (PDOException $e) {
        $_SESSION['erro_login'] = "Erro ao processar o login: " . $e->getMessage();
        header("Location: /FLOWTRACK/Frontend/pagina-login/index.php");
        exit();
    }

} else {
    // Se tentar acessar processar_login.php diretamente por GET
    header("Location: /FLOWTRACK/Frontend/pagina-login/index.php");
    exit();
}
?>