<?php

function Controller($uri, $parametros) {

    [$controller, $method] = explode('@', array_values($uri)[0]);
    
    $controllerCompleto = "App\\controllers\\".$controller;

    if (!class_exists($controllerCompleto)) {
        throw new Exception("O Controlador não existe!");
    }

    $controllerInstance = new $controllerCompleto;

    if (!method_exists($controllerInstance, $method)) {
        echo $method;
        throw new Exception("O Methodo não existe!");
    }

    $controllerInstance->$method($parametros);

}

?>