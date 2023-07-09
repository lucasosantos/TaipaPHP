<?php
use App\controllers\LoginController;

//Redireciona para rotas internas da aplicação
function goToPage($page){
    header('Location: '.HOST."/".$page);
};

//Redireciona para links externos da aplicação
function goToURL($url){
    header('Location: '.$url);
};

//Retorna um componente presente na pagina views\components
//Chamada = "getComponent('comp')" se estiver dentro de pasta = "getComponent('pasta.comp')" 
function getComponent($name){
    $nameFile = str_replace('.', '\\', $name);
    $file = COMPONENTS_PATH . "\\" . $nameFile . ".php";
    if (file_exists($file)) {
        return include $file;
    }
}

//Retorna um assets presente na pagina 'public\assets'
//Chamada = "getAsset('css-style.css')" "css-" = nome da pasta + '-', "style.css" = nome do arquivo
function getAsset($name){
    $nameFile = str_replace('-', '\\', $name);
    $file = "http://" . ASSETS_PATH . "\\" . $nameFile;
    echo $file;
};

//Retorna um componente presente na pagina views\components
//Chamada = "views('view')" se estiver dentro de pasta = "views('view')" 
function views($name){
    $nameFile = str_replace('.', '\\', $name);
    $file = VIEWS_PATH . "\\" . $nameFile . ".php";
    if (file_exists($file)) {
        return include $file;
    } else {
        goToPage('error');
    }
}

function estaLogado(){
    $valido = new LoginController;
    if (!$valido->ValidarLogin()) {
        goToPage('logout');
    }
}

function testeLogado(){
    $valido = new LoginController;
    if ($valido->ValidarLogin()) {
        return true;
    } else {
        return false;
    }
}

function estaLogadoLevel($level){
    estaLogado();
    if (userLevel() != $level) {
        goToPage('painel');
    }
}

function userLevel() {
    $valido = new LoginController;
    return $valido->GetUserLevel();
}

?>