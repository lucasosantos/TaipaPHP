<?php

function uriExata($uri, $array) {
    if (array_key_exists($uri, $array)) {
        return [$uri => $array["$uri"]];
    }
    return [];
}

function uriDinamica($uri, $array) {
    return array_filter(
        $array,
        function ($values) use ($uri) {
            $string = str_replace('/', '\/', ltrim($values, '/'));
            return preg_match("/^$string$/", ltrim($uri, '/'));
        },
        ARRAY_FILTER_USE_KEY
    );
}

function diferencaDeParamentros($uri, $rota) {
    if (!empty($rota)) {
        $parametros = array_keys($rota)[0];
        return array_diff(
            $uri,
            explode('/', ltrim($parametros))
        );
    } else {
        return [];
    }
}

function formatarParametros($uri, $parametros) {
    $parametrosData = [];
    foreach ($parametros as $index => $item) {
        $parametrosData[$uri[$index - 1]] = $item;
    }
    return $parametrosData;
}

function router($rotas){
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $httpMethod = $_SERVER['REQUEST_METHOD'];

    if(array_key_exists($httpMethod, $rotas)) {
        $rotas = $rotas[$httpMethod];
    } else {
        http_response_code(501);
        throw new Exception("Not Implemented");
    }
    
    $rotaEncontrada = uriExata($uri, $rotas);
    $parametros = [];
    if (empty($rotaEncontrada) || $rotaEncontrada == '/') {
        $rotaEncontrada = uriDinamica($uri, $rotas);
        $uriExplode = explode('/', ltrim($uri));
        $parametros = formatarParametros($uriExplode, diferencaDeParamentros($uriExplode, $rotaEncontrada));
    }
    if (!empty($rotaEncontrada)) {
        Controller($rotaEncontrada, $parametros);
        return;
    }

    http_response_code(404);
    throw new Exception("Not Found");
}