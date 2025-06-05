<?php
// backend/processar_cadastro_usuario.php

require 'config/database.php'; // Certifique-se de que este caminho está correto

session_start();

// Verifica se o usuário logado tem permissão para cadastrar outros usuários
// Apenas administradores devem ter acesso a esta funcionalidade.
// Ajuste 'tipo_acesso' para o nome da sua variável de sessão que guarda o tipo de acesso
// e 'Administrador' para o valor exato na sua coluna de banco de dados.
if (!isset($_SESSION['acesso_usuario']) || $_SESSION['acesso_usuario'] !== 'Administrador') {
    // Se não for um administrador ou não estiver logado, redireciona
    $_SESSION['erro_acesso'] = "Você não tem permissão para acessar esta funcionalidade.";
    header("Location: /FLOWTRACK/Frontend/pagina-login/index.php"); // Redirecione para a página de login ou uma página de erro
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $funcao = filter_input(INPUT_POST, 'funcao', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    // Validação extra para o tipo_acesso vindo do select
    $tipo_acesso = filter_input(INPUT_POST, 'tipo_acesso', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $usuario = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    // Caminho para redirecionamento padrão em caso de erro
    $redirect_erro = "/FLOWTRACK/Frontend/adicionar-usuario/adicionar-usuario.php";

    // --- Início das Validações ---

    // 1. Validação dos campos obrigatórios
    if (empty($nome) || empty($usuario) || empty($senha) || empty($confirmar_senha) || empty($tipo_acesso)) {
        $_SESSION['erro_cadastro'] = "Por favor, preencha todos os campos obrigatórios.";
        header("Location: " . $redirect_erro);
        exit();
    }

    // 2. Validação do formato do nome de usuário (apenas letras, números e underscores)
    if (!preg_match("/^[a-zA-Z0-9_]+$/", $usuario)) {
        $_SESSION['erro_cadastro'] = "Nome de usuário inválido. Use apenas letras, números e underscores.";
        header("Location: " . $redirect_erro);
        exit();
    }
    
    // 3. Validação do tipo de acesso (se é um dos valores esperados do ENUM)
    // Se sua coluna 'tipo_acesso' é ENUM('Administrador','Comum')
    $tipos_acesso_validos = ['Administrador', 'Comum'];
    if (!in_array($tipo_acesso, $tipos_acesso_validos)) {
        $_SESSION['erro_cadastro'] = "Tipo de acesso inválido selecionado.";
        header("Location: " . $redirect_erro);
        exit();
    }


    // 4. Validação do comprimento da senha (ex: mínimo 8 caracteres)
    if (strlen($senha) < 8) {
        $_SESSION['erro_cadastro'] = "A senha deve ter pelo menos 8 caracteres.";
        header("Location: " . $redirect_erro);
        exit();
    }

    // 5. Validação da igualdade das senhas
    if ($senha !== $confirmar_senha) {
        $_SESSION['erro_cadastro'] = "As senhas não coincidem.";
        header("Location: " . $redirect_erro);
        exit();
    }

    // --- Fim das Validações ---

    // Criptografia da senha (APENAS após todas as validações de senha)
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    try {
        // Verificar se o nome de usuário já existe no banco de dados
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
        $stmt_insert->bindParam(':tipo_acesso', $tipo_acesso); // Já está sanitizado
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
    // Se a requisição não for POST, redirecionar para a página de cadastro
    header("Location: " . $redirect_erro);
    exit();
}
?>