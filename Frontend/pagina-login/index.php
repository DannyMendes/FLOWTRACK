<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="barra-navegacao">
        <span class="icone-menu">&#9776;</span>
        <span class="titulo">Login</span>
    </div>

    <div class="container-login">
        <span>&#129489;&#8205;&#128188;</span>
        <h2>LOGIN</h2>
        <?php
            session_start();
            if (isset($_SESSION['erro_login'])) {
                echo '<p class="mensagem-erro">' . $_SESSION['erro_login'] . '</p>';
                unset($_SESSION['erro_login']); // Limpa a mensagem de erro
            } 
        ?>
        <form action="/FLOWTRACK/backend/processar_login.php" method="post">
            <label for="usuario">Usuário</label>
            <input type="text" id="usuario" name="usuario" required>

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>

            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>