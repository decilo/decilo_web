            <div class="fixed-action-btn">
                <a
                    id="backToTopBtn"
                    class="btn-floating btn-large bg-dark-1 waves-light hand no-select tooltipped"
                    style="display: none;"
                    data-position="left"
                    data-tooltip="Volver al principio"
                >
                    <i class="material-icons">keyboard_arrow_up</i>
                </a>
            </div>

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
                    <button id="tryAccountRecoveryBtn" class="waves-effect waves-light btn-flat dark-5" style="display: none;"> Olvidé la contraseña </button>
                    <button id="continueLoginBtn" class="waves-effect waves-light btn-flat dark-5"> Continuar </button>
                </div>
            </div>

        </main>
        <footer></footer>

        <!-- jQuery -->
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js" onload="console.info('jQuery: loaded successfully.');"></script>

        <!-- Day.js -->
        <script id="dayJS" src="https://unpkg.com/dayjs@1.8.21/dayjs.min.js" onload="console.info('Day.js: loaded successfully.');" defer></script>

        <!-- Day.js localized format -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.8.35/locale/es.min.js" integrity="sha512-OXy30agOq/KkLVI1lickBn2JCJGlTxoTWXUGEpgrD8XhWG8cuZOHfySxm5cuUo94BwLT7cJ9qAmP1Am93j6IWg=="   crossorigin="anonymous" onload="console.info('Day.js/locale-en: loaded successfully.');" defer></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.8.35/locale/en.min.js" integrity="sha512-2uenbLpmKVYJT0OEPCEWCiKMkX6aes4OTUv8RQxEquaPw7AsDkwvFCHG87JGzaiC8HUuCVCe2UbUx0HHOkG5pA=="   crossorigin="anonymous" onload="console.info('Day.js/locale-es: loaded successfully.');" defer></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.8.35/plugin/localizedFormat.min.js" onload="console.info('Day.js/localizedFormat: loaded successfully.');" defer></script>

        <!-- MaterializeCSS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js" onload="console.info('Materialize: loaded successfully.');" defer></script>

        <!-- Masonry -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/masonry/4.2.2/masonry.pkgd.min.js" integrity="sha512-JRlcvSZAXT8+5SQQAvklXGJuxXTouyq8oIMaYERZQasB8SBDHZaUbeASsJWpk0UUrf89DP3/aefPPrlMR1h1yQ==" crossorigin="anonymous" onload="console.info('Masonry: loaded successfully.');" defer async></script>
        
        <!-- Common script -->
        <script src="assets/js/common.js"></script>

        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script src="https://www.googletagmanager.com/gtag/js?id=<?= GOOGLE_ANALYTICS_KEY ?>" onload="setupGoogleAnalytics();" async defer></script>

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