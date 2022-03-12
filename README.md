# Taipa PHP  
*Um Framework PHP simples e pequeno*
  
### Por que "Taipa"?
  
![Casa de Taipa](https://upload.wikimedia.org/wikipedia/commons/thumb/8/88/Serra-Talhada-Casa-de-taipa.jpg/1200px-Serra-Talhada-Casa-de-taipa.jpg)
  
Taipa é um método construtivo vernacular que consiste no uso do barro e da madeira para criar moradias. Taipa de mão é um método construtivo antigo que consiste no entrelaçamento de madeiras que formam vãos. Essas aberturas, posteriormente, são preenchidas com barro.
  
### Get started!
  
```composer create-project lucasosantos/taipaphp ola_mundo```
  
### Estrutura das pastas

app
-- controllers
-- core
-- --- Controller.php
-- --- Model.php
-- db
-- --- Database.php
-- helpers
-- --- SqlHelper.php
-- models
-- views
-- --- components
-- --- error.php
-- --- index.php
public
-- assets
-- --- css
-- --- imgs  
-- -- js
-- bootstrap.php
-- index.php
composer.json 
composer.lock 
Readme.md

### Rotas
  
No arquivo "app -> router -> routers.php"

    return [
        '/' => 'HomeController@Index',
        '/erro' => 'HomeController@Error',
    ];

Definindo uma nova rota:

    '/erro' => 'HomeController@Error'
    '/<caminho>' => '<Nome do Controller>@<Metodo>'
  
### Controllers

    namespace App\controllers;
    class <Nome_Controller> {
    public function <Nome_Metodo>(<apributo_1>,... <atributo_n>) {
        }
    }

Chamar views:

    views();
    views("<nome_da_views>");
    views("<pasta>.<nome_da_views>");
    
Exemplo chama a view "index.php":

    views("index");

### Variaveis do Banco de Dados
  
No arquivo "app -> env_variables.php"

    //Configurações do banco de dados
    define('SGBD','<sgbd_name>');
    define('DB_HOST','<Host>');
    define('DB_NAME','<Nome_Banco>');
    define('DB_USER','<user_name>');
    define('DB_PASS','<senha>');
    define('DB_PORT','<porta>');
  
### Models

    namespace App\models;
    use App\core\Model;
    class nome extends Model {
        public $table = "<nome_da_tabela_no_banco>";
    }  

### Operações com Models

```insert()```
Inserir dados
**insert(colunas, valores)**
insert(array(<nome_coluna_1,... nome_coluna_n>),array(<<valor_coluna_1,... valor_coluna_n>>))

```listAll()```
Listar todos os elementos do model

```listWhere()```
Listar todos os elementos do model com uma condição
**listWhere(coluna, condição)**

```getOne()```
Retorna um resultado que corresponda a condição
**getOne(coluna, condição)**

```delete()```
Deleta linhas que correspondam a condição
**delete(coluna, condição)**

```update()```
Atualiza linhas que correspondam a condição
**update(coluna_de_comparação, condição, colunas_alteração, novos_valores)**
update(<coluna_comparaçao>,<condicao_comparaçao>,array(coluna_1,... coluna_n),array(valor_1,... valor_n))
  
### Métodos Auxiliares
  
```goToPage(<caminho_da_rota>)```
Redireciona para pagina interna do sistema
goToPage('/home')

```goToURL(<link_externo>)```
Redireciona para link externo
goToURL('www.google. com. br')
    
```getComponent(<>)```
Recupera componente presente na pasta "componets"
getComponent('navbar')
    
```getAsset(<>)```
Recupera arquivo de assets presente na pasta "assets"
getAsset('css-style.css')
getAsset('nome_pasta - nome_arquivo . extenção')
