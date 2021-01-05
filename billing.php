<?php

require_once 'includes/main.php';

define('MIN_ACCESS_LEVEL', USER_LEVEL_CUSTOMER);

$title = 'Facturación';

$js = [
    'billing.min.js?v=3',
    'https://cdnjs.cloudflare.com/ajax/libs/card/2.5.0/jquery.card.min.js',
    'https://cdn.jsdelivr.net/npm/payform@1.4.0/dist/jquery.payform.min.js',
    'https://secure.mlstatic.com/sdk/javascript/v1/mercadopago.js'
];

require_once 'views/header.php';

$user = getCurrentUser();

if ($user == null) {
    redirect(SYSTEM_HOSTNAME);
} else {
    $companies = getCompaniesForUser($user['id']);
}

if (count($companies) > 0) {
    $subscriptions = getSubscriptions($companies[0]['id']);
} else {
    redirect(SYSTEM_HOSTNAME);
}

?>

<div class="section"></div>
<div class="row container dark-5">
    <div class="row col s12 m6">
        <div class="col s12">
            <p class="flow-text center">
                Añadí un medio de pago
            </p>

            <div class="divider"></div>
            <div class="section"></div>

            <ul class="collection">
                <li id="trialInformationCollection" class="collection-item avatar bg-dark-12" style="display: none;">
                    <i class="material-icons circle bg-dark-1 bg-light-1"> free_breakfast </i>

                    <span class="title"> <b> Publicá gratis por 30 días </b> </span>

                    <p>
                        Empezá a publicitar sin pagar ni un centavo durante un mes. <br>
                        Si te arrepentís, podés darte de baja cuando quieras.
                    </p>
                </li>

                <li id="tariffsInformationCollection" class="collection-item avatar bg-dark-12" style="display: none;">
                    <i class="material-icons circle bg-dark-1 bg-light-1"> attach_money </i>

                    <span class="title"> <b> Tarifas y cobranzas </b> </span>

                    <p>
                        Procesamos tu subscripción a través de Mercado Pago.    <br>
                        Si necesitás ayuda, no dudes en contactarnos.           <br>
                        <br>
                        <a href="<?= WHATSAPP_LINK ?>">
                            Abrir chat
                        </a>
                    </p>
                </li>

                <li id="alreadySubscribedCollection" class="collection-item avatar bg-dark-12" style="display: none;">
                    <i class="material-icons circle bg-dark-1 bg-light-1"> money_off </i>

                    <span class="title"> <b> No podés agregar más tarjetas </b> </span>

                    <p>
                        Ya tenés una subscripción activa.               <br>
                        Si necesitás hacer cambios, primero, cancelála. <br>
                    </p>
                </li>
            </ul>

            <div class="section"></div>

            <div id="billingForm" class="row col s12" style="display: none;">
                <div class="card-wrapper"></div>

                <div class="col s12" style="padding-top: 2em;">
                    <div class="section"></div>
                </div>

                <div class="input-field col s12">
                    <select id="companySelect">
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

                <form id="paymentForm">
                    <div class="input-field col s12">
                        <input
                            data-checkout="cardNumber"
                            type="hidden"
                            class="dark-5"
                            value=""
                            onselectstart="return false"
                            onpaste="return false"
                            oncopy="return false"
                            oncut="return false"
                            ondrag="return false"
                            ondrop="return false"
                            autocomplete=off
                        >
                        <input
                            id="cardNumber"
                            type="text"
                            class="dark-5"
                            value=""
                            onselectstart="return false"
                            onpaste="return false"
                            oncopy="return false"
                            oncut="return false"
                            ondrag="return false"
                            ondrop="return false"
                            autocomplete=off
                        >
                        <label for="cardNumber"> Número de la tarjeta </label>
                        <span class="helper-text" data-error="El número no es válido" data-success="El número es válido">
                            El número de tu tarjeta de crédito o débito
                        </span>
                    </div>

                    <div class="hide">
                        <input
                            type="text"
                            placeholder="MM"
                            id="cardExpirationMonth"
                            data-checkout="cardExpirationMonth"
                            onselectstart="return false"
                            onpaste="return false"
                            oncopy="return false"
                            oncut="return false"
                            ondrag="return false"
                            ondrop="return false"
                            autocomplete=off
                        >
                        <input
                            type="text"
                            placeholder="YY"
                            id="cardExpirationYear"
                            data-checkout="cardExpirationYear"
                            onselectstart="return false"
                            onpaste="return false"
                            oncopy="return false"
                            oncut="return false"
                            ondrag="return false"
                            ondrop="return false"
                            autocomplete=off
                        >
                    </div>

                    <div class="input-field col s12 m6">
                        <input
                            id="cardExpiration"
                            type="text"
                            class="dark-5"
                            value=""
                            maxlength="7"
                            onselectstart="return false"
                            onpaste="return false"
                            oncopy="return false"
                            oncut="return false"
                            ondrag="return false"
                            ondrop="return false"
                            autocomplete=off
                        >
                        <label for="cardExpiration"> Fecha de expiración (MM/AA) </label>
                        <span class="helper-text" data-error="La fecha no es válida" data-success="La fecha es válida">
                            Aparece como "vencimiento" o "hasta"
                        </span>
                    </div>

                    <div class="input-field col s12 m6">
                        <input
                            id="cardCVC"
                            data-checkout="securityCode"
                            type="text"
                            class="dark-5"
                            value=""
                            onselectstart="return false"
                            onpaste="return false"
                            oncopy="return false"
                            oncut="return false"
                            ondrag="return false"
                            ondrop="return false"
                            autocomplete=off
                        >
                        <label for="cardCVC"> Código de seguridad </label>
                        <span class="helper-text" data-error="El código de seguridad no es válido" data-success="El código de seguridad es válido">
                            El número que aparece en el dorso
                        </span>
                    </div>

                    <div class="input-field col s12">
                        <input
                            id="cardFullName"
                            data-checkout="cardholderName"
                            type="text"
                            class="dark-5"
                            value=""
                        >
                        <label for="cardFullName"> Nombre completo </label>
                        <span class="helper-text" data-error="El nombre no puede estar vacío" data-success="El nombre es válido">
                            Tu nombre tal como se ve en la tarjeta
                        </span>
                    </div>

                    <div class="input-field col s12">
                        <input id="mailAddress" type="email" class="dark-5" value="">
                        <label for="mailAddress"> Correo electrónico </label>
                        <span class="helper-text" data-error="El correo electrónico no es válido" data-success="El correo electrónico es válido">
                            Donde vas a recibir las facturas y notificaciones
                        </span>
                    </div>

                    <div class="input-field col s12">
                        <select
                            id="documentTypeSelect"
                            data-checkout="docType"
                            readonly
                        >
                            <option value="-1"> Cargando... </option>
                        </select>
                        <label> Seleccioná un tipo de documento </label>
                    </div>

                    <div class="input-field col s12">
                        <input
                            id="documentNumber"
                            data-checkout="docNumber"
                            type="text"
                            class="dark-5"
                            value=""
                        >
                        <label for="documentNumber"> Número de documento (DNI) </label>
                        <span class="helper-text" data-error="El DNI no es válido" data-success="El DNI es válido">
                            Podés ingresarlo con o sin puntos
                        </span>
                    </div>
                </form>

                <div id="invoiceHint" class="col s12" style="display: none;">
                    <blockquote></blockquote>
                </div>

                <div class="col s12" style="height: 1em;"></div>

                <button
                    id="saveBillingMethodBtn"
                    type="button"
                    class="bg-light-1 bg-dark-1 waves-effect waves-light btn btn-block col s6 offset-s3 m4 offset-m4"
                    disabled
                >
                    Continuar
                </button>

                <div id="transactionProgressBar"></div>
            </div>
        </div>
    </div>

    <div class="row col s12 m6">
        <div class="col s12">
            <p class="flow-text center">
                Tus subscripciones
            </p>

            <div class="divider"></div>
            <div class="section"></div>

            <div id="paymentMethodsContainer" style="display: none;">
                <ul class="collection"></ul>
            </div>
        </div>
    </div>
</div>

<script>
    const COMPANIES     = <?= json_encode($companies);      ?>;
    const SUBSCRIPTIONS = <?= json_encode($subscriptions);  ?>;
    
    const MERCADOPAGO_PUBLIC_KEY        = <?= json_encode(MERCADOPAGO_KEYS['PUBLIC']);   ?>;
    const MERCADOPAGO_SUBSCRIPTION_COST = <?= json_encode(MERCADOPAGO_SUBSCRIPTION_COST) ?>;
</script>

<script src="https://www.mercadopago.com/v2/security.js" view="item"></script>

<?php require_once 'views/footer.php'; ?>