<?php
// editar-usuario.php

session_start(); // Inicia a sessão para acessar variáveis de sessão
require '../../backend/config/database.php'; // Caminho correto para o seu arquivo de conexão

// --- Controle de Acesso (Importante!) ---
// Apenas usuários logados e com tipo de acesso 'Administrador' devem poder acessar esta página.
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['acesso_usuario']) || $_SESSION['acesso_usuario'] !== 'Administrador') {
    $_SESSION['erro_acesso'] = "Você não tem permissão para acessar esta página.";
    header("Location: /FLOWTRACK/Frontend/pagina-login/index.php"); // Redireciona para a página de login
    exit();
}

$usuario_id = null; // Inicializa a variável
$usuario_data = null; // Inicializa a variável
$erro_mensagem = null; // Para mensagens de erro ao carregar a página

// Verifica se o ID do usuário foi passado pela URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $usuario_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

    try {
        $stmt = $pdo->prepare("SELECT id, nome, funcao, tipo_acesso, usuario FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $usuario_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario_data) {
            $erro_mensagem = "Utilizador não encontrado.";
            // Se o usuário não for encontrado, redireciona para a lista de usuários
            $_SESSION['erro_gerir_usuarios'] = "Utilizador com ID " . htmlspecialchars($usuario_id) . " não encontrado.";
            header("Location: /FLOWTRACK/Frontend/gerir-usuarios/usuarios.php");
            exit();
        }
    } catch (PDOException $e) {
        $erro_mensagem = "Erro ao buscar utilizador: " . $e->getMessage();
        // Em caso de erro no banco de dados, redireciona para a lista de usuários
        $_SESSION['erro_gerir_usuarios'] = "Erro ao buscar dados do utilizador: " . $e->getMessage();
        header("Location: /FLOWTRACK/Frontend/gerir-usuarios/usuarios.php");
        exit();
    }
} else {
    $erro_mensagem = "ID de utilizador inválido ou não fornecido.";
    // Se o ID for inválido, redireciona para a lista de usuários
    $_SESSION['erro_gerir_usuarios'] = "ID de utilizador inválido ou não fornecido para edição.";
    header("Location: /FLOWTRACK/Frontend/gerir-usuarios/usuarios.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard / Gestão - Editar Utilizador</title>
    <link rel="stylesheet" href="cadastrar-usuarios.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="cabecalho">
        <i class="fas fa-bars icone-menu"></i>
        <span class="titulo">Dashboard / Gestão</span>
    </div>
    <div class="navegacao">
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/dashboard-gestao/dashboard.php'">Tarefas</button>
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/adicionar-tarefas/adicionar-tarefa.php'">Inserir tarefa</button>
        <button class="botao-navegacao active" onclick="window.location.href='/FLOWTRACK/Frontend/adicionar-usuario/adicionar-usuario.php'">Cadastrar usuário</button>
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/gerir-usuarios/usuarios.php'">Gerir usuário</button>
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/dashboard-relatorios/relatorio.php'">Relatório</button>
    </div>
    <div class="conteudo">
        <h2 class="titulo-pagina">Editar Utilizador: <?php echo htmlspecialchars($usuario_data['nome'] ?? ''); ?></h2>

        <?php
        // Exibe mensagens de sucesso ou erro que vieram do processar_usuario.php
        if (isset($_SESSION['erro_atualizar_usuario'])) {
            echo '<div class="mensagem-erro">' . htmlspecialchars($_SESSION['erro_atualizar_usuario']) . '</div>';
            unset($_SESSION['erro_atualizar_usuario']);
        }
        if (isset($_SESSION['sucesso_atualizar_usuario'])) {
            echo '<div class="mensagem-sucesso">' . htmlspecialchars($_SESSION['sucesso_atualizar_usuario']) . '</div>';
            unset($_SESSION['sucesso_atualizar_usuario']);
        }
        // Mensagem de erro de acesso, caso o usuário não tenha permissão
        if (isset($_SESSION['erro_acesso'])) {
            echo '<div class="mensagem-erro">' . htmlspecialchars($_SESSION['erro_acesso']) . '</div>';
            unset($_SESSION['erro_acesso']);
        }
        ?>

        <?php if ($usuario_data): // Apenas exibe o formulário se os dados do usuário foram encontrados ?>
            <form class="formulario-adicionar-tarefa" action="../../backend/modifica-usuario.php" method="post">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario_data['id']); ?>">

                <div class="grupo-formulario">
                    <label for="nome">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario_data['nome']); ?>" required>
                </div>
                <div class="grupo-formulario">
                    <label for="funcao">Função:</label>
                    <input type="text" id="funcao" name="funcao" value="<?php echo htmlspecialchars($usuario_data['funcao']); ?>">
                </div>
                <div class="grupo-formulario">
                    <label for="tipo_acesso">Tipo de Acesso:</label>
                    <select id="tipo_acesso" name="tipo_acesso" required>
                        <option value="">Selecione</option>
                        <option value="Administrador" <?php if ($usuario_data['tipo_acesso'] === 'Administrador') echo 'selected'; ?>>Administrador</option>
                        <option value="Comum" <?php if ($usuario_data['tipo_acesso'] === 'Comum') echo 'selected'; ?>>Comum</option>
                    </select>
                </div>
                <div class="grupo-formulario">
                    <label for="usuario_login">Nome de Usuário (login):</label>
                    <input type="text" id="usuario_login" name="usuario_login" value="<?php echo htmlspecialchars($usuario_data['usuario']); ?>" required>
                </div>
                <div class="grupo-formulario">
                    <label for="nova_senha">Nova Senha:</label>
                    <input type="password" id="nova_senha" name="nova_senha">
                    <small>Deixe em branco para manter a senha atual.</small>
                </div>
                <div class="grupo-formulario">
                    <label for="confirmar_senha">Confirmar Nova Senha:</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha">
                </div>

                <div class="acoes">
                    <button type="submit" class="botao-acao salvar">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                    <button type="submit" class="botao-acao deletar" name="acao" value="deletar" onclick="return confirm('ATENÇÃO: Isso excluirá permanentemente o utilizador. Tem CERTEZA?')">
                        <i class="fas fa-trash-alt"></i> Deletar Utilizador
                    </button>
                    <button type="button" class="botao-acao cancelar" onclick="window.location.href='usuarios.php'">
                        <i class="fas fa-times-circle"></i> Cancelar
                    </button>
                </div>
            </form>
        <?php else: // Exibe a mensagem de erro se usuario_data não foi carregado ?>
            <div class="mensagem-erro"><?php echo htmlspecialchars($erro_mensagem); ?></div>
            <button onclick="window.location.href='usuarios.php'" class="botao-acao cancelar">Voltar para Gerir Usuários</button>
        <?php endif; ?>
    </div>
</body>
</html>