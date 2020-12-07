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
                <i class="large material-icons" style="opacity: 1;"> signal_wifi_off </i>
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

        <!-- Default passive events -->
        <script type="text/javascript" src="https://unpkg.com/default-passive-events@2.0.0/dist/index.umd.js"></script>

        <!-- jQuery -->
        <script src="https://unpkg.com/jquery@3.5.1/dist/jquery.min.js" integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg==" crossorigin="anonymous" onload="console.info('jQuery: loaded successfully.');"></script>

        <!-- Day.js -->
        <script src="https://unpkg.com/dayjs@1.8.35/dayjs.min.js" onload="console.info('Day.js: loaded successfully.');"></script>
        <script src="https://unpkg.com/dayjs@1.8.35/plugin/localizedFormat.js" onload="console.info('Day.js/localizedFormat: loaded successfully.');"></script>
        <script src="https://unpkg.com/dayjs@1.8.35/locale/es.js" onload="console.info('Day.js/localizedFormat/es: loaded successfully.');"></script>
        <script src="https://unpkg.com/dayjs@1.8.35/locale/en.js" onload="console.info('Day.js/localizedFormat/en: loaded successfully.');"></script>

        <!-- MaterializeCSS - custom build -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js" onload="console.info('Materialize: loaded successfully.');" defer></script>

        <!-- Masonry -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/masonry/4.2.2/masonry.pkgd.min.js" integrity="sha512-JRlcvSZAXT8+5SQQAvklXGJuxXTouyq8oIMaYERZQasB8SBDHZaUbeASsJWpk0UUrf89DP3/aefPPrlMR1h1yQ==" crossorigin="anonymous" onload="console.info('Masonry: loaded successfully.');" defer></script>

        <!-- imagesloaded -->
        <script src="https://unpkg.com/imagesloaded@4.1.4/imagesloaded.pkgd.min.js" integrity="sha512-S5PZ9GxJZO16tT9r3WJp/Safn31eu8uWrzglMahDT4dsmgqWonRY9grk3j+3tfuPr9WJNsfooOR7Gi7HL5W2jw==" crossorigin="anonymous" onload="console.info('imagesLoaded: loaded successfully.');"></script>

        <!-- Common script -->
        <script src="assets/js/common.min.js?v=10"></script>

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