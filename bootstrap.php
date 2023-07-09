<?php

require_once "vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
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