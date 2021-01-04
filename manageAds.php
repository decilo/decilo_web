<?php

require_once 'includes/main.php';

define('MIN_ACCESS_LEVEL', USER_LEVEL_CUSTOMER);

$title = 'Anuncios'; $js = 'manageAds.js';

require_once 'views/header.php';

$userId = getUserId();

if ($userId == null) {
    redirect(SYSTEM_HOSTNAME);
} else {
    $companies = getCompaniesForUser($userId);
}

if (count($companies) < 1) {
    redirect(SYSTEM_HOSTNAME);
}

?>

<div class="section"></div>
<div class="row container dark-5">
    <div class="row col s12 m6">
        <div class="col s12">
            <p class="flow-text center">
                Tus anuncios
            </p>

            <div class="divider"></div>
            <div class="section"></div>

            <ul class="collection" style="display: none;">
                <li id="pausedWarningCollection" class="collection-item avatar bg-dark-12" style="display: none;">
                    <i class="material-icons circle bg-dark-1 bg-light-1"> credit_card </i>

                    <span class="title">
                        <b>
                            Tu<span class="plurals">s</span> anuncio<span class="plurals">s</span> está<span class="plurals">n</span> pausado<span class="plurals">s</span>
                        </b>
                    </span>

                    <p>
                        Para publicar tu<span class="plurals">s</span> anuncio<span class="plurals">s</span>, añadí un método de pago. <br>
                        Es rápido, fácil y seguro a través de Mercado Pago. <br>
                        <br>
                        <a href="/company/billing">
                            Agregar método de pago
                        </a>
                    </p>
                </li>

                <li id="reviewingWarningCollection" class="collection-item avatar bg-dark-12" style="display: none;">
                    <i class="material-icons circle bg-dark-1 bg-light-1"> free_breakfast </i>

                    <span class="title"> <b> Tenés <span class="reviewingHint"> un </span> anuncio<span class="plurals">s</span> en revisión </b> </span>

                    <p>
                        Cuando lo<span class="plurals">s</span> hayamos verificado, lo<span class="plurals">s</span> vas a ver publicado<span class="plurals">s</span>.
                    </p>
                </li>
            </ul>

            <div id="container" class="gridContainer">

                <div class="row">
                    <!-- Messages container -->
                </div>

            </div>
        </div>
    </div>

    <div class="row col s12 m6">
        <div class="col s12 hide-on-med-and-up">
            <div class="section"></div>
        </div>

        <div class="col s12">
            <p class="flow-text center"> Creá un anuncio </p>

            <div class="divider"></div>
            <div class="section"></div>
        </div>
        <div class="row col s12">
            <div class="input-field col s12">
                <label> Vista previa </label>

                <div id="adPreview" class="row"></div>
            </div>
            <div class="input-field col s12">
                <select>
                    <?php
                        $first = true;

                        foreach ($companies as $company) {
                            print
                                '<option value="' . $company['id'] . '" ' . ($first ? 'selected' : '') . '> ' . 
                                    $company['name'] . ' (' . $company['legalName'] . ', ' . $company['identifier'] . ')
                                 </option>';
                        }
                    ?>
                </select>
                <label> Seleccioná una empresa </label>
            </div>

            <div class="input-field col s12">
                <input id="content" type="text" class="dark-5" value="">
                <label for="content"> Contenido del anuncio </label>
                <span class="helper-text" data-error="El contenido no puede estar vacío" data-success="El contenido es válido">
                    Este es el contenido que van a ver los visitantes que reciban tu anuncio. Podés incluír enlaces externos y se van a procesar automáticamente.
                </span>
            </div>

            <div class="col s12" style="height: 1em;"></div>

            <button
                id="createAdBtn"
                type="button"
                class="bg-light-1 bg-dark-1 waves-effect waves-light btn btn-block col s6 offset-s3 m4 offset-m4"
            >
                Crear anuncio
            </button>
        </div>
    </div>
</div>

<!-- AD remove request modal -->
<div id="requestRemovalModal" class="modal">
    <div class="modal-content bg-dark-4 dark-5">
        <p class="flow-text roboto thin"> ¿Querés eliminar este anuncio? </h4>
        <p class="lato thin"> El anuncio que seleccionaste se va a eliminar permanentemente, por lo que este proceso es irreversible. </p>
    </div>
    <div class="divider"></div>
    <div class="modal-footer bg-dark-4">
        <button href="#!" class="modal-close waves-effect waves-light btn-flat dark-5"> Cancelar </button>
        <button id="confirmAdRemovalBtn" data-ad="-1" class="waves-effect waves-light btn-flat dark-5"> Eliminar </button>
    </div>
</div>

<script>
    const COMPANIES = <?= json_encode($companies);                 ?>;
    const ADS       = <?= json_encode(getAdsForUser(getUserId())); ?>;
</script>

<?php require_once 'views/footer.php'; ?>