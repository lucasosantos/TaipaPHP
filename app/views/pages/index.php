<h1>Olá Mundo</h1>
<?php
    if (testIsAutenticated()) {
        echo 'Usuário logado: ' . $_SESSION['username'];
    }
?>
<a href="/login">Login</a>
<a href="/register">Register</a>
<a href="/logout">Logout</a>