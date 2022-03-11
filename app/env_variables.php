<?php

//Configurações do banco de dados
define('SGBD','mysql');
define('DB_HOST','localhost');
define('DB_NAME','adeca_db');
define('DB_USER','root');
define('DB_PASS','');
define('DB_PORT','');

define('HOST', $_SERVER['HTTP_HOST']);
define('ROOT', dirname(__FILE__, 2));
define("ASSETS_PATH", HOST . "\\assets");
define("VIEWS_PATH", ROOT . "\\app\\views");
define("COMPONENTS_PATH", VIEWS_PATH . "/components");

?>