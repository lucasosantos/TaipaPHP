<?php

function routers()
{
    return require "routers.php";
}

function uriExata($uri) {
    if (array_key_exists($uri, routers())) {
        return [];
    } else {
        return [];
    }
}

function uriDinamica($uri) {
    return array_filter(
        routers(),
        function ($values) use ($uri) {
            $string = str_replace('/', '\/', ltrim($values, '/'));
            return preg_match("/^$string$/", ltrim($uri, '/'));
        },
        ARRAY_FILTER_USE_KEY
    );
}

function diferencaDeParamentros($uri, $rotaEncontrada) {
    if (!empty($rotaEncontrada)) {
        $parametros = array_keys($rotaEncontrada)[0];
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

function router()
{

    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    $rotaEncontrada = uriExata($uri);

    if (empty($rotaEncontrada)) {
        $rotaEncontrada = uriDinamica($uri);
        $uriExplode = explode('/', ltrim($uri));
        $parametros = formatarParametros($uriExplode, diferencaDeParamentros($uriExplode, $rotaEncontrada));
    }

    if (!empty($rotaEncontrada)) {
        Controller($rotaEncontrada);
        return;
    }

    throw new Exception("Algo deu errado!");

}