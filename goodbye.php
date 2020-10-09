<?php

$title = 'Privacidad';

require_once 'includes/main.php';
require_once 'views/header.php';

?>

<div class="container dark-5">
    <p class="flow-text center"> Desactivaste tu cuenta </p>

    <div class="divider"></div>

    <p class="lato thin">
        Lamentamos que te vayas, pero esperamos que vuelvas pronto. Si te arrepentís, siempre podés volver a crearte una cuenta. <br>
        <br>
        Al cabo de unos segundos, todos tus datos van a ser permanentemente eliminados del servidor de producción. Luego de aproximadamente un mes, también borramos las copias de seguridad. <br>
        <br>
        Acordáte de que siempre vas a poder seguir usando la versión pública de la página <a href="<?= SYSTEM_HOSTNAME ?>" class="lato thin">tocando acá</a>.
    </p>
</div>

<?php require_once 'views/footer.php'; ?>