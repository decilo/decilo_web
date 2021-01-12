<?php 

$title = 'Página principal'; $js = 'index.min.js?v=24';

if (!isset($_GET['to'])) {
    $tabs = [
        'sortByRelevance'   => [
            'icon'  => 'trending_up',
            'text'  => 'por relevancia'
        ],
        'sortByDate'        => [
            'icon'  => 'access_time',
            'text'  => 'por fecha'
        ],
        'sortByLikes'       => [
            'icon'  => 'thumb_up',
            'text'  => 'por likes'
        ],
        'sortByComments'    => [
            'icon'  => 'comment',
            'text'  => 'por comentarios'
        ]
    ];
}

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
            <?= $recipientUsername == null ? 'Mirá lo que dicen los demás' : 'Mirá lo que le dicen los demás a @' . $recipientUsername ?>
        </p>
        <div class="divider"></div>
        <div id="recentsContainer" class="gridContainer">
            <div class="preloader-container">
                <div class="preloader-wrapper active">
                    <div class="spinner-layer spinner-blue border-dark-5 border-light-3">
                        <div class="circle-clipper left">
                        <div class="circle"></div>
                        </div><div class="gap-patch">
                        <div class="circle"></div>
                        </div><div class="circle-clipper right">
                        <div class="circle"></div>
                        </div>
                    </div>

                    <div class="spinner-layer spinner-red border-dark-1 border-light-1">
                        <div class="circle-clipper left">
                        <div class="circle"></div>
                        </div><div class="gap-patch">
                        <div class="circle"></div>
                        </div><div class="circle-clipper right">
                        <div class="circle"></div>
                        </div>
                    </div>

                    <div class="spinner-layer spinner-yellow border-dark-5 border-light-3">
                        <div class="circle-clipper left">
                        <div class="circle"></div>
                        </div><div class="gap-patch">
                        <div class="circle"></div>
                        </div><div class="circle-clipper right">
                        <div class="circle"></div>
                        </div>
                    </div>

                    <div class="spinner-layer spinner-green border-dark-1 border-light-1">
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

                    <div class="input-field col s12">
                        <input id="declaredName" type="text" placeholder="Anónimo" class="validate dark-5">
                        <label for="declaredName"> Tu nombre </label>
                    </div>

                    <div class="col s12">
                        <p id="markdownHint" class="grey-text">
                            Podés usar Markdown para agregar links personalizados, letra cursiva y otros. <a rel="noreferrer" target="_blank" href="https://github.com/showdownjs/showdown/wiki/Showdown's-Markdown-syntax"> ¿Cómo? </a>
                        </p>
                    </div>

                    <div class="col s12 hide-on-med-and-up" style="height: 1em;"></div>

                    <button id="createPostBtn" class="btn waves-effect waves-light col s5 m5 l3 right mr-1 btn-block bg-light-1 bg-dark-1 <?= isNSFW() ? 'bg-nsfw' : '' ?> dark-5 fixed-height-btn" type="button" disabled>
                        Publicar
                        <i class="material-icons deferred-icon right"> send </i>
                    </button>

                    <div id="postProgressBar"></div>

                    <label for="imageInput" class="btn waves-effect waves-light bg-light-1 bg-dark-1 <?= isNSFW() ? 'bg-nsfw' : '' ?> fixed-height-btn hand right mr-1">
                        <i class="material-icons deferred-icon special-file-btn-icon dark-5"> add_a_photo </i> 
                    </label>

                    <button id="removeFileBtn" class="btn waves-effect waves-light red fixed-height-btn hand right tooltipped" type="button" data-tooltip="Eliminar archivo" data-position="bottom">
                        <i class="material-icons deferred-icon"> delete_forever </i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- NSFW switch modal -->
<div id="nsfwSwitchModal" class="modal">
    <div class="modal-content bg-dark-4 dark-5">
        <p class="flow-text roboto thin"> ¿Querés entrar al modo NSFW? </h4>
        <p class="lato thin">
            Los mensajes que veas en este modo pueden no ser aptos para todo público, contener lenguaje vulgar y sexual, y otras ofensas. <br>
            <br>
            Si sos mayor de 18 años, tocá <span class="hand soft-link" onclick="switchToNSFW();">continuar</span>.
        </p>
    </div>
    <div class="divider"></div>
    <div class="modal-footer bg-dark-4">
        <button href="#!" class="modal-close waves-effect waves-light btn-flat dark-5"> Cancelar </button>
        <button onclick="switchToNSFW();" class="waves-effect waves-light btn-flat dark-5"> Continuar </button>
    </div>
</div>

<div class="fixed-action-btn">
    <button id="createMessageBtn" type="button" class="btn-floating btn-large bg-light-1 bg-dark-1 <?= isNSFW() ? 'bg-nsfw' : '' ?> no-select tooltipped" data-tooltip="Publicá algo" data-position="left">

        <div class="preloader-wrapper big" style="position: absolute; left: 25%; top: 25%; width: 50%; height: 50%;">
            <div class="spinner-layer border-dark-9 border-light-5">
            <div class="circle-clipper left">
                <div class="circle"></div>
            </div><div class="gap-patch">
                <div class="circle"></div>
            </div><div class="circle-clipper right">
                <div class="circle"></div>
            </div>
            </div>
        </div>

        <i class="material-icons deferred-icon"> message </i>
    </button>
</div>

<script>
    const RECIPIENT = <?= json_encode($recipientUsername)   ?>;
    const LOGGED_IN = <?= json_encode(getUserId() != null)  ?>;
    const NSFW      = <?= json_encode(isNSFW())             ?>;
    const PRIVATE   = false;
</script>

<?php require_once 'views/footer.php'; ?>