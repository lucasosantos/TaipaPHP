<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php getComponent('head') ?>
    <title>Login - <?php echo APP_NOME ?></title>
</head>
<body class="metod">
    <h1>Login</h1>
    <form action="/login" method="post">
        <input type="text" name="username" id="username">
        <input type="password" name="password" id="password">
        <input type="submit" value="Enviar">
    </form>
</body>
</html>