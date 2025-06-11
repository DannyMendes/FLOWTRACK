<?php
require 'config/database.php'; 
session_start();

//Controle Acesso 'Administrador'
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['acesso_usuario']) || $_SESSION['acesso_usuario'] !== 'Administrador') {
    $_SESSION['erro_acesso'] = "Você não tem permissão para realizar esta operação.";
    header("Location: /FLOWTRACK/Frontend/pagina-login/index.php"); // vai para a pg de login
    exit();
}

//redirecionamento p erros
$redirect_erro_geral = "/FLOWTRACK/Frontend/gerir-usuarios/usuarios.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DELETAR Usuário 
    if (isset($_POST['acao']) && $_POST['acao'] === 'deletar') {
        $usuario_id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);//sanitiza o ID do usuário evitando injeção SQL

        // Verifica se o ID do usuário é válido
        if (empty($usuario_id)) {
            $_SESSION['erro_deletar_usuario'] = "ID de utilizador inválido para exclusão.";
            header("Location: " . $redirect_erro_geral);
            exit();
        }

        // evita auto-deleção
        if ($usuario_id == $_SESSION['id_usuario']) {
            $_SESSION['erro_deletar_usuario'] = "Você não pode deletar sua própria conta de administrador.";
            header("Location: " . $redirect_erro_geral);
            exit();
        }

        try {
            // Ve se existe antes de tentar deletar
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE id = ?");
            $stmt_check->execute([$usuario_id]);
            if ($stmt_check->fetchColumn() === 0) {
                $_SESSION['erro_deletar_usuario'] = "Utilizador não encontrado.";
                header("Location: " . $redirect_erro_geral);
                exit();
            }

            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_id]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['sucesso_deletar_usuario'] = "Utilizador deletado com sucesso.";
            } else {
                // falha
                $_SESSION['erro_deletar_usuario'] = "Erro ao deletar utilizador. Nenhuma linha afetada.";
            }
            header("Location: " . $redirect_erro_geral);
            exit();

        } catch (PDOException $e) {
            $_SESSION['erro_deletar_usuario'] = "Erro ao deletar utilizador: " . $e->getMessage();
            header("Location: " . $redirect_erro_geral);
            exit();
        }
    }
    //atualiza
    else {
        $usuario_id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $nome = filter_var($_POST['nome'], FILTER_SANITIZE_FULL_SPECIAL_CHARS); 
        $funcao = filter_var($_POST['funcao'], FILTER_SANITIZE_FULL_SPECIAL_CHARS); 
        $tipo_acesso = filter_var($_POST['tipo_acesso'], FILTER_SANITIZE_FULL_SPECIAL_CHARS); 
        $usuario = filter_var($_POST['usuario_login'], FILTER_SANITIZE_FULL_SPECIAL_CHARS); 
        $nova_senha = $_POST['nova_senha'];
        $confirmar_senha = $_POST['confirmar_senha'];

        $redirect_erro_edicao = "/FLOWTRACK/Frontend/gerir-usuarios/editar-usuario.php?id=" . $usuario_id;

        // verificacao
        if (empty($usuario_id)) {
            $_SESSION['erro_atualizar_usuario'] = "ID do utilizador para atualização é inválido.";
            header("Location: " . $redirect_erro_geral); 
            exit();
        }

        if (empty($nome) || empty($usuario)) {
            $_SESSION['erro_atualizar_usuario'] = "Nome completo e Nome de Usuário são campos obrigatórios.";
            header("Location: " . $redirect_erro_edicao);
            exit();
        }
        
        // Valida formato nome
        if (!preg_match("/^[a-zA-Z0-9_]+$/", $usuario)) {
            $_SESSION['erro_atualizar_usuario'] = "Nome de usuário inválido. Use apenas letras, números e underscores.";
            header("Location: " . $redirect_erro_edicao);
            exit();
        }

        // Valida tipo de acesso adm 
        $tipos_acesso_validos = ['Administrador', 'Comum']; 
        if (!in_array($tipo_acesso, $tipos_acesso_validos)) {
            $_SESSION['erro_atualizar_usuario'] = "Tipo de acesso selecionado é inválido.";
            header("Location: " . $redirect_erro_edicao);
            exit();
        }

        try {
            //nomejá existe ?
            $stmt_check_user = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = :usuario AND id != :id");
            $stmt_check_user->bindParam(':usuario', $usuario);
            $stmt_check_user->bindParam(':id', $usuario_id);
            $stmt_check_user->execute();

            if ($stmt_check_user->fetchColumn() > 0) {
                $_SESSION['erro_atualizar_usuario'] = "Nome de usuário já está em uso por outro utilizador.";
                header("Location: " . $redirect_erro_edicao);
                exit();
            }

            $sql = "UPDATE usuarios SET nome = ?, funcao = ?, tipo_acesso = ?, usuario = ?";
            $params = [$nome, $funcao, $tipo_acesso, $usuario];

            //Senha
            if (!empty($nova_senha)) {
                if (strlen($nova_senha) < 8) { 
                    $_SESSION['erro_atualizar_usuario'] = "A nova senha deve ter pelo menos 8 caracteres.";
                    header("Location: " . $redirect_erro_edicao);
                    exit();
                }
                if ($nova_senha !== $confirmar_senha) {
                    $_SESSION['erro_atualizar_usuario'] = "A nova senha e a confirmação não coincidem.";
                    header("Location: " . $redirect_erro_edicao);
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

            // Se usuário logado editado, atualiza sessão
            if ($usuario_id == $_SESSION['id_usuario']) {
                $_SESSION['nome_usuario'] = $nome;
                $_SESSION['funcao_usuario'] = $funcao;
                $_SESSION['acesso_usuario'] = $tipo_acesso;
                $_SESSION['usuario_login'] = $usuario;
            }

            $_SESSION['sucesso_atualizar_usuario'] = "Utilizador atualizado com sucesso.";
            header("Location: " . $redirect_erro_geral); // Redireciona para a lista geral de usuários
            exit();

        } catch (PDOException $e) {
            $_SESSION['erro_atualizar_usuario'] = "Erro ao atualizar utilizador: " . $e->getMessage();
            header("Location: " . $redirect_erro_edicao);
            exit();
        }
    }
} else {
    // Acesso direto não permitido
    header("Location: " . $redirect_erro_geral);
    exit();
}
?>