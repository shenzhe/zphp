<?php
    use ZPHP\ZPHP;
    $rootPath = dirname(__DIR__);
    require (dirname($rootPath).DIRECTORY_SEPARATOR.'ZPHP'.DIRECTORY_SEPARATOR.'ZPHP.php');
    ZPHP::run($rootPath);