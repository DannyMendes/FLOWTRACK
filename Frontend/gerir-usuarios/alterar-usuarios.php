<?php
// editar-usuario.php

require '../../backend/config/database.php';

// Verifica se o ID do usuário foi passado pela URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $usuario_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

    try {
        $stmt = $pdo->prepare("SELECT id, nome, funcao, tipo_acesso, usuario FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $usuario_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario_data) {
            $erro_mensagem = "Utilizador não encontrado.";
        }
    } catch (PDOException $e) {
        $erro_mensagem = "Erro ao buscar utilizador: " . $e->getMessage();
    }
} else {
    $erro_mensagem = "ID de utilizador inválido.";
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
        <button class="botao-navegacao ativo" onclick="window.location.href='/FLOWTRACK/Frontend/gerir-usuarios/usuarios.php'">Gerir usuário</button>
        <button class="botao-navegacao" onclick="window.location.href='/FLOWTRACK/Frontend/dashboard-relatorios/relatorio.php'">Relatório</button>
    </div>
    <div class="conteudo">
        <h2 class="titulo-pagina">Editar Utilizador</h2>

        <?php if (isset($erro_mensagem)): ?>
            <div class="mensagem-erro"><?php echo htmlspecialchars($erro_mensagem); ?></div>
            <button onclick="window.location.href='usuarios.php'" class="botao-acao cancelar">Voltar</button>
        <?php elseif (isset($usuario_data)): ?>
            <form class="formulario-adicionar-tarefa" action="../../backend/modifica-usuario.php" method="post">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario_data['id']); ?>">

                <div class="grupo-formulario">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario_data['nome']); ?>" required>
                </div>
                <div class="grupo-formulario">
                    <label for="funcao">Função:</label>
                    <input type="text" id="funcao" name="funcao" value="<?php echo htmlspecialchars($usuario_data['funcao']); ?>">
                </div>
                <div class="grupo-formulario">
                    <label for="tipo_acesso">Tipo de Acesso:</label>
                    <select id="tipo_acesso" name="tipo_acesso">
                        <option value="">Selecione</option>
                        <option value="GESTOR" <?php if ($usuario_data['tipo_acesso'] === 'GESTOR') echo 'selected'; ?>>Gestor</option>
                        <option value="COLABORADOR" <?php if ($usuario_data['tipo_acesso'] === 'COLABORADOR') echo 'selected'; ?>>Colaborador</option>
                    </select>
                </div>
                <div class="grupo-formulario">
                    <label for="usuario">Nome de Usuário:</label>
                    <input type="text" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuario_data['usuario']); ?>" required>
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
                    <button type="submit" class="botao-acao deletar" name="acao" value="deletar" onclick="return confirm('isso excluirá permanetemente a tarefa Tem CERTEZA?')"><i class="fas fa-trash-alt"></i> Deletar Utilizador
                    </button> 
                    <button type="button" class="botao-acao cancelar" onclick="window.location.href='usuarios.php'">
                        <i class="fas fa-times-circle"></i> Cancelar
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>