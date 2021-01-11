<?php

require_once 'includes/main.php';

define('MIN_ACCESS_LEVEL', USER_LEVEL_CUSTOMER);

$css = 'profile.min.css'; $js = 'profile.min.js?v=1';

require_once 'views/header.php';

$user = getCurrentUser();

?>

<div class="section"></div>
<div class="row container dark-5">
    <div class="row col s12 m6">
        <div class="col s12">
            <p class="flow-text center"> Configuración de la cuenta </p>

            <div class="divider"></div>
            <div class="section"></div>
        </div>
        <div class="input-field col s12">
            <input id="username" type="text" class="dark-5" value="<?= $user['username'] == null ? '' : $user['username'] ?>">
            <label for="username"> Nombre de usuario </label>
            <span class="helper-text" data-error="El nombre de usuario no es válido" data-success="El nombre de usuario es válido"></span>
        </div>
        <div class="input-field col s12">
            <input id="mailAddress" type="email" class="dark-5" value="<?= $user['mailAddress'] ?>">
            <label for="mailAddress"> Correo electrónico </label>
            <span class="helper-text" data-error="El correo electrónico no es válido" data-success="El correo electrónico es válido"></span>
        </div>
        <div class="input-field col s12 m6">
            <input id="password" type="password" class="dark-5">
            <label for="password"> Contraseña </label>
            <span class="helper-text" data-error="Las contraseñas no coinciden" data-success="Las contraseñas coinciden"></span>
        </div>
        <div class="input-field col s12 m6">
            <input id="passwordVerifier" type="password" class="dark-5">
            <label for="passwordVerifier"> Repetí tu contraseña </label>
            <span class="helper-text"></span>
        </div>

        <div class="input-field col s12">
            <select id="themeSelect">
                <?php
                    $themes = [];
                    $themes[THEMES['AUTO']]  = 'Automático';
                    $themes[THEMES['LIGHT']] = 'Claro';
                    $themes[THEMES['DARK']]  = 'Oscuro';

                    foreach ($themes as $identifier => $theme) {
                        print '<option value="' . $identifier .'" ' . (!is_null($user['theme']) && $user['theme'] == $identifier ? 'selected' : '') . '> ' . $theme . ' </option>';
                    }
                ?>
            </select>
            <label>Tema</label>
        </div>

        <button id="profileUpdateTryBtn" type="button" class="bg-light-1 bg-dark-1 waves-effect waves-light btn btn-block col s4 offset-s4 m4 offset-m4">
            Guardar
        </button>
    </div>

    <div class="row col s12 m6">
        <div class="col s12 hide-on-med-and-up">
            <div class="section"></div>
        </div>

        <div class="col s12">
            <p class="flow-text center"> Sobre vos </p>

            <div class="divider"></div>
            <div class="section"></div>
        </div>
        <div class="row col s12">
            <div class="col s12">
                <div class="row">
                    <p class="center col s12">
                        Descargá tu información personal
                    </p>
                    <ul class="disc col s11 offset-s1 m10 offset-m2">
                        <li class="thin"> Información sobre tus intereses. </li>
                        <li class="thin"> Datos analíticos que obtuvimos de vos. </li>
                        <li class="thin"> Tus publicaciones, comentarios y actividad. </li>
                    </ul>
                    <button id="requestDataDownloadBtn" type="button" class="bg-light-1 bg-dark-1 waves-effect waves-light btn btn-block col s10 offset-s1 m8 offset-m2">
                        <span> Generar informe </span>
                    </button>
                </div>
            </div>

            <div class="col s12">
                <div class="row">
                    <p class="center col s12">
                        Eliminá tu cuenta
                    </p>
                    <ul class="disc col s11 offset-s1 m10 offset-m2">
                        <li class="thin"> Tu cuenta se elimina permanentemente. </li>
                        <li class="thin"> Te desvinculamos físicamente del sistema. </li>
                        <li class="thin"> Eliminamos todas las copias al cabo de un mes. </li>
                    </ul>
                    <button id="requestAccountRemovalBtn" type="button" class="red waves-effect waves-light btn btn-block col s10 offset-s1 m8 offset-m2">
                        <span> Eliminar cuenta </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'views/footer.php'; ?>