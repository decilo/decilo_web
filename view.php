<?php

$title = 'Ver'; $js = 'view.min.js?v=5';

require_once 'includes/functions.php';
require_once 'includes/main.php';

if (!isset($_GET['message'])) {
    redirect(SYSTEM_HOSTNAME);
}

$message = null;

if (isset($_GET['private'])) {
    $message = getMessage($_GET['message'], true);
} else {
    $message = getMessage($_GET['message']);
}

if ($message == null) {
    redirect(SYSTEM_HOSTNAME);
}

$meta = [];

if (isset($message['recipient'])) {
    $recipient = getUserById($message['recipient'])['username'];

    $meta['name'] = 'Para @' . $recipient;
} else {
    $meta['name'] = 'Anónimo';
}

$meta['description'] = $message['content'];

require_once 'views/header.php';

?>

<div class="container dark-5">
    <div class="section"></div>

    <p class="flow-text center"> Estás viendo un mensaje <?= $message['declaredName'] == null ? 'anónimo' : 'de "' . $message['declaredName'] . '"' ?> </p>

    <div class="divider"></div>

    <div class="section"></div>

    <div class="row">
        <div class="col m3 hide-on-mobile"></div>
        <?=
            $message['image'] == null
                ? '' 
                : '<img class="materialboxed responsive-img col s12 m6" src="' . $message['image'] . '">'
        ?>

        <div class="col s12"></div>

        <p id="messageContent" class="lato regular word-wrap col s12 word-wrap process-whitespaces overflow-ellipsis">
            <?= $message['content'] ?>
        </p>
    </div>

    <div class="divider"></div>

    <div class="section"></div>

    <div class="row">
        <div class="input-field col s12">
            <textarea id="commentInput" class="materialize-textarea dark-5" data-length="65535"></textarea>
            <label for="commentInput"> Dejá tu comentario </label>
            <span class="helper-text" data-error="Tenés que escribir un comentario" data-success="Ya podés publicarlo">Escribí tu comentario</span>
        </div>

        <div class="input-field col s12 m9">
            <input id="commentDeclaredName" type="text" placeholder="Anónimo" class="validate dark-5">
            <label for="commentDeclaredName" class="active"> Tu nombre </label>
        </div>

        <div class="col s6 offset-s6 m3">
            <button
                id="sendCommentBtn"
                type="button"
                class="btn waves-effect waves-light col right btn-block bg-light-1 bg-dark-1 dark-5 fixed-width-btn fixed-height-btn <?= isset($_GET['nsfw']) ? 'bg-nsfw' : '' ?>"
            >
                Enviar
            </button>
        </div>
    </div>

    <ul id="commentsCollapsible" class="collapsible border-dark-8"> </ul>

    <p class="roboto thin small">
        Publicado: <?= getParsedDatetime($message['created']) . (
            isset($recipient) && $recipient != null
                ? ', se lo mandaron a @' . $recipient . '.'
                : ''
        )
        ?>
    </p>
</div>

<script>
    const MESSAGE   = <?= json_encode($_GET['message'])               ?>;
    const PRIVATE   = <?= json_encode(isset($message['recipient']))   ?>;
    const NSFW      = <?= json_encode(isset($_GET['nsfw']))           ?>;
</script>

<?php require_once 'views/footer.php'; ?>