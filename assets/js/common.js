const NO_MESSAGES_HINT = 
    `¡Nada por acá!
     <br>
     <br>
     Pasále el link a tus amigos y empezá a recibir mensajes.`;

let loader = () => {
    console.info('loader: no loader was specified.');
};

let deferredPreloader = null;

function run(url, action, values, before = function () {}, overridesFailure = false) {
    console.info('run \n\nurl:', url, 'action:', action, 'values:', values, 'before:', before);

    return $.ajax(
        {
            url: 'services/' + url + '.php',
            type: 'POST',
            processData: false,
            contentType: 'application/json',
            beforeSend: before,
            data: JSON.stringify(
                {
                    'action'    : action,
                    'values'    : values
                }
            )
        }
    )
    .fail(function (error) {
        if (!overridesFailure) {
            console.error(error);

            toast('Algo salió mal, por favor probá otra vez.');
        }
    });
}

function toast(html) {
    if (typeof(M) != 'undefined') {
        M.toast({ html: html });
    } else {
        console.warn('Unable to display toast, there must be a slow connection, since the M class isn\'t available yet.\n\nhtml: ' + html);
    }
}

function redirect(url, timeout = null) {
    setTimeout(function () {
        if (url == window.location.href) {
            window.location.reload();
        } else {
            window.location.href = url;
        }
    }, timeout = null ? MATERIALIZE_TRANSITION_TIME : timeout);
}

function animateRedirect(url, fullBody = false, timeout = null) {
    setTimeout(function () {
        redirCall = () => {
            $(fullBody ? 'body' : 'main').fadeOut(MATERIALIZE_TRANSITION_TIME, () => {
                redirect(url, 0);
            });
        };

        if ($('.sidenav').length > 0) {
            $('.sidenav').sidenav('close');

            setTimeout(redirCall, M.Sidenav.getInstance($('.sidenav')).options.outDuration);
        } else {
            redirCall();
        }
    }, timeout = null ? MATERIALIZE_TRANSITION_TIME : timeout);
}

function disable(element) {
    element.attr('disabled', '');
}

function enable(element) {
    element.removeAttr('disabled');
}

function markValid(element) {
    element
        .addClass('valid')
        .removeClass('invalid');
}

function markInvalid(element) {
    element
        .addClass('invalid')
        .removeClass('valid');
}

function displayRemovableWarning(html) {
    $('#recentsContainer')
        .find('.removableWarning')
        .remove();

    $('#recentsContainer').prepend(
        `<div class="removableWarning center">
            <div class="section"></div>
            <span style="display: none;" class="showNow thin">
                ` + html +`
            </span>
            <br>
            <div class="section"></div>
            </div>`
    );

    $('.showNow')
        .removeClass('showNow')
        .fadeIn();
}

function queueRetry() {
    setTimeout(() => {
        $('.removableWarning').fadeOut(() => {
            $(window).trigger('load');
        });
    }, FAILURE_RETRY_INTERVAL);
}

function setupGoogleAnalytics() {
    window.dataLayer = window.dataLayer || [];

    function gtag() {
        dataLayer.push(arguments);
    }

    gtag('js', new Date());

    gtag('config', GOOGLE_ANALYTICS_KEY);

    console.info('setupGoogleAnalytics: success setting up Google Analytics.');
}

function deferLoginPreloader() {
    deferredPreloader = setTimeout(() => {
        $('#loginPreloader').fadeIn();
    }, FAILURE_RETRY_INTERVAL);
}

function stopPreloader() {
    if (deferredPreloader != null) {
        clearInterval(deferredPreloader);

        deferredPreloader = null;
    }

    $('#loginPreloader').fadeOut();
}

function isMailAddressValid(mailAddress) {
    return  mailAddress.length > 0
            &&
            mailAddress.includes('@')
            &&
            mailAddress.split('@').length > 1
            &&
            mailAddress.split('@')[1].split('.').length > 1
            &&
            mailAddress.split('@')[1].split('.')[1].length > 0;
}

function setupMaterializeImages() {
    if ($('.materialboxed').length > 0) {
        $('.materialboxed').materialbox({
            'onOpenStart' : (target) => {
                let message = $(target)
                                .parent()
                                .parent()
                                .parent()
                                .parent()
                                .data()
                                .message;

                                console.log(message);

                $('.message[data-message="' + message + '"]')
                    .find('img')
                    .addClass('was-unverified')
                    .removeClass('unverified-img');
            },
            'onCloseEnd'  : (target) => {
                let message = $(target)
                                .parent()
                                .parent()
                                .parent()
                                .parent()
                                .data()
                                .message;
                                console.log(message);

                let img = $('.message[data-message="' + message + '"]').find('img');

                if (img.hasClass('was-unverified')) {
                    img
                        .removeClass('was-unverified')
                        .addClass('unverified-img');
                }
            }
        });
    }
}

$(document).ready(function () {
    let isGoingTop = false;

    // Grab preloaded CSS.
    $('link[as="style"]').each(function () {
        $(this)
            .prop('rel', 'stylesheet')
            .removeAttr('as');
    });

    if (!$('body').is(':visible')) {
        $('body').fadeIn();
    }

    if ($('main').css('visibility') == 'hidden') {
        /* This is a workaround to prevent the tooltip 
         * from getting stuck at the top-left corner of 
         * the main element.
         * 
         * Trust me, there was no way around. 
         */

        $('main')
            .css({ 
                'display' : 'none', 
                'visibility' : 'visible'
            })
            .fadeIn();
    }

    $('a').each(function () {
        if ($(this).hasClass('hard-switch')) {
            $(this).on('click', function (event) {
                event.preventDefault();

                animateRedirect(
                    $(this).attr('href')
                );
            });
        }
    });

    $('.logOutBtn').on('click', function (event) {
        event.preventDefault();

        animateRedirect($(this).attr('href'), true);
    });

    $('[class^="btn-"]').each(function () {
        if ($(this).hasClass('pulse')) {
            let btn = $(this);

            btn.on('click', function () {
                btn.removeClass('pulse');
            });
        }
    });

    if (!window.location.href.includes('login') && HEARTBEAT_INTERVAL > -1) {
        let heartbeat = setInterval(function () {
            run('accountManager', 'areYouThere?', undefined, function () {}, true)
            .done(function (response) {
                console.log(response);

                switch (response.status) {
                    case NOT_ALLOWED:
                        toast('Por favor volvé a iniciar sesión.');

                        clearInterval(heartbeat);

                        animateRedirect('login.php', true, MATERIALIZE_TRANSITION_TIME * 9);

                        break;
                }
            })
            .fail(function (error) {
                console.error(error);

                toast('Estamos teniendo problemas para conectarnos, por favor esperá un rato.');
            });
        }, HEARTBEAT_INTERVAL);
    }

    $(window).on('scroll', () => {
        if ($('html, body').scrollTop() > document.documentElement.clientHeight) {
            if (!isGoingTop) {
                $('#backToTopBtn').fadeIn();
            }
        } else {
            $('#backToTopBtn').fadeOut();
        }

        // Prevent this error, it doesn't really matter.
        try {
            $('.tooltipped').tooltip('close');
        } catch (exception) {}
    });

    // toast(navigator.userAgent);

    // if (navigator.userAgent.includes('Android')) {
    //     toast('Hey there, it\'s an Android device!');
    //  }

    function goBackToTop() {
        if (!isGoingTop) {
            isGoingTop = true;

            $('#backToTopBtn').fadeOut();

            $('html, body').animate({ 'scrollTop' : 0 }, SCROLLTOP_DURATION, () => {
                isGoingTop = false;
            });
        }
    }

    $('#backToTopBtn').on('click', goBackToTop);

    $('.sidenav').sidenav();

    $(window).on('load', function () {
        console.log('common/window: success loading assets.');

        $('.custom-link').on('click', function (event) {
            event.preventDefault();

            animateRedirect(
                $(this).attr('href')
            );
        });

        $('input[type="text"], textarea').each(function () {
            if (typeof($(this).attr('data-length')) != 'undefined') {
                $(this).characterCounter();
            }
        });
    
        if ($('.tooltipped').length > 0) {
            $('.tooltipped').tooltip();

            $('.tooltip-content').addClass('thin');
        }
    
        $('.scrollspy').scrollSpy();

        M.updateTextFields();

        if ($('textarea').length > 0) {
            M.textareaAutoResize($('textarea'));
        }
    });

    $('#continueLoginBtn, #tryAccountRecoveryBtn').on('click', function (event) {
        loginPassword = $('#loginPassword');

        if (
            event.target.id == 'continueLoginBtn'
            &&
            loginPassword.parent().is(':visible')
        ) {
            if (loginPassword.val().length > 0) {
                deferLoginPreloader();

                if (typeof(grecaptcha) == 'undefined') {
                    toast('No podemos validar tu sesión, parece que hay problemas con tu conexión.');

                    return;
                }

                grecaptcha.ready(() => {
                    grecaptcha.execute(RECAPTCHA_PUBLIC_KEY, {action: 'submit'}).then((token) => {
                        run('accountManager', 'tryLogin', {
                            'mailAddress'   : $('#loginMailAddress').val(),
                            'password'      : $('#loginPassword').val(),
                            'token'         : token
                        }, () => {
                            disable($('#tryAccountRecoveryBtn, #continueLoginBtn, #loginMailAddress, #loginPassword'));
                        })
                        .done((response) => {
                            console.log(response);

                            switch (response.status) {
                                case SUSPICIOUS_OPERATION:
                                    toast('Necesitamos que resuelvas un desafío.');

                                    break;
                                case NO_SUCH_ELEMENT:
                                    toast('El correo electrónico especificado no existe.');

                                    break;
                                case NOT_ALLOWED:
                                    toast('La contraseña no es correcta.');

                                    break;
                                case OK:
                                    $('#loginMailAddress, #loginPassword')
                                        .removeClass('valid invalid')
                                        .val('');

                                    animateRedirect(window.location.href);

                                    break;
                            }
                        })
                        .always(() => {
                            enable($('#tryAccountRecoveryBtn, #continueLoginBtn, #loginMailAddress, #loginPassword'));

                            stopPreloader();
                        });
                    });
                });
            } else {
                markInvalid(loginPassword);

                stopPreloader();
            }
        } else {
            if ($('#loginMailAddress').hasClass('valid')) {
                deferLoginPreloader();

                run('accountManager', 'trySendLoginMail', {
                    'mailAddress'   : $('#loginMailAddress').val(),
                    'force'         : event.target.id == 'tryAccountRecoveryBtn'
                }, () => {
                    console.info('accountManager/trySendLoginMail: account creation/recovery started.');

                    disable($('#tryAccountRecoveryBtn, #continueLoginBtn, #loginMailAddress, #loginPassword'));
                })
                .done((response) => {
                    console.log(response);

                    switch (response.status) {
                        case BAD_REQUEST:
                            toast('Algo anda mal, probá otra vez.');

                            break;
                        case ALREADY_EXISTS:
                            loginPassword
                                .parent()
                                .slideDown(() => {
                                    loginPassword
                                        .focus()
                                        .click();
                                });

                            $('#continueLoginBtn').html('Iniciar sesión');
                            $('#tryAccountRecoveryBtn').fadeIn();

                            break;
                        case OK:
                            $('#loginForm').fadeOut(() => {
                                loginModal = $('#loginModal');
                
                                loginModal.animate(
                                    { bottom : -1 * loginModal.find('.modal-footer').outerHeight() },
                                    MATERIALIZE_TRANSITION_TIME,
                                    () => {
                                        loginStatus = $('#loginStatus');

                                        loginStatus
                                            .html('¡Listo! En un rato vas a recibir un mail para continuar.')
                                            .fadeIn(() => {
                                                setTimeout(() => {
                                                    loginModal.animate(
                                                        { bottom : -1 * loginModal.outerHeight() },
                                                        MATERIALIZE_TRANSITION_TIME,
                                                        () => { loginModal.modal('close'); }
                                                    )
                                                }, INFORMATION_MODAL_TIMEOUT);
                                            });
                                    }
                                );
                            });

                            break;
                        case ERROR:
                            toast('Hubo un problema, probá otra vez.');

                            break;
                    }
                    
                })
                .always(() => {
                    enable($('#tryAccountRecoveryBtn, #continueLoginBtn, #loginMailAddress, #loginPassword'));

                    $('#signupMailAddress').val('');

                    stopPreloader();
                });
            }
        }
    });

    $('#loginBtn, #loginBtnMobile').on('click', () => {
        $('.sidenav').sidenav('close');
        $('#loginModal').modal('open');
    });

    $('.modal').modal({
        onOpenStart: () => {
            $('.tooltipped').tooltip('close');
        }
    });

    $('#loginModal').modal({ 
        onCloseEnd: () => {
            $('#loginModal').css({ bottom : '' });
            $('#loginForm').show();
            $('#loginStatus').html('');

            loginMailAddress = $('#loginMailAddress');
            loginMailAddress.val('');
            loginMailAddress.removeClass('valid');

            loginPassword = $('#loginPassword');
            loginPassword.val('');
            loginPassword.removeClass('valid');
            loginPassword.parent().hide();

            $('#tryAccountRecoveryBtn').hide();
            $('#continueLoginBtn').html('Continuar');

            allowDeferredPreloader = true;
        }
    });

    $('#loginMailAddress, #loginPassword').on('keyup', (event) => {
        if (event.key == 'Enter') {
            $('#continueLoginBtn').click();
        }
    });

    $('#logoutBtn, #logoutBtnMobile').on('click', () => {
        run('accountManager', 'tryLogout', undefined, () => {
            $('.sidenav').sidenav('close');
        })
        .done((response) => {
            if (response.status == OK) {
                animateRedirect('index.php');
            }
        });
    });

    $('#loginMailAddress').on('keyup change', function () {
        value = $(this).val().trim();

        if (isMailAddressValid(value)) {
            markValid($(this));
        } else {
            markInvalid($(this));
        }
    });

    $('#loginPassword').on('keyup change', function () {
        if ($(this).val().length > 0) {
            markValid($(this));
        } else {
            markInvalid($(this));
        }
    });

    async function pushLoader() {
        var script = null;

        // Google reCaptcha v3
        script          = document.createElement('script');
        script.src      = 'https://www.google.com/recaptcha/api.js?render=' + RECAPTCHA_PUBLIC_KEY;
        script.onload   = () => {
            console.log('reCaptcha v3: successfully loaded.');

            loader();
        };

        script.onerror  = () => {
            toast('Algunos módulos no fueron cargados, si falla algo, intentá recargando la página.');
        }

        script.setAttribute('defer', true);
        script.setAttribute('async', true);
    
        document.getElementsByTagName('head')[0].appendChild(script);


        // Global site tag (gtag.js) - Google Analytics
        script          = document.createElement('script');
        script.src      = 'https://www.googletagmanager.com/gtag/js?id=' + GOOGLE_ANALYTICS_KEY;
        script.onload   = setupGoogleAnalytics;

        script.onerror  = () => {
            toast('Algunos módulos no fueron cargados, si falla algo, intentá recargando la página.');
        }

        script.setAttribute('defer', true);
        script.setAttribute('async', true);

        document.getElementsByTagName('body')[0].appendChild(script);
    }
    
    if (document.readyState != 'complete') {
        $(window).on('load', pushLoader);
    } else {
        pushLoader();
    }

    if (EXCEPTION != null) {
        switch (parseInt(EXCEPTION)) {
            case EXPIRED_TOKEN:
                toast('Ese código ya expiró, por favor pedí otro.');

                break;
            case QUICKSTART['INVALID_MAIL_ADDRESS']:
                toast('El mail que ingresaste no es válido.');

                break;
            case QUICKSTART['MAIL_CHANGE_OK']:
                toast('¡Listo! Ya cambiaste tu mail.');

                break;
        }
    }

    setupMaterializeImages();

    let nav = $('nav');

    nav.pushpin();
    $('main').css({ 'padding-top' : nav.height() });

    $('.brand-logo').on('click', () => {
        if (
            $(window).scrollTop() >= document.documentElement.clientHeight
            &&
            $('.sidenav-trigger').is(':visible')
        ) {
            goBackToTop();
        } else {
            animateRedirect('index.php');
        }
    });

    // Officially proposed fix, read https://github.com/jquery/jquery/issues/2871.
    jQuery.event.special.touchstart = {
        setup: function( _, ns, handle ){
            this.addEventListener('touchstart, wheel, scroll', handle, { passive: true });
        }
    };
});