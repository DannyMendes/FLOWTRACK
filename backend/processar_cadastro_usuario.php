<?php
// backend/processar_cadastro_usuario.php

require 'config/database.php'; 

session_start();

// Controle de Acesso 'Administrador'
if (!isset($_SESSION['acesso_usuario']) || $_SESSION['acesso_usuario'] !== 'Administrador') {
    $_SESSION['erro_acesso'] = "Você não tem permissão para acessar esta funcionalidade.";
    header("Location: /FLOWTRACK/Frontend/pagina-login/index.php");
    exit();
}

// Redirecionamento padrão em caso de erro
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $funcao = filter_input(INPUT_POST, 'funcao', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $tipo_acesso = filter_input(INPUT_POST, 'tipo_acesso', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $usuario = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    // Caminho p/ erro
    $redirect_erro = "/FLOWTRACK/Frontend/adicionar-usuario/adicionar-usuario.php";

    //1 campos obrigatórios
    if (empty($nome) || empty($usuario) || empty($senha) || empty($confirmar_senha) || empty($tipo_acesso)) {
        $_SESSION['erro_cadastro'] = "Por favor, preencha todos os campos obrigatórios.";
        header("Location: " . $redirect_erro);
        exit();
    }

    // 2 formato do nome 
    if (!preg_match("/^[a-zA-Z0-9_]+$/", $usuario)) {
        $_SESSION['erro_cadastro'] = "Nome de usuário inválido. Use apenas letras, números e underscores.";
        header("Location: " . $redirect_erro);
        exit();
    }
    
    // 3 tipo de acesso 
    $tipos_acesso_validos = ['Administrador', 'Comum'];
    if (!in_array($tipo_acesso, $tipos_acesso_validos)) {
        $_SESSION['erro_cadastro'] = "Tipo de acesso inválido selecionado.";
        header("Location: " . $redirect_erro);
        exit();
    }


    // 4 tamanho da senha 8
    if (strlen($senha) < 8) {
        $_SESSION['erro_cadastro'] = "A senha deve ter pelo menos 8 caracteres.";
        header("Location: " . $redirect_erro);
        exit();
    }

    // 5 igualdade das senhas
    if ($senha !== $confirmar_senha) {
        $_SESSION['erro_cadastro'] = "As senhas não coincidem.";
        header("Location: " . $redirect_erro);
        exit();
    }


    // Criptografia da senha (após validações)
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    try {
        // ve se nome de usuário já existe 
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = :usuario");
        $stmt_check->bindParam(':usuario', $usuario);
        $stmt_check->execute();

        if ($stmt_check->fetchColumn() > 0) {
            $_SESSION['erro_cadastro'] = "Nome de usuário já existe. Escolha outro.";
            header("Location: " . $redirect_erro);
            exit();
        }

        // Inserir o novo usuário no banco de dados
        $stmt_insert = $pdo->prepare("INSERT INTO usuarios (nome, funcao, tipo_acesso, usuario, senha) VALUES (:nome, :funcao, :tipo_acesso, :usuario, :senha)");
        $stmt_insert->bindParam(':nome', $nome);
        $stmt_insert->bindParam(':funcao', $funcao);
        $stmt_insert->bindParam(':tipo_acesso', $tipo_acesso); 
        $stmt_insert->bindParam(':usuario', $usuario);
        $stmt_insert->bindParam(':senha', $senha_hash);

        if ($stmt_insert->execute()) {
            $_SESSION['sucesso_cadastro'] = "Usuário cadastrado com sucesso!";
            header("Location: /FLOWTRACK/Frontend/gerir-usuarios/usuarios.php"); // Redireciona para a página de gerenciar usuários
            exit();
        } else {
            $_SESSION['erro_cadastro'] = "Erro ao cadastrar o usuário. Tente novamente.";
            header("Location: " . $redirect_erro);
            exit();
        }

    } catch (PDOException $e) {
        $_SESSION['erro_cadastro'] = "Erro no banco de dados: " . $e->getMessage();
        header("Location: " . $redirect_erro);
        exit();
    }
} else {
    // Se a req  não for POST, redirecionar para a página de cadastro
    header("Location: " . $redirect_erro);
    exit();
}
?>