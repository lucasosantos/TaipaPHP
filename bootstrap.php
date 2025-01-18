<?php

require_once "vendor/autoload.php";
$dotenv = '';

$dotenv_test = strpos($_SERVER['REQUEST_URI'], 'localhost') !== true;

if ($dotenv_test) {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__, '.env.dev');
} else {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
}

$dotenv->load();

try {
    
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $urlPartes = explode('/', ltrim($uri));

    if($urlPartes[1] === "api") {
        api_router();
    } else {
        web_router();
    }

} catch (Exception $e) {
    echo $e->getMessage();
}

?>