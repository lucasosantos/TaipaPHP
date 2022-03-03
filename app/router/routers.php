<?php

#Rotas do sistema
#Rota exata | '/user' => 'NameController@NameMethod',
#Rotas dinamica | '/user/VarName/[a-z][0-9]+' => 'NameController@NameMethod',

return [
    '/' => 'HomeController@Index',
    '/erro' => 'HomeController@Error',
];
?>