<!DOCTYPE html>
<html lang="pt-br">
<head>
    <?php getComponent('head') ?>
    <title><?php if ($title != '' || $title != null) { echo $title . ' - '; } ?><?php echo APP_NOME ?></title>
</head>
<body>
    <?php include $page ?>
    <?php getComponent('script') ?>
</body>
</html>