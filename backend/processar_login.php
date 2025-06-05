<?php
session_start();
require 'config/database.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pega o nome de usuário (que agora será o valor da coluna 'usuario')
    $usuario_digitado = filter_var($_POST['usuario'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $senha_digitada = $_POST['senha'];

    try {
        // Altera a consulta SQL para buscar pelo campo 'usuario' em vez de 'nome'
        // A coluna 'nome' é o nome completo da pessoa, 'usuario' é o login.
        $stmt = $pdo->prepare("SELECT id, nome, usuario, senha, funcao, tipo_acesso FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario_digitado]);
        $dados_usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica se o usuário foi encontrado e se a senha está correta
        if ($dados_usuario && password_verify($senha_digitada, $dados_usuario['senha'])) {
            // Login bem-sucedido
            $_SESSION['id_usuario'] = $dados_usuario['id']; // Armazena o ID do usuário
            $_SESSION['nome_usuario'] = $dados_usuario['nome']; // Armazena o nome completo
            $_SESSION['usuario_login'] = $dados_usuario['usuario']; // Armazena o nome de login
            $_SESSION['funcao_usuario'] = $dados_usuario['funcao']; // Armazena a função do usuário
            $_SESSION['acesso_usuario'] = $dados_usuario['tipo_acesso']; // Armazena a tipo de acesso do usuário

            // Redirecionamento baseado na função
            if ($_SESSION['acesso_usuario'] === 'administrador') {
                header("Location: /FLOWTRACK/Frontend/dashboard-gestao/dashboard.php");
            } elseif ($_SESSION['acesso_usuario'] === 'comum') {
                header("Location: /FLOWTRACK/Frontend/lista-tarefa/detalhe.php");
            } else {
                // Função desconhecida - volta para a página de login
                $_SESSION['erro_login'] = "acesso de usuário desconhecido.";
                header("Location: /FLOWTRACK/Frontend/pagina-login/index.php");
            }
            exit();
        } else {
            // Falha no login: usuário não encontrado ou senha incorreta
            $_SESSION['erro_login'] = "Usuário ou senha incorretos.";
            header("Location: /FLOWTRACK/Frontend/pagina-login/index.php");
            exit();
        }

    } catch (PDOException $e) {
        // Erro de banco de dados
        $_SESSION['erro_login'] = "Erro ao processar o login: " . $e->getMessage();
        header("Location: /FLOWTRACK/Frontend/pagina-login/index.php");
        exit();
    }

} else {
    // Se a requisição não for POST, redireciona para a página de login
    header("Location: /FLOWTRACK/Frontend/pagina-login/index.php");
    exit();
}
?>