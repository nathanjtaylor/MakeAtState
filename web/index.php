<?php
ob_start();
require dirname(__DIR__). '/src/main.php';
main();
ob_end_flush();


?>
