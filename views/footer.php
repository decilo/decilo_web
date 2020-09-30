            <!-- Login/signup modal -->
            <div id="loginModal" class="modal bottom-sheet">
                <div class="modal-content bg-dark-3">
                    <div class="row">
                        <div id="loginForm" class="col s12 row">
                            <div class="input-field col s12">
                                <input id="loginMailAddress" type="email" class="dark-5">
                                <label for="loginMailAddress"> Correo electrónico </label>
                                <span class="helper-text" data-error="La dirección no es válida" data-success="La dirección es válida">Tu dirección de correo electrónico</span>
                            </div>
                            <div class="input-field col s12" style="display: none;">
                                <input id="loginPassword" type="password" class="dark-5">
                                <label for="loginPassword"> Contraseña </label>
                                <span class="helper-text" data-error="Tenés que ingresar una contraseña" data-success="La contraseña es válida">Tu contraseña</span>
                            </div>
                        </div>
                        <div id="loginStatus" class="col s12 dark-5 center" style="display: none;"></div>
                    </div>
                </div>
                <div class="modal-footer bg-dark-4">
                    <div id="loginPreloader" class="preloader-wrapper small active preloader-bottom-small left" style="display: none;">
                        <div class="spinner-layer border-dark-5">
                            <div class="circle-clipper left">
                                <div class="circle"></div>
                            </div>
                            <div class="gap-patch">
                                <div class="circle"></div>
                            </div>
                            <div class="circle-clipper right">
                                <div class="circle"></div>
                            </div>
                        </div>
                    </div>
                    <button id="tryAccountRecoveryBtn" class="waves-effect waves-light btn-flat dark-5" style="display: none;"> Olvidé la contraseña </button>
                    <button id="continueLoginBtn" class="waves-effect waves-light btn-flat dark-5"> Continuar </button>
                </div>
            </div>

        </main>
        <footer></footer>

        <script>
            const EXCEPTION = <?= json_encode(isset($_GET['e']) ? $_GET['e'] : null); ?>
        </script>

        <!-- jQuery -->
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js" onload="console.info('jQuery: loaded successfully.');"></script>

        <!-- jQuery Visible -->
        <script src="https://cdn.jsdelivr.net/npm/element-in-view@0.1.0/dist/element-in-view.min.js"></script>

        <!-- MaterializeCSS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js" onload="console.info('Materialize: loaded successfully.');" defer></script>

        <!-- Masonry -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/masonry/4.2.2/masonry.pkgd.min.js" integrity="sha512-JRlcvSZAXT8+5SQQAvklXGJuxXTouyq8oIMaYERZQasB8SBDHZaUbeASsJWpk0UUrf89DP3/aefPPrlMR1h1yQ==" crossorigin="anonymous" onload="console.info('Masonry: loaded successfully.');" defer async></script>
        
        <!-- Common script -->
        <script src="assets/js/common.js"></script>

        <?php

        if (isset($js)) {
            if (is_array($js)) {
                foreach ($js as $src) {
                    print '<script src="' . (strpos($src, 'http') !== false ? $src : 'assets/js/' . $src) . '"></script>';
                }
            } else {
                print '<script src="' . (strpos($js, 'http') !== false ? $js : 'assets/js/' . $js) . '"></script>';
            }
        }

        ?>
    </body>
</html>