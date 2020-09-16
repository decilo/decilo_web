<?php 

$title = 'Iniciá sesión'; $css = 'login.css'; $js = 'login.js'; $nav = false;

require_once 'includes/main.php';

if (getUserId() != null) {
  redirect('index.php');
}

require_once 'views/header.php';

?>

<div id="notReadyWrapper" class="valign-wrapper" style="display: none;">
  <div class="row">
    <div class="col s12 center">
      <img src="assets/img/logo.png" style="width: 5rem; height: 5.5rem;">
    </div>

    <div class="col s12">
      <p class="center">
        <span class="real-text" style="display: none;">
          Te damos la bienvenida a <?= SYSTEM_TITLE ?>.
        </span>

        <noscript>
          Lo sentimos, es necesario que dejes Javascript activado. :(
        </noscript>
      </p>
    </div>

    <div class="progress col s6 offset-s3 red">
        <div class="indeterminate red"></div>
    </div>
  </div>
</div>

<div id="loginWrapper" class="container center-align valign-wrapper scale-transition scale-out">

  <div id="fullLoginForm" class="z-depth-3 y-depth-3 x-depth-3 bg-dark-3 green-text lighten-4 row">
    <div class="col s12">

      <div class="row">
        <div class="input-field col s12">
          <input class="validate dark-5" type="text" name="username" id="username" required="">
          <label for="username">Nombre de usuario o correo electrónico</label>
        </div>
      </div>
      <div class="row">
        <div class="input-field col s12">
          <input class="validate dark-5" type="password" name="password" id="password" required="">
          <label for="password">Contraseña</label>
        </div>
        <div class="row">
          <div class="col s12">
            <label>
              <a>
                <b class="dark-1">
                  Olvidé mi contraseña
                </b>
              </a>
            </label>
          </div>
        </div>
      </div>
      <div class="row">
        <button type="button" id="loginBtn" class="col offset-s3 s6 btn btn-small bg-dark-1 dark-3 dark-5 waves-effect z-depth-1 y-depth-1"> Iniciar sesión </button>
      </div>
    </div>
  </div>

</div>

<?php require_once 'views/footer.php'; ?>