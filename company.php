<?php

require_once 'includes/main.php';

if (!COMPANY['ENABLE']) {
    redirect(SYSTEM_HOSTNAME . 'exceptions/not_implemented');
}

define('MIN_ACCESS_LEVEL', USER_LEVEL_CUSTOMER);

$title = 'Empresa'; $css = 'company.min.css'; $js = 'company.min.js?v=2';

require_once 'views/header.php';

$user = getCurrentUser();

if ($user == null) {
    redirect(SYSTEM_HOSTNAME);
} else {
    $companies = getCompaniesForUser($user['id']);
}

$company = null;
$ads = null;

if (count($companies) > 0) {
    $company = $companies[0];

    $ads = getAdsForUser(getUserId());
}

?>

<div class="section"></div>
<div class="row container dark-5">
    <div class="row col s12 m6">
        <div class="col s12">
            <p id="companyStatusHeader" class="flow-text center">
                <?= $company == null
                    ? 'Creá tu cuenta de empresa'
                    : 'Tu empresa'
                ?>
            </p>

            <div class="divider"></div>
            <div class="section"></div>
        </div>
        <div class="input-field col s12">
            <input id="companyName" type="text" class="dark-5" value="<?= $company == null ? '' : $company['name'] ?>">
            <label for="companyName"> Nombre visible de tu empresa </label>
            <span class="helper-text" data-error="El nombre de tu empresa no es válido" data-success="El nombre de tu empresa es válido">
                Este es el nombre que vas a ver en la página.
            </span>
        </div>
        <div class="input-field col s12">
            <input id="companyLegalName" type="text" class="dark-5" value="<?= $company == null ? '' : $company['legalName'] ?>">
            <label for="companyLegalName"> Nombre real de tu empresa </label>
            <span class="helper-text" data-error="El nombre de tu empresa no es válido" data-success="El nombre de tu empresa es válido">
                Este es el nombre legal de tu empresa o razón social.
            </span>
        </div>
        <div class="input-field col s12">
            <input id="companyIdentifier" type="text" class="dark-5" value="<?= $company == null ? '' : $company['identifier'] ?>" maxlength="13">
            <label for="mailAddress"> Identificador de tu empresa (CUIT) </label>
            <span class="helper-text" data-error="El identificador no es válido" data-success="El identificador es válido">
                Podés ingresarlo con o sin guiones.
            </span>
        </div>

        <div class="col s12" style="height: 1em;"></div>

        <button
            id="companyUpdateBtn"
            type="button"
            class="bg-light-1 bg-dark-1 waves-effect waves-light btn btn-block col s6 offset-s3 m4 offset-m4 disabled">
            <?= $company == null
                ? 'Crear cuenta'
                : 'Actualizar datos'
            ?>
        </button>
    </div>

    <div class="row col s12 m6">
        <div class="col s12 hide-on-med-and-up">
            <div class="section"></div>
        </div>

        <div class="col s12">
            <p class="flow-text center"> ¿Qué podés hacer? </p>

            <div class="divider"></div>
            <div class="section"></div>
        </div>
        <div class="row col s12">
            <div class="col s12">
                <div class="row">
                    <p class="center col s12">
                        Publicitá tu marca
                    </p>
                    <ul class="disc col s11 offset-s1 m10 offset-m2">
                        <li class="thin"> <b> ¡Es gratis durante 30 días! </b> </li>
                        <li class="thin"> Pagás un costo fijo de <?= MERCADOPAGO_SUBSCRIPTION_COST ?> AR$ por mes. </li>
                        <li class="thin"> Recibís datos analíticos sobre tu publicidad. </li>
                        <li class="thin"> Podés darte de baja cuando quieras sin costo adicional. </li>
                    </ul>
                    <div <?= $company == null ? 'class="tooltipped btn-wrapper" data-tooltip="Antes, tenés que crear tu cuenta de empresa" data-position="bottom"' : '' ?>>
                        <a
                            id="startAdCampaignBtn"
                            type="button"
                            class="bg-light-1 bg-dark-1 waves-effect waves-light btn btn-block col s10 offset-s1 m8 offset-m2 custom-link"
                            href="/company/ads/manage"
                            <?= $company == null ? 'disabled' : '' ?>
                        >
                            <span>
                                <?=
                                    $ads != null && count($ads) > 0
                                        ? 'Administrar anuncios'
                                        : 'Crear anuncio'
                                ?>
                            </span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col s12">
                <div class="row">
                    <p class="center col s12">
                        Eliminá tu empresa
                    </p>
                    <ul class="disc col s11 offset-s1 m10 offset-m2">
                        <li class="thin"> Tu cuenta de empresa se elimina permanentemente. </li>
                        <li class="thin"> Te desvinculamos físicamente del sistema. </li>
                        <li class="thin"> Eliminamos todas las copias al cabo de un mes. </li>
                    </ul>
                    <div <?= $company == null ? 'class="tooltipped btn-wrapper" data-tooltip="Antes, tenés que crear tu cuenta de empresa" data-position="bottom"' : '' ?>>
                        <button
                            id="requestCompanyRemovalBtn"
                            type="button"
                            class="red waves-effect waves-light btn btn-block col s10 offset-s1 m8 offset-m2"
                            <?= $company == null ? 'disabled' : '' ?>
                        >
                            <span> Eliminar empresa </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Delete company modal -->
<div id="deleteCompanyModal" class="modal bg-dark-4 dark-5">
    <div class="modal-content">
        <p class="flow-text"> Estás a punto de eliminar <span class="companyName"> </span> </p>

        <div class="row">
            <div class="col s12">
                <p>
                    Para confirmar la eliminación de tu empresa, es necesario que escribas el nombre legal de la misma. <br>
                    <br>
                    Tené en cuenta que es necesario que respetes mayúsculas y minúsculas. <br>
                    <br>
                    Escribí esto: <b> <span class="companyLegalName"> </span> </b>
                </p>
            </div>

            <div class="input-field col s12">
                <input id="toDeleteCompanyLegalName" type="text" class="dark-5">
                <label for="toDeleteCompanyLegalName"> Nombre legal de tu empresa </label>
                <span class="helper-text" data-error="El nombre no coincide" data-success="El nombre es válido"></span>
            </div>
        </div>
    </div>
    <div class="modal-footer bg-dark-4 dark-5">
        <button id="deleteCompanyBtn" href="#!" class="waves-effect waves-light btn-flat dark-5"> Eliminar </a>
    </div>
</div>

<script>
    let company = <?= json_encode($company); ?>
</script>

<?php require_once 'views/footer.php'; ?>