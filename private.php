<?php

$title = 'Mis mensajes'; $js = 'private.min.js';

require_once 'includes/main.php'; 

define('MIN_ACCESS_LEVEL', USER_LEVEL_CUSTOMER);

require_once 'views/header.php';

?>

<div class="dark-5 row">
    <div class="col s12 m5 offset-m1">
        <div class="section"></div>
        <p class="flow-text center">
            Recibí mensajes
        </p>
        <div class="divider"></div>
        <div class="row">
            <?php
            if (getCurrentUser()['username'] == null) {
                print '
                <p class="col s12 regular center" style="margin-top: 1.5em;">
                    Falta un paso para que puedas empezar a recibir mensajes. <br>
                    <br>                    
                    Visitá tu perfil y <a href="profile" class="custom-link">configurá tu nombre de usuario</a>.
                </p>';
            } else {
                $userQR = getUserQR();

                print '
                <p class="col s12 regular center" style="margin-top: 1.5em;">
                    Empezá a recibir mensajes, compartí una captura de tu código QR.
                </p>
                <div class="col s12 m8 offset-m2 l6 offset-l3 xl8 offset-xl2">
                    <div class="qr-wrapper center d-flex flex-center">
                        <div class="hide load-error">
                            <i class="material-icons large"> refresh </i>
                            <p> Algo anda mal, por favor probá recargando la página. </p>
                        </div>
                        <div class="preloader-wrapper small active center-block">
                            <div class="spinner-layer border-dark-5 border-light-9">
                                <div class="circle-clipper left">
                                <div class="circle"></div>
                                </div><div class="gap-patch">
                                <div class="circle"></div>
                                </div><div class="circle-clipper right">
                                <div class="circle"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <img
                        data-src="' . $userQR . '"
                        style="display: none; width: 0px; height: 0px;"
                        onload="
                            $(this)
                                .prev()
                                .css({ background: \'url(\' + $(this).attr(\'src\') + \')\' })
                                .find(\'.preloader-wrapper\')
                                .remove();
                        "
                        onerror="
                            $(this)
                                .prev()
                                .css({ background: \'url(\' + $(this).attr(\'src\') + \')\' })
                                .find(\'.preloader-wrapper\')
                                .remove();

                            $(this)
                                .prev()
                                .find(\'.load-error\')
                                .removeClass(\'hide\');
                        "
                    >
                </div>
                <p class="col s12 regular center">
                    También podés <span id="shareBtnHint" style="display: none;"> tocar el botón de abajo o </span> copiar y pegar tu link.
                </p>
                <input id="shareableLink" type="text" class="dark-5 center col s10 offset-s1 m10 offset-m1 offset-l3 l6 thin" value="' . getUserLink() . '" readonly>
                <div class="col s12 center">
                    <button
                        id="shareProfileBtn"
                        class="btn waves-effect waves-light btn-block bg-light-1 bg-dark-1 dark-5 fixed-height-btn"
                        type="button"
                        style="display: none;"
                    >
                        Compartir
                        <i class="material-icons deferred-icon right">share</i>
                    </button>
                </div>';
            }
            ?>
        </div>
    </div>
    <div class="col s12 m5">
        <div class="section"></div>
        <p class="flow-text center">
            Qué te dijeron
        </p>
        <div class="divider"></div>
        <div id="recentsContainer" class="gridContainer">

            <div class="row">
                <!-- Messages container -->
            </div>

        </div>
    </div>
</div>

<!-- Message remove request modal -->
<div id="requestRemovalModal" class="modal">
    <div class="modal-content bg-dark-4 dark-5">
        <p class="flow-text roboto thin"> ¿Querés eliminar este mensaje? </h4>
        <p class="lato thin"> El mensaje que seleccionaste se va a eliminar permanentemente, por lo que este proceso es irreversible. </p>
    </div>
    <div class="divider"></div>
    <div class="modal-footer bg-dark-4">
        <button href="#!" class="modal-close waves-effect waves-light btn-flat dark-5"> Cancelar </button>
        <button onclick="tryToRemove();" class="waves-effect waves-light btn-flat dark-5"> Eliminar </button>
    </div>
</div>

<script>
    const RECENTS   = <?= json_encode(getRecentMessages(getUserName())) ?>;
    const RECIPIENT = <?= json_encode(getUserName())                    ?>;
    const PRIVATE   = true;
</script>

<?php require_once 'views/footer.php'; ?>