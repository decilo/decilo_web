<?php

use MatthiasMullie\Minify;

require_once 'vendor/autoload.php';

define('ASSETS_PATH', 'assets/');
define('CSS_PATH', ASSETS_PATH . 'css/');
define('JS_PATH', ASSETS_PATH . 'js/');

print '-> Minifying CSS files...' . PHP_EOL;
foreach (scandir(CSS_PATH) as $css) {
    if ($css != '.' && $css != '..') {
        $minifier = new Minify\CSS(CSS_PATH . $css);

        $minifier->minify(CSS_PATH . str_replace('.css', '', $css) . '.min.css');
    }
}

print '-> Minifying JS files...' . PHP_EOL;
foreach (scandir(JS_PATH) as $js) {
    if ($js != '.' && $js != '..') {
        $minifier = new Minify\JS(JS_PATH . $js);

        $minifier->minify(JS_PATH . str_replace('.js', '', $js) . '.min.js');
    }
}

?>