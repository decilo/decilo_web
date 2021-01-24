<?php

use MatthiasMullie\Minify;

require_once 'vendor/autoload.php';

require_once 'includes/main.php';

define('SETTINGS_PATH', 'includes/settings.php');
define('ASSETS_PATH', 'assets/');
define('VIEWS_PATH', 'views/');

define('CSS_PATH', ASSETS_PATH . 'css/');
define('JS_PATH', ASSETS_PATH . 'js/');
define('CSS_BUNDLE_PATH', CSS_PATH . 'bundle.min.css');
define('JS_BUNDLE_PATH', JS_PATH . 'bundle.min.js');
define('REPORT_REASONS_CACHE_PATH', VIEWS_PATH . 'reportReasons.html');

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
    print '-> Rendering CSS bundle...' . PHP_EOL;
    if (file_exists(CSS_BUNDLE_PATH)) {
        unlink(CSS_BUNDLE_PATH);
    }

    $bundleContent = '';

    foreach (CORE_STYLESHEETS as $name => $src) {
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

    file_put_contents(CSS_BUNDLE_PATH, $bundleContent, FILE_APPEND);

    $fullBundle = $bundleContent;

    print '-> Rendering JS bundle...' . PHP_EOL;
    if (file_exists(JS_BUNDLE_PATH)) {
        unlink(JS_BUNDLE_PATH);
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

        print ' > Downloading and appending from: ' . $src;

        $request = curl_init();

        curl_setopt_array($request, $curlOptions);

        $bundleContent .= curl_exec($request) . PHP_EOL;

        print ' | HTTP: ' . curl_getinfo($request, CURLINFO_HTTP_CODE) . PHP_EOL;
    }

    file_put_contents(JS_BUNDLE_PATH, $bundleContent, FILE_APPEND);

    $fullBundle .= $bundleContent;

    print '-> Updating bundle version...' . PHP_EOL;

    $settings = explode(PHP_EOL, file_get_contents(SETTINGS_PATH));

    $lineCount = count($settings);

    unlink(SETTINGS_PATH);

    foreach ($settings as $index => $line) {
        file_put_contents(
            SETTINGS_PATH,
            (
                strpos($line, 'BUNDLE_VERSION') !== false
                    ? explode(',', $line)[0] . ', \'' . hash(BUNDLE_HASH_ALGO, $fullBundle) . '\');'
                    : $line
            ) . ($index < $lineCount - 1 ? PHP_EOL : ''),
            FILE_APPEND
        );
    }

    print '-> Building report reasons static cache...' . PHP_EOL;

    $reportReasonsCache = '';
    foreach (getReportReasons() as $reportReason) {
        $reportReasonsCache .= trim(
            preg_replace(
                '/\s+/'
                ,
                ' ', '
                <p>
                    <label>
                        <input name="reportReason" type="radio" value="' . $reportReason['id'] . '" />
                        <span class="' . ($reportReason['score'] < 0 ? 'red-text medium' : 'regular') . '"> ' . $reportReason['reason'] . ' </span>
                    </label>
                </p>'
            )
        );
    }

    file_put_contents(REPORT_REASONS_CACHE_PATH, $reportReasonsCache);
}

?>