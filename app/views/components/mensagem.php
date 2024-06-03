<?php
if (isset($_SESSION['msn'])) {
?>
<div>
    <?php 
    foreach ($_SESSION['msn'] as $key => $alert) {
        $classe = '';
        switch ($alert['tipo']) {
            case '3':
                $classe = 'danger';
                break;
            case '2':
                $classe = 'secondary';
                break;
            case '1':
                $classe = 'success';
                break;
            default:
                $classe = 'secondary';
                break;
        }
    ?>
    <div class="alert alert-<?php echo $classe ?>">
        <p><?php echo $alert['msn'] ?></p>
    </div>
    <?php 
    unset($_SESSION['msn'][$key]);
    }
    ?>
</div>
<?php
}
?>