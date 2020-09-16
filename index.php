<?php 

$title = 'Página principal'; $js = 'index.js';

require_once 'includes/main.php'; 
require_once 'views/header.php';

$recipientUsername = null;
if (isset($_GET['to'])) {
    $recipientUsername = getRecipientUsername($_GET['to']);
}

?>

<div class="dark-5 row">
    <div class="col s12 m5 offset-m1">
        <div class="section"></div>
        <p class="flow-text center">
            <?=
            $recipientUsername == null
                ? 'Compartí algo'
                : 'Dejále un mensaje a @' . $recipientUsername
            ?>
        </p>
        <div class="divider"></div>
        <div class="row">
            <form class="col s12">
                <div class="row">
                    <div class="section"></div>
                    <div class="input-field col s12">
                        <textarea id="messageInput" class="materialize-textarea dark-5" data-length="65535"></textarea>
                        <label for="messageInput"> Mensaje </label>
                        <span class="helper-text" data-error="Tenés que escribir un mensaje" data-success="Ya podés publicar">Escribí tu mensaje público</span>
                    </div>

                    <div class="input-field col s7 m6 l5">
                        <input id="declaredName" type="text" placeholder="Anónimo" class="validate dark-5">
                        <label for="declaredName"> Tu nombre </label>
                    </div>

                    <button id="createPostBtn" class="btn waves-effect waves-light col s4 m4 right mr-1 btn-block bg-dark-1 dark-5 fixed-height-btn" type="button" disabled>
                        Publicar
                        <i class="material-icons right">send</i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="col s12 m5">
        <div class="section"></div>
        <p class="flow-text center">
            Mirá lo que dicen los demás
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

<!-- <div class="fixed-action-btn">
    <button id="createRequest" class="btn-floating btn-large waves-effect waves-light bg-dark-1 pulse tooltipped" data-position="left" data-tooltip="Crear pedido">
        <i class="large material-icons">add</i>
    </button>
</div> -->

<script>
    const RECIPIENT = <?= json_encode($recipientUsername); ?>;
</script>

<?php require_once 'views/footer.php'; ?>