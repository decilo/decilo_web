<?php
chdir('..');

require_once 'includes/settings.php';
$width = 1600; $height = 900;
$availableColors = SHARED_VALUES['THEME'];
$limit = 4;
require_once 'vendor/neolightning/PHPMaterialImageGenerator/MaterialImageGen.php';
?>