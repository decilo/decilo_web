<?php

use MatthiasMullie\Minify;

require_once 'vendor/autoload.php';

require_once 'includes/settings.php';

define('SETTINGS_PATH', 'includes/settings.php');
define('ASSETS_PATH', 'assets/');
define('CSS_PATH', ASSETS_PATH . 'css/');
define('JS_PATH', ASSETS_PATH . 'js/');
define('BUNDLE_PATH', JS_PATH . 'bundle.min.js');

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

if (USE_BUNDLE) {
    print '-> Rendering bundle...' . PHP_EOL;
    if (file_exists(BUNDLE_PATH)) {
        unlink(BUNDLE_PATH);
    }

    $bundleContent = '';

    foreach (CORE_SCRIPTS as $name => $src) {
        $curlOptions = [
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_SSL_VERIFYHOST  => false,
            CURLOPT_SSL_VERIFYPEER  => false
        ];

        if (strpos($src, 'http') === false) {
            $src = SHARED_VALUES['SYSTEM_HOSTNAME'] . $src;

            $curlOptions[CURLOPT_SSL_VERIFYHOST] = false;
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
        }

        $curlOptions[CURLOPT_URL] = $src;

        print ' > Downloading and appending from: ' . $src . PHP_EOL;

        $request = curl_init();

        curl_setopt_array($request, $curlOptions);

        $bundleContent .= curl_exec($request);
    }

    file_put_contents(BUNDLE_PATH, $bundleContent, FILE_APPEND);

    print '-> Updating bundle version...';

    $settings = explode(PHP_EOL, file_get_contents(SETTINGS_PATH));

    $lineCount = count($settings);

    unlink(SETTINGS_PATH);

    foreach ($settings as $index => $line) {
        file_put_contents(
            SETTINGS_PATH,
            (
                strpos($line, 'BUNDLE_VERSION') !== false
                    ? explode(',', $line)[0] . ', \'' . hash(BUNDLE_HASH_ALGO, $bundleContent) . '\');'
                    : $line
            ) . ($index < $lineCount - 1 ? PHP_EOL : ''),
            FILE_APPEND
        );
    }
}

?>