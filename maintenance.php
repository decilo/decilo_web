<?php 

$title = 'Página principal';
$css = 'maintenance.css'; $js = 'maintenance.js';
$maintenance = true;

require_once 'includes/main.php'; 
require_once 'views/header.php';

?>

<div id="notReadyWrapper" class="valign-wrapper" style="display: none;">
  <div class="row">
    <div class="col s12 center">
        <i class="material-icons large dark-2">error</i>
    </div>

    <div class="col s12">
      <p class="center">
        <span class="real-text roboto medium dark-5" style="display: none;">
            <span class="lato thin">
                ¡Uh! Algo salió mal.
            </span>
        </span>

        <noscript>
          Lo sentimos, es necesario que dejes Javascript activado. :(
        </noscript>
      </p>
    </div>

    <div class="progress col s4 offset-s4 red">
        <div class="indeterminate red"></div>
    </div>

    <div class="col s12 center">
        <br>
        <span id="retryCountdown" class="roboto thin dark-5">Reintentando en <span id="remainingTime" class="medium">5</span> segundos...</span>
        <span id="retryingHint" class="roboto thin dark-5" style="display: none;">Reintentando...</span>
    </div>
  </div>
</div>

<?php require_once 'views/footer.php'; ?>