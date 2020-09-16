<?php 

$title  = 'Crear pedido'; 

require_once 'includes/main.php';

$css    = 'create.css'; 
$js     = [
    'https://www.bing.com/api/maps/mapcontrol?key=' . BING_MAPS_KEY . '&callback=loadMapScenario',
    'create.js'
];

require_once 'views/header.php';

$user = getCurrentUser();

$dataProtectionHint = 
    'En ' . SYSTEM_TITLE . ' queremos ser transparentes y proteger tu información personal. <br>
     <br>
     Es por eso que no almacenamos tus datos de facturación y, pasad' . ($user == null ? 'a cierta cantidad de tiempo' : 'os <b>' . $user['killSwitchMonths'] . ' meses</b>') . ' sin actividad, desactivamos tu cuenta y borramos los datos sensibles.<br>
     <br>
     Recordá que podés cambiar esto en <span href="profile.php" class="custom-link blue-text">mi cuenta</span>.';

?>

<ul id="mobileStepsTable" class="section hide-on-med-and-up show-on-medium-and-down col s12 steps-table">
    <li><a href="#description">Describí el problema</a></li>
    <li><a href="#setup">Configurá tu pedido</a></li>
    <li><a href="#shippingLocation">Seleccioná dónde entregarlo</a></li>
</ul>

<div class="row">
    <div class="col s12 l10">
        <div id="description" class="scrollspy current-step step" tabindex="-1">
            <h4> Describí el problema </h4>
            <p>
                Acá podés contarnos qué creés que pasó, qué estabas haciendo cuando empezaste a tener el problema y cuál fue el momento en que la falla fue crítica.
            </p>
            <div class="row">
                <div class="input-field col s12">
                    <textarea id="issueDescriptionText" class="materialize-textarea" data-length="65535"></textarea>
                    <label for="issueDescriptionText">¿Qué pasó?</label>
                </div>
            </div>

            <?= getNavigationButtons(true) ?>
        </div>

        <div id="setup" class="scrollspy next-step step" tabindex="-1">
            <h4> Configurá tu pedido </h4>
            <p>
                Ahora, seleccioná todo lo que sientas que coincide con tu situación.
            </p>
            <?php
                foreach (getDefaultIssues() as $issue) {
                    print 
                    '<p>
                        <label>
                            <input type="checkbox" class="filled-in" issue="' . $issue['id'] . '" disabled>
                            <span> ' . $issue['description'] . ' </span>
                        </label>
                     </p>';
                }
            ?>
            
            <p class="new-issue">
                <label>
                    <input type="checkbox" class="filled-in" issue="-1" disabled />
                    <span>
                        <input placeholder="Otro" type="text" class="checkbox-input" disabled>
                    </span>
                </label>
            </p>

            <?= getNavigationButtons(false, null, 'enableMapBtn') ?>
        </div>

        <div id="shippingLocation" class="scrollspy next-step step" tabindex="-1">
            <h4> Seleccioná dónde entregarlo </h4>
            <p>
                En este paso vas a poder seleccionar dónde entregar tu computadora.
            </p>
            <div id='shippingLocationPicker' style="height: 85vh;" class="col s12"></div>

            <?= getNavigationButtons(false, 'disableMapBtn') ?>
        </div>
    </div>

    <ul id="stepsTable" class="section steps-table hide-on-med-and-down col m2 l2">
        <li><a href="#description">Describí el problema</a></li>
        <li><a href="#setup">Configurá tu pedido</a></li>
        <li><a href="#shippingLocation">Seleccioná dónde entregarlo</a></li>
        
        <div class="section"></div>

        <p>¿Cómo procesamos tus datos?</p>

        <p class="light-paragraph">
            <?= $dataProtectionHint ?>
        </p>
    </ul>
</div>

<div class="row hide-on-med-and-up show-on-medium-and-down">
    <div class="col s12">
        <h5 class="center">¿Cómo procesamos tus datos?</h5>

        <p class="light-paragraph">
            <?= $dataProtectionHint ?>
        </p>
    </div>
</div>

<?php require_once 'views/footer.php'; ?>