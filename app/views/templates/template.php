<?php

use App\Core\Config;
$config = Config::getInstance();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php component('head') ?>
    <title><?php echo $config->get('app.name') ?></title>
</head>
<body>
    <?php include $page ?>
    <?php component('script') ?>
</body>
</html>