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

//Envia mensagens que só serão exibidas 1 vez para o usuario, quando recarregada a pagina irá apagar
function sendMsn($msn) {
    $_SESSION['msn'][] = $msn;
}

//Recupera variaveis de ambiente
function getVar($name) {
    return $_SERVER[$name];
}

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
 
//Chama uma pagina
function view(string $view, string $template = 'template', string $title = null, array $vars = null){
    if ($vars != null) {extract($vars);}
    $nameFile = str_replace('.', '/', $view);
    $templateFile = VIEWS_PATH . "/templates/" . $template . ".php";
    $page = VIEWS_PATH . "/pages/" . $view . ".php";
    if (file_exists($page)) {
        return include $templateFile;
    } else {
        goToPage('error');
    }
}

//Regra de acesso: Usuário logado
function pageRuleIsAuthenticated(){
    $valido = new LoginController;
    if (!$valido->ValidarLogin()) {
        goToPage('logout');
    }
}

//Teste lógico para saber se o usuário esta logado, retorna true ou false
function userIsAuthenticated(){
    $valido = new LoginController;
    if ($valido->ValidarLogin()) {
        return true;
    } else {
        return false;
    }
}

//Regra de acesso: Usuário com nível de acesso específico
function pageRuleAuthenticatedUserLevel($level){
    pageRuleIsAuthenticated();
    if (userLevel() != $level) {
        goToPage('painel');
    }
}

//Retorna a string do nivel do usuário
function userLevel() {
    $valido = new LoginController;
    return $valido->GetUserLevel();
}

?>