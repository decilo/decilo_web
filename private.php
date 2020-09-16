<?php

$title = 'Mis mensajes'; $js = 'private.js';

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
                <p class="col s12 thin center" style="margin-top: 1.5em;">
                    Falta un paso para que puedas empezar a recibir mensajes. <br>
                    <br>                    
                    Visitá tu perfil y <a href="profile.php" class="custom-link">configurá tu nombre de usuario</a>.
                </p>';
            } else {
                print '
                <p class="col s12 thin center" style="margin-top: 1.5em;">
                    Empezá a recibir mensajes, compartí una captura de tu código QR.
                </p>
                <div class="col s12 m8 offset-m2 l6 offset-l3">
                    ' . getUserQR() . '
                </div>
                <p class="col s12 thin center">
                    También podés <span id="shareBtnHint" style="display: none;"> tocar el botón de abajo o </span> copiar y pegar tu link.
                </p>
                <input id="shareableLink" type="text" class="dark-5 center col s10 offset-s1 m10 offset-m1 offset-l3 l6" value="' . getUserLink() . '" readonly>
                <div class="col s12 center">
                    <button
                        id="shareProfileBtn"
                        class="btn waves-effect waves-light btn-block bg-dark-1 dark-5 fixed-height-btn"
                        type="button"
                        style="display: none;"
                    >
                        Compartir
                        <i class="material-icons right">share</i>
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
        <div id="recentsContainer">
            <!-- Waiting for the server to reply with at least one chunk. -->

            <div id="preloader">
                <div class="section"></div>
                
                <div class="section center">
                    <div class="preloader-wrapper active">
                        <div class="spinner-layer border-dark-5">
                            <div class="circle-clipper left">
                                <div class="circle"></div>
                            </div>
                            <div class="gap-patch">
                                <div class="circle"></div>
                            </div>
                            <div class="circle-clipper right">
                                <div class="circle"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Messages container -->
            </div>

        </div>
    </div>
</div>

<?php require_once 'views/footer.php'; ?>
