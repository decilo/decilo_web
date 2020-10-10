<?php 

$css = 'maintenance.css';
$maintenance = true;

chdir('..');

require_once 'includes/main.php'; 

if (isset($_GET['e'])) {
  switch ($_GET['e']) {
    case 404:
      $title = 'No encontramos eso';
      $icon  = 'link';
      $info  = 
        '<span class="roboto thin dark-5">La página que visitaste ya no existe (o nunca existió), ¿quién sabe?</span>
         <br>
         <br>
         <span class="roboto thin dark-5">No te preocupes, hay mucho más que ver, <a href="' . SYSTEM_HOSTNAME . '" class="custom-link">tocá acá</a>.';

      break;
    case 400:
      $title = 'Tenemos que hablar';
      $icon  = 'broken_image';
      $info  = '<span class="roboto thin dark-5">No fue posible procesar tu solicitud porque no coincide con ninguna de las esperadas.</span>';

      break;
    case 403:
      $title = 'No podés hacer eso';
      $icon  = 'block';
      $info  = '<span class="roboto thin dark-5">No tenés permitido acceder a esa página.</span>';

      break;
    case 418:
      $title = 'No tenemos teteras';
      $icon  = 'free_breakfast';
      $info  = '<span class="roboto thin dark-5">Lo sentimos, pero no hay teteras disponibles para procesar la solicitud, sólo cafeteras.</span>';

      break;
    case 500:
      $title = 'Algo anda muy mal';
      $icon  = 'cloud_off';
      $info  = '<span class="roboto thin dark-5">Un problema interno del servidor previene que puedas usar esta página, esperá un rato y <a href="/" onclick="(e) => { e.preventDefault(); window.history.back(); }">probá otra vez</a>.</span>';

      break;
    default:
      exit();
  }
} else {
  exit();
}

require_once 'views/header.php';

?>

<div id="notReadyWrapper" class="valign-wrapper">
  <div class="row">
    <div class="col s12 center">
        <i class="material-icons large dark-1"><?= $icon ?></i>
    </div>

    <div class="col s12">
      <p class="center">
        <span class="real-text roboto medium dark-5">
            <h5 class="large thin dark-5 center">
              <?= $title ?>
            </h5>
        </span>
      </p>
    </div>

    <div class="col s12 center">
        <br>
        <?= $info ?>
    </div>
  </div>
</div>

<?php require_once 'views/footer.php'; ?>