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
    <div class="col s12">
        <p class="flow-text center">
            Mirá lo que dicen los demás
        </p>
        <div class="divider"></div>
        <div id="recentsContainer">

            <div class="row">
                <!-- Messages container -->
            </div>

        </div>
    </div>
</div>

<!-- Report message modal -->
<div id="reportMessageModal" class="modal bg-dark-4 dark-5">
    <div class="modal-content">
        <p class="flow-text">
            Reportar una publicación
        </p>

        <?php
        foreach (getReportReasons() as $reportReason) {
            print '
            <p>
                <label>
                    <input name="reportReason" type="radio" value="' . $reportReason['id'] . '" />
                    <span class="' . ($reportReason['score'] < 0 ? 'red-text medium' : 'regular') . '"> ' . $reportReason['reason'] . ' </span>
                </label>
            </p>';
        }
        ?>
    </div>
    <div class="modal-footer bg-dark-4 dark-5">
        <button id="sendReportBtn" href="#!" class="waves-effect waves-light btn-flat dark-5"> Enviar </a>
    </div>
</div>

<!-- Create message modal -->
<div id="createMessageModal" class="modal bg-dark-4 dark-5">
    <div class="modal-content">
        <p class="flow-text">
            <?=
            $recipientUsername == null
                ? 'Compartí algo'
                : 'Dejále un mensaje a @' . $recipientUsername
            ?>
        </p>

        <div class="row">
            <form class="col s12">
                <input id="imageInput" type="file">
                <div class="row">
                    <div class="input-field col s12">
                        <textarea id="messageInput" class="materialize-textarea dark-5" data-length="65535"></textarea>
                        <label for="messageInput"> Mensaje </label>
                        <span class="helper-text" data-error="Tenés que escribir un mensaje" data-success="Ya podés publicar">Escribí tu mensaje público</span>
                    </div>

                    <div class="input-field col s12 l5">
                        <input id="declaredName" type="text" placeholder="Anónimo" class="validate dark-5">
                        <label for="declaredName"> Tu nombre </label>
                    </div>

                    <button id="createPostBtn" class="btn waves-effect waves-light col s5 m5 l3 right mr-1 btn-block bg-dark-1 dark-5 fixed-height-btn" type="button" disabled>
                        Publicar
                        <i class="material-icons right"> send </i>
                    </button>

                    <div id="postProgressBar"></div>

                    <label for="imageInput" class="btn waves-effect waves-light bg-dark-1 fixed-height-btn hand right mr-1" disabled>
                        <i class="material-icons special-file-btn-icon dark-5"> add_a_photo </i> 
                    </label>

                    <button id="removeFileBtn" class="btn waves-effect waves-light red fixed-height-btn hand right tooltipped" type="button" data-tooltip="Eliminar archivo" data-position="bottom">
                        <i class="material-icons"> delete_forever </i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="fixed-action-btn">
    <button id="fabToggleBtn" type="button" class="btn-floating btn-large bg-dark-1 no-select tooltipped" data-tooltip="Publicá algo" data-position="left">
        <i class="large material-icons"> add </i>
    </button>
    <ul>
        <li>
            <button id="createMessageBtn" type="button" class="btn-floating bg-dark-2">
                <i class="material-icons small"> message </i>
            </button>
        </li>
    </ul>
</div>

<script>
    const RECIPIENT = <?= json_encode($recipientUsername)        ?>;
    const LOGGED_IN = <?= json_encode(getUserId() != null)       ?>;

    const RECENTS   = <?= json_encode(getRecentMessages($recipientUsername)) ?>;
</script>

<?php require_once 'views/footer.php'; ?>