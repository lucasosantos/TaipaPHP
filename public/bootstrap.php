<?php

require_once "../vendor/autoload.php";

try {
    router();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>