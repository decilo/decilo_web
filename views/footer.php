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
                <div class="modal-footer bg-light-5 bg-dark-4">
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

            <!-- No password login modal -->
            <div id="noPasswordLoginModal" class="modal bg-dark-4 dark-5">
                <div class="modal-content center">
                    <p class="flow-text roboto"> Revisá tu bandeja de entrada </p>
                    <i class="material-icons large"> email </i>
                    <p class="lato thin"> En unos segundos vas a recibir un mail en <strong class="mailAddress"></strong> para continuar. </p>
                </div>
                <div class="modal-footer bg-dark-4 dark-5">
                    <a href="#!" target="_blank" class="waves-effect waves-light btn-flat dark-5"> Ver mail </a>
                </div>
            </div>

            <!-- Comments modal -->
            <div id="commentsModal" class="modal modal-fixed-footer bg-dark-4 dark-5">
                <div class="modal-content">
                    <p class="flow-text">
                        Comentarios
                    </p>

                    <div id="commentsMessageWrapper"></div>
                </div>
            </div>

            <button id="noInternetBtn" type="button" class="btn-floating btn-large red no-select fab-left">
                <i class="large material-icons deferred-icon" style="opacity: 1;"> signal_wifi_off </i>
            </button>

        </main>
        <footer></footer>

        <!-- Data collection consent modal -->
        <div id="gdprModal" class="modal bottom-sheet">
            <div class="modal-content bg-dark-3">
                <div class="modal-content dark-5">
                    <h4>Sobre tu privacidad</h4>
                    <p>
                        Usamos cookies y otras técnicas de rastreo para mejorar tu experiencia de navegación y mostrarte contenidos personalizados, para analizar el tráfico en nuestra web y para comprender de dónde vienen nuestros visitantes. <br>
                        <br>
                        Si querés saber más, leé nuestra <a href="/privacy">política de privacidad</a>.
                    </p>
                </div>
            </div>
            <div class="modal-footer bg-light-5 bg-dark-4">
                <button id="acceptCollectionBtn" class="waves-effect waves-light btn-flat dark-5"> Continuar </button>
            </div>
        </div>

        <script>
            const EXCEPTION             = <?= json_encode(isset($_GET['e']) ? $_GET['e'] : null);   ?>;
            const DISPLAY_GDPR_MODAL    = <?= json_encode($_SERVER['REQUEST_URI'] != '/privacy');   ?>;
        </script>

        <?php

        if (USE_BUNDLE) {
            print '
            <!-- Bundle -->
            <script type="text/javascript" src="assets/js/bundle.min.js?v=' . BUNDLE_VERSION . '" onload="console.info(\'Bundle: loaded successfully.\');"></script>';
        } else {
            foreach (CORE_SCRIPTS as $name => $src) {
                print '
                <!-- ' . $name . ' -->
                <script
                    type="text/javascript"
                    src="' . $src . (
                        strpos($src, 'http') === false
                            ? '?v=' . getVersionFromPath($src)
                            : ''
                    ) . '"
                    onload="console.info(\'' . $name . ': loaded successfully.\');"
                >
                </script>';
            }
        }

        if (isset($js)) {
            if (is_array($js)) {
                foreach ($js as $src) {
                    print '<script src="' . (strpos($src, 'http') !== false ? $src : 'assets/js/' . $src . '?v=' . getVersionFromPath('assets/js/' . $src)) . '"></script>';
                }
            } else {
                print '<script src="' . (strpos($js, 'http') !== false ? $js : 'assets/js/' . $js . '?v=' . getVersionFromPath('assets/js/' . $js)) . '"></script>';
            }
        }

        ?>
    </body>
</html>

<?= preg_replace('/\s+/', ' ', ob_get_clean()) ?>