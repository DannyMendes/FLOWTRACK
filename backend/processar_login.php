<?php
session_start();
require 'config/database.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pega o nome de usuário 
    $usuario_digitado = filter_var($_POST['usuario'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $senha_digitada = $_POST['senha'];

    try {
        // consulta SQL
        $stmt = $pdo->prepare("SELECT id, nome, usuario, senha, funcao, tipo_acesso FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario_digitado]);
        $dados_usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica se o usuário foi encontrado e se a senha está correta
        if ($dados_usuario && password_verify($senha_digitada, $dados_usuario['senha'])) {
            // Login bem-sucedido
            //armazena os dados do usuário
            $_SESSION['id_usuario'] = $dados_usuario['id'];
            $_SESSION['nome_usuario'] = $dados_usuario['nome']; 
            $_SESSION['usuario_login'] = $dados_usuario['usuario'];
            $_SESSION['funcao_usuario'] = $dados_usuario['funcao']; 
            $_SESSION['acesso_usuario'] = $dados_usuario['tipo_acesso']; 

            // Redireciona de acordo a função
            if ($_SESSION['acesso_usuario'] === 'Administrador') {
                header("Location: /FLOWTRACK/Frontend/dashboard-gestao/dashboard.php");
            } elseif ($_SESSION['acesso_usuario'] === 'Comum') {
                header("Location: /FLOWTRACK/Frontend/lista-tarefa/detalhe.php");
            } else {
                // Função desconhecida - volta para o login
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
        // Erro de BD
        $_SESSION['erro_login'] = "Erro ao processar o login: " . $e->getMessage();
        header("Location: /FLOWTRACK/Frontend/pagina-login/index.php");
        exit();
    }

} else {
    // Se a req não for POST, redireciona para login
    header("Location: /FLOWTRACK/Frontend/pagina-login/index.php");
    exit();
}
?>