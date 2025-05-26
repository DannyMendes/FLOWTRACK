<?php
// backend/processar_cadastro_usuario.php

require 'config/database.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $funcao = filter_input(INPUT_POST, 'funcao', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $tipo_acesso = filter_input(INPUT_POST, 'tipo_acesso', FILTER_SANITIZE_FULL_SPECIAL_CHARS); 
    $usuario = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    // Validação dos campos obrigatórios
    if (empty($nome) || empty($usuario) || empty($senha) || empty($confirmar_senha)) {
        $_SESSION['erro_cadastro'] = "Por favor, preencha todos os campos obrigatórios.";
        header("Location: /FLOWTRACK/Frontend/adicionar-usuario/adicionar-usuario.php");
        exit();
    }

    // Validação do formato do nome de usuário 
    if (!preg_match("/^[a-zA-Z0-9_]+$/", $usuario)) {
        $_SESSION['erro_cadastro'] = "Nome de usuário inválido. Use apenas letras, números e underscores.";
        header("Location: /FLOWTRACK/Frontend/adicionar-usuario/adicionar-usuario.php");
        exit();
    }

    // Validação da igualdade das senhas
    if ($senha !== $confirmar_senha) {
        $_SESSION['erro_cadastro'] = "As senhas não coincidem.";
        header("Location: /FLOWTRACK/Frontend/adicionar-usuario/adicionar-usuario.php");
        exit();
    }

    // Criptografia da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    try {
        // Verificar se o nome de usuário já existe
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = :usuario");
        $stmt_check->bindParam(':usuario', $usuario);
        $stmt_check->execute();

        if ($stmt_check->fetchColumn() > 0) {
            $_SESSION['erro_cadastro'] = "Nome de usuário já existe. Escolha outro.";
            header("Location: /FLOWTRACK/Frontend/adicionar-usuario/adicionar-usuario.php");
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
            header("Location: /FLOWTRACK/Frontend/gerir-usuarios/usuarios.php");
            exit();
        } else {
            $_SESSION['erro_cadastro'] = "Erro ao cadastrar o usuário. Tente novamente.";
            header("Location: /FLOWTRACK/Frontend/adicionar-usuario/adicionar-usuario.php");
            exit();
        }

    } catch (PDOException $e) {
        $_SESSION['erro_cadastro'] = "Erro no banco de dados: " . $e->getMessage();
        header("Location: /FLOWTRACK/Frontend/adicionar-usuario/adicionar-usuario.php");
        exit();
    }
} else {
    // Se a requisição não for POST, redirecionar para a página de cadastro
    header("Location: /FLOWTRACK/Frontend/adicionar-usuario/adicionar-usuario.php");
    exit();
}
?>