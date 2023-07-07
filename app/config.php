<?php

//Nome da Aplicação
define('APP_NOME', 'Taipa PHP');

//Localização de arquivos
define('HOST', $_SERVER['HTTP_HOST']);
define('ROOT', dirname(__FILE__, 2));
define("ASSETS_PATH", HOST . "\\assets");
define("VIEWS_PATH", ROOT . "\\app\\views");
define("COMPONENTS_PATH", VIEWS_PATH . "/components");

?>