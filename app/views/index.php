<!DOCTYPE html>
<html lang="en">
<head>
    <?php getComponent('head') ?>
    <title><?php echo APP_NOME ?></title>
</head>
<body class="metod">
    <h1>Olá Mundo</h1>
    <?php
        if (testeLogado()) {
            echo 'Usuário logado: ' . $_SESSION['username'];
        }
    ?>
    <a href="/login">Login</a>
    <a href="/register">Register</a>
    <a href="/logout">Logout</a>
</body>
</html>