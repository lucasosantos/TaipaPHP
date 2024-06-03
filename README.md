# Taipa PHP  
*Um Framework PHP simples e pequeno*
  
### Por que "Taipa"?
  
![Casa de Taipa](https://upload.wikimedia.org/wikipedia/commons/thumb/8/88/Serra-Talhada-Casa-de-taipa.jpg/1200px-Serra-Talhada-Casa-de-taipa.jpg)
  
Taipa é um método construtivo vernacular que consiste no uso de barro e madeira para criar moradias. A Taipa de mão é um método construtivo antigo que consiste no entrelaçamento de madeiras para formar vãos. Essas aberturas são posteriormente preenchidas com barro.
  
### Get started!
  
```composer create-project lucasosantos/taipaphp ola_mundo```

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
  
No arquivo ".env"

    //Configurações do banco de dados
    SGBD=mysql
    DB_HOST=localhost
    DB_NAME=taipa
    DB_USER=root
    DB_PASS=12345
    DB_PORT=3306
  
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

```getOneById()```
Retorna um resultado por id
**getOneById(id)**

```delete()```
Deleta linhas que correspondam a condição
**delete(coluna, condição)**

```update()```
Atualiza linhas que correspondam a condição
**update(coluna_de_comparação, condição, colunas_alteração, novos_valores)**
update(<coluna_comparaçao>,<condicao_comparaçao>,array(coluna_1,... coluna_n),array(valor_1,... valor_n))
  
### Métodos Auxiliares
  
```request_post()```
Retorna os dados enviados via post
request_post()

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
getAsset("<nome_pasta> - <nome_arquivo> . <extenção>")

### Métodos de segurança

```pageRuleIsAuthenticated()```
Regra de segurança colocada no controller da pagina, antes do methodo 'views('nome')'

```pageRuleAuthenticatedUserLevel(<level>)```
Regra de segurança com level de usuário
Colocada no controller da pagina, antes do methodo 'views('nome')'
pageRuleAuthenticatedUserLevel('ADM')

```userLevel()```
Retorna o level do usuario

```testIsAutenticated()```
retorna um booleano se o usuário esta ou não logado no sistema

### Tabela para login

Nome: 'user'

Campos:
- id (Int) - Primária
- username (varchar(25))
- password (varchar(200))
- level (varchar(20))

### Variaveis de segurança
No arquivo "taipa-config"

//Sua chave de segurança para gerar o token JWT
KEY="key"

//O algoritimo que sua aplicação irá utilizar
ALGORITHM='HS256'