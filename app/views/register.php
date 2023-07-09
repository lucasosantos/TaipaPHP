<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php getComponent('head') ?>
    <title>Register - <?php echo APP_NOME ?></title>
</head>
<body class="metod">
    <h1>Register</h1>
    <form action="/register" method="post">
        <input type="text" name="username" id="username">
        <input type="password" name="password" id="password">
        <input type="submit" value="Register">
    </form>
</body>
</html>