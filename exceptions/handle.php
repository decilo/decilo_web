<?php 

define('MAINTENANCE', true);

$css = 'maintenance.min.css'; $js = 'handle.min.js';

chdir('..');

require_once 'includes/main.php'; 

$exception = [
  'title' => 'Algo anda mal',
  'icon'  => 'error',
  'info'  =>
    '<span class="roboto thin dark-5"> No sabemos qué pasó, pero algo falló. <br>
     <br>
     Si querés, por ahora, <a href="/" onclick="(e) => { e.preventDefault(); window.history.back(); }">volvé a la página anterior</a>.</span>'
];

if (isset($_GET['e'])) {
  switch ($_GET['e']) {
    case 404:
      $exception = [
        'title' => 'No encontramos eso',
        'icon'  => 'link',
        'info'  =>
          '<span class="roboto thin dark-5">La página que visitaste ya no existe (o nunca existió), ¿quién sabe?</span>
           <br>
           <br>
           <span class="roboto thin dark-5">No te preocupes, hay mucho más que ver, <a href="' . SYSTEM_HOSTNAME . '" class="custom-link">tocá acá</a>.'
      ];

      break;
    case 400:
      $exception = [
        'title' => 'Tenemos que hablar',
        'icon'  => 'broken_image',
        'info'  => '<span class="roboto thin dark-5">No fue posible procesar tu solicitud porque no coincide con ninguna de las esperadas.</span>'
      ];

      break;
    case 403:
      $exception = [
        'title' => 'No podés hacer eso',
        'icon'  => 'block',
        'info'  => '<span class="roboto thin dark-5">No tenés permitido acceder a esa página.</span>'
      ];

      break;
    case 418:
      $exception = [
        'title' => 'No tenemos teteras',
        'icon'  => 'free_breakfast',
        'info'  => '<span class="roboto thin dark-5">Lo sentimos, pero no hay teteras disponibles para procesar la solicitud, sólo cafeteras.</span>'
      ];

      break;
    case 500:
      $exception = [
        'title' => 'Algo anda muy mal',
        'icon'  => 'cloud_off',
        'info'  => '<span class="roboto thin dark-5">Un problema interno del servidor previene que puedas usar esta página, esperá un rato y <span class="hand soft-link" onclick="window.history.back();">probá otra vez</span>.</span>'
      ];

      break;
    case 424:
      $exception = [
        'title' => 'No tenés internet',
        'icon'  => 'signal_wifi_off',
        'info'  =>
          '<span class="roboto thin dark-5">No tenés una copia almacenada de esta página. <br>
           <br>
           Cuando te conectes, podés <span class="hand soft-link" onclick="window.location.reload();">probar otra vez</span>. <br>
           <br>
           Si querés, por ahora, <a href="/" onclick="(e) => { e.preventDefault(); window.history.back(); }">volvé a la página anterior</a>.</span>'
      ];

      break;
    case 501:
      $exception = [
        'title' => 'No implementado',
        'icon'  => 'watch_later',
        'info'  =>
          '<span class="roboto thin dark-5"> Por ahora, esa característica no está disponible. <br>
           <br>
           Si querés, <a href="/" onclick="(e) => { e.preventDefault(); window.history.back(); }">volvé a la página anterior</a> y probá más tarde.</span>'
      ];

      break;
    case 1000:
      $exception = [
        'title' => 'Tu navegador es muy antiguo',
        'icon'  => 'update',
        'info'  =>
          '<span class="roboto thin dark-5"> Lamentablemente, el navegador que estás usando no es compatible. <br>
           <br>
           Te recomendamos actualizarlo lo antes posible y probar otra vez.'
      ];

      define('SKIP_CORE_SCRIPTS', true);

      break;
  }
}

require_once 'views/header.php';

?>

<div id="notReadyWrapper" class="valign-wrapper">
  <div class="row">
    <div class="col s12 center">
        <i class="material-icons deferred-icon large dark-1"><?= $exception['icon'] ?></i>
    </div>

    <div class="col s12">
      <p class="center">
        <span class="real-text roboto medium dark-5">
            <h5 class="large thin dark-5 center">
              <?= $exception['title'] ?>
            </h5>
        </span>
      </p>
    </div>

    <div class="col s12 center">
        <br>
        <?= $exception['info'] ?>
    </div>
  </div>
</div>

<script>
  const EXCEPTION_CODE = <?= json_encode($_GET['e']) ?>;
</script>

<?php require_once 'views/footer.php'; ?>