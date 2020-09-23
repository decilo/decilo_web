<?php

$userId             = getUserId();
$userName           = getUserName();
$userMailAddress    = getUserMailAddress();

if (defined('MIN_ACCESS_LEVEL') && (getAllowance() == null || getAllowance() < MIN_ACCESS_LEVEL)) {
    redirect('index.php');
}

?>

<!DOCTYPE html>
<html>
    <head>
        <!-- Let browser know website is optimized for mobile -->
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <!-- SEO meta -->
        <meta name="application-name" content="<?= SYSTEM_TITLE ?>">
        <meta name="description" content="Dejá tu mensaje, contá un secreto o compartí lo que pensás.">
        <meta name="keywrods" content="secret, secreto, secretos, contar, contá, compartir, compartí, multimedia, contenido, fotos, imágenes, imagenes, comparti, conta, hablar, charlar, charlá, charla, di, deci, decí">

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

        <title> <?= (isset($title) ? $title . ' - ' : '') . SYSTEM_TITLE ?> </title>

        <?php
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
                'assets/css/style.css'
            ];

            $css = array_merge($css, $backupCSS);

            $fonts = [
                // Import Roboto font
                'https://fonts.googleapis.com/css2?family=Lato:wght@150;300;500&family=Roboto:wght@150;300;500&display=swap',

                // Import Google Icon Font
                'https://fonts.googleapis.com/icon?family=Material+Icons&display=swap'
            ];

            foreach ($fonts as $font) {
                print '<link rel="preload" as="style" href="' . $font . '">';
            }
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
                    print '<link rel="stylesheet" href="' . $href . '">';
                }
            } else {
                print '<link rel="stylesheet" href="assets/css/' . $css . '">';
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

    <body class="bg-dark-4" <?= isset($_GET['fromLogin']) ? 'style="display: none;"' : '' ?>>
        <header>
            <nav class="nav-extended bg-dark-1">
                <div class="nav-wrapper">
                    <a href="index.php" class="brand-logo regular custom-link"><?= SYSTEM_TITLE ?></a>
                    <a href="#" data-target="mobile-nav" class="sidenav-trigger">
                        <i class="material-icons">menu</i>
                    </a>
                    <ul id="nav-mobile" class="right hide-on-med-and-down">
                        <li>
                            <a href="privacy.php" class="lato center"> Privacidad </a>
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
                            <a href="private.php" class="lato center custom-link"> Mis mensajes </a>
                        </li>
                        <li>
                            <a href="profile.php" class="lato center custom-link"> Mi cuenta </a>
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
                        <div class="background">
                            <img src="services/getRandomImage.php" style="filter: blur(1px);">
                        </div>
                        <a href="profile.php" class="custom-link">
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
                    <a href="privacy.php" class="dark-5 hand custom-link"> Privacidad </a>
                </li>
                <?= $userId == null ? '
                <li>
                    <a id="loginBtnMobile" class="dark-5 hand"> Iniciá sesión </a>
                </li>' : '
                <li>
                    <a href="private.php" class="dark-5 custom-link"> Mis mensajes </a>
                </li>
                <li>
                    <a href="profile.php" class="dark-5 custom-link"> Mi cuenta </a>
                </li>
                <li>
                    <a id="logoutBtnMobile" class="dark-5 hand"> Salir </a>
                </li>'
                ?>
            </ul>
        </header>
        <main style="visibility: hidden;">