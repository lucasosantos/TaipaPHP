<h1>Olá Mundo</h1>
<?php
    if (userIsAuthenticated()) {
        echo 'Usuário logado: ' . $_SESSION['username'];
    }
    getComponent('mensagem');
?>
<a href="/login">Login</a>
<a href="/register">Register</a>
<a href="/logout">Logout</a>