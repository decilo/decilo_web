<?php

$title = 'Ver';

require_once 'includes/functions.php';
require_once 'includes/main.php';

if (!isset($_GET['message'])) {
    redirect('index.php');
}

$message = null;

if (isset($_GET['private'])) {
    $message = getMessage($_GET['message'], true);
} else {
    $message = getMessage($_GET['message']);
}

if ($message == null) {
    redirect('index.php');
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

        <p class="lato thin word-wrap col s12 word-wrap process-whitespaces overflow-ellipsis">
            <?= $message['content'] ?>
        </p>
    </div>

    <div class="divider"></div>

    <p class="roboto thin small">
        Publicado: <?= getParsedDatetime($message['created']) . (
            isset($recipient) && $recipient != null
                ? ', se lo mandaron a @' . $recipient . '.'
                : ''
        )
        ?>
    </p>
</div>

<?php require_once 'views/footer.php'; ?>