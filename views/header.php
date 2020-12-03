<?php

$userId             = getUserId();
$userName           = getUserName();
$userMailAddress    = getUserMailAddress();

$isCrawling = false;
foreach (GOOGLE_CRAWLER_UAS as $userAgent) {
    if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), strtolower($userAgent)) !== false) {
        $isCrawling = true;
        
        break;
    }
}

if (defined('MIN_ACCESS_LEVEL') && (getAllowance() == null || getAllowance() < MIN_ACCESS_LEVEL)) {
    redirect(SYSTEM_HOSTNAME);
}

?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <!-- Let browser know website is optimized for mobile -->
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <!-- SEO meta -->
        <meta name="application-name" content="<?= SYSTEM_TITLE ?>">
        <meta name="description" content="<?= isset($meta) ? $meta['description'] : 'Dejá tu mensaje, contá un secreto o compartí lo que pensás.' ?>">
        <meta name="keywords" content="secret, secreto, secretos, contar, contá, compartir, compartí, multimedia, contenido, fotos, imágenes, imagenes, comparti, conta, hablar, charlar, charlá, charla, di, deci, decí">

        <meta name="apple-mobile-web-app-title" content="<?= SYSTEM_TITLE ?>">
        <meta name="msapplication-TileColor" content="#2196f3">
        <meta name="msapplication-config" content="assets/misc/browserconfig.xml">
        <meta name="theme-color" content="#1d7fcd">

        <script>
            <?php
                print getUIConstants(UI_SETTINGS);
                print getUIConstants(SHARED_VALUES);
            ?>
        </script>

        <title> <?= (isset($meta) ? $meta['name'] . ' - ' : (isset($title) ? $title . ' - ' : '')) . SYSTEM_TITLE ?> </title>

        <base href="<?= SYSTEM_HOSTNAME ?>">

        <style>
            <?php readfile('assets/css/critical.min.css'); ?>
        </style>

        <?php
            foreach (CRITICAL_ORIGINS as $criticalOrigin) {
                print '<link rel="preconnect" href="' . $criticalOrigin . '">';
            }

            $backupCSS = [];
            if (isset($css)) {
                if (is_array($css)) {
                    foreach ($css as $preBackupCSS) {
                        $backupCSS[] = strpos($css, 'http') !== false ? $css : 'assets/css/' . $css;
                    }
                } else {
                    $backupCSS = [ strpos($css, 'http') !== false ? $css : 'assets/css/' . $css ];
                }
            }

            $css = [
                // Import MaterializeCSS
                'https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css',

                // Custom styles.
                'assets/css/style.min.css'
            ];

            $css = array_merge($css, $backupCSS);
        ?>

        <!--

        TODO: Buy a logo and use it.

        <link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="194x194" href="assets/icons/favicon-194x194.png">
        <link rel="icon" type="image/png" sizes="192x192" href="assets/icons/android-chrome-192x192.png">
        <link rel="icon" type="image/png" sizes="16x16" href="assets/icons/favicon-16x16.png">
        <link rel="manifest" href="assets/misc/site.webmanifest">
        <link rel="mask-icon" href="assets/icons/safari-pinned-tab.svg" color="#2196f3">
        <link rel="shortcut icon" href="assets/icons/favicon.ico">
        -->

        <?php

        if (isset($css)) {
            if (is_array($css)) {
                foreach ($css as $href) {
                    print '<link rel="preload" as="style" href="' . $href . '">';
                }
            } else {
                print '<link rel="preload" as="style" href="assets/css/' . $css . '">';
            }
        }

        ?>

        <noscript>
            <style>
                #notReadyWrapper { display: flex !important; }
            </style>
        </noscript>

        <style>
            <?php require_once 'includes/theme.php'; ?>
        </style>
    </head>

    <body class="bg-light-5 bg-dark-4" style="opacity: 0;">
        <?php

        if ($isCrawling) {
            $messages =
                $GLOBALS['database']->prepare(
                    'SELECT
                        content, (
                            SELECT  `url`
                            FROM    `d_images`
                            WHERE   `d_images`.`message` =  `d_messages_private`.`id`
                        ) AS `image`,
                        declaredName,
                        CONCAT(\'view/private/\', `d_messages_private`.`id`) AS url
                     FROM `d_messages_private`

                     UNION

                     SELECT
                        content, (
                            SELECT  `url`
                            FROM    `d_images`
                            WHERE   `d_images`.`message` =  `d_messages_public`.`id`
                        ) AS `image`,
                        declaredName,
                        CONCAT(\'view/\', `d_messages_public`.`id`) AS url
                     FROM `d_messages_public`'
                );

            $messages->execute();

            foreach ($messages->fetchAll() as $message) {
                print 
                '<div class="message">
                    <img src="' . $message['image'] . '" style="max-width: 100%; max-height: 100%;">
                    <h1 id="declaredName"> ' . ($message['declaredName'] == null ? 'Anonymous' : $message['declaredName']) . ' </h1>
                    <p id="content"> ' . substr($message['content'], 0, MESSAGES['MAX_LENGTH']) . '… </p>
                    <a href="' . SYSTEM_HOSTNAME . $message['url'] . '"> Ver mensaje </a>
                 </div>';
            }

            print '</body>
            </html>';

            exit();
        }

        ?>

        <header>
            <nav class="nav-extended bg-light-1 bg-dark-1">
                <div class="nav-wrapper">
                    <a class="brand-logo thin hand no-select"><?= SYSTEM_TITLE ?></a>
                    <a href="#" data-target="mobile-nav" class="sidenav-trigger">
                        <i class="material-icons">menu</i>
                    </a>
                    <ul id="nav-mobile" class="right hide-on-med-and-down">
                        <li>
                            <a href="<?= SYSTEM_HOSTNAME . 'privacy' ?>" class="lato center custom-link"> Privacidad </a>
                        </li>
                        <?= $userId == null ? '
                        <li>
                            <a id="loginBtn" class="lato center"> Iniciá sesión </a>

                            <div class="tap-target bg-dark-1" data-target="loginBtn">
                                <div class="tap-target-content">
                                    <h5> Recibí mensajes privados </h5>
                                    <p class="lato"> Creá tu cuenta y empezá a recibir mensajes anónimos de tus seguidores. </p>
                                </div>
                            </div>
                        </li>' : '
                        <li>
                            <a href="' . SYSTEM_HOSTNAME . 'private" class="lato center custom-link"> Mis mensajes </a>
                        </li>
                        <li>
                            <a href="' . SYSTEM_HOSTNAME . 'profile" class="lato center custom-link"> Mi cuenta </a>
                        </li>
                        <li>
                            <a id="logoutBtn" class="lato"> Salir </a>
                        </li>'
                        ?>
                    </ul>
                </div>
            </nav>

            <ul class="sidenav bg-dark-3" id="mobile-nav">
                <li>
                    <div class="user-view">
                        <div class="background" style="background: url('<?= getRandomWallpaper() ?>');" style="filter: blur(1px);"></div>
                        <a href="profile" class="custom-link">
                            <span class="white-text name">
                                <?=
                                    $userId == null
                                        ? 'No iniciaste sesión'
                                        : (
                                            $userName == null
                                                ? '<span class="medium"> Creá tu nombre de usuario </span>'
                                                : $userName
                                        )
                                ?>
                            </span>
                        </a>
                        <a href="#email">
                            <span class="white-text email">
                                <?= $userId == null ? '' : $userMailAddress ?>
                            </span>
                        </a>
                    </div>
                </li>

                <li>
                    <a href="<?= SYSTEM_HOSTNAME . 'privacy' ?>" class="dark-5 hand custom-link"> Privacidad </a>
                </li>
                <?= $userId == null ? '
                <li>
                    <a id="loginBtnMobile" class="dark-5 hand"> Iniciá sesión </a>
                </li>' : '
                <li>
                    <a href="' . SYSTEM_HOSTNAME . 'private" class="dark-5 custom-link"> Mis mensajes </a>
                </li>
                <li>
                    <a href="' . SYSTEM_HOSTNAME . 'profile" class="dark-5 custom-link"> Mi cuenta </a>
                </li>
                <li>
                    <a id="logoutBtnMobile" class="dark-5 hand"> Salir </a>
                </li>'
                ?>
            </ul>
        </header>
        <main>