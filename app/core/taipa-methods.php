<?php
use App\controllers\LoginController;

define('HOST', $_SERVER['HTTP_HOST']);
define('ROOT', $_SERVER['DOCUMENT_ROOT']);

//Redireciona para rotas internas da aplicação
function goToPage($page){
    header('Location: '. HTTP . '://' .HOST."/".$page);
};

//Redireciona para links externos da aplicação
function goToURL($url){
    header('Location: '.$url);
};

//Envia mensagens que só serão exibidas 1 vez para o usuario, quando recarregada a pagina irá apagar
//Tipo = 'msn' - 1, 'alert' - 2, 'error' - 3
function sendMsn($msn, $tipo) {
    $_SESSION['msn'][] = ['msn' => $msn, 'tipo' => $tipo];
}

//Retorna um componente presente na pagina views\components
//Chamada = "getComponent('comp')" se estiver dentro de pasta = "getComponent('pasta.comp')" 
function getComponent($name){
    $nameFile = str_replace('.', '\\', $name);
    $file = ROOT . "/app/views/components/" . $nameFile . ".php";
    if (file_exists($file)) {
        return include $file;
    }
}

//Retorna um assets presente na pagina 'public\assets'
//Chamada = "getAsset('css-style.css')" "css-" = nome da pasta + '-', "style.css" = nome do arquivo
function getAsset($name){
    $nameFile = str_replace('-', '\\', $name);
    $file = HTTP . "://" . HOST . "/assets/" . $nameFile;
    echo $file;
};
 
//Chama uma pagina
function view(string $view, string $template = 'template', string $title = null, array $vars = null){
    if ($vars != null) {extract($vars);}
    $nameFile = str_replace('.', '/', $view);
    $templateFile = ROOT . "/app/views/templates/" . $template . ".php";
    $page = ROOT . "/app/views/pages/" . $view . ".php";
    if (file_exists($page)) {
        return include $templateFile;
    } else {
        goToPage('erro');
    }
}

function request_post_api(){
    $inputJSON = file_get_contents('php://input');
    return json_decode($inputJSON, true);
}

function request_post_form(){
    return filter_input_array(INPUT_POST, FILTER_DEFAULT);
}

?>