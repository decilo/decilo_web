let grid = null;

let createPostBtn       = null;
let imageInput          = null;
let maxScrollTop        = null;
let isPullingChunks     = false;

let toReport = null;

function reloadLayout(toAppend = null) {
    if (typeof(Masonry != 'undefined')) {
        if (toAppend == null) {
            console.info('reloadLayout: grid initialization started.');

            grid = new Masonry(
                '#recentsContainer',
                {
                    itemSelector: '.col',
                    containerStyle: {
                        'position' : 'relative',
                        'padding-top' : '1em'
                    },
                    transitionDuration: 0
                }
            );
        } else {
            console.info('reloadLayout: reloading newly added items.');

            if (toAppend != null) {
                grid.appended($(toAppend));
            }

            grid.reloadItems();
            grid.layout();

            $('.tooltipped').tooltip();
        }
    } else {
        console.warn('Cannot update layout, Masonry isn\'t ready.');
    }
}

function resetMessageInputs() {
    $('#messageInput, #declaredName')
        .removeClass('valid')
        .val('');

    $('label[for="imageInput"]')
        .removeClass('green')
        .addClass('bg-dark-1');

    $('label[for="imageInput"]')
        .find('.material-icons')
        .html('add_a_photo');

    enable($('#messageInput, #declaredName, #createPostBtn, label[for="imageInput"]'));
}

function getLastMessageId() {
    return  $('.message')
                .last()
                .data('message');
}

function getRenderedMessage(id, content, declaredName, created = null, display = false, reported, image = null, verified = true) {
    auxiliaryContent = content;

    content.split('http').forEach((match) => {
        match
            .split(' ')
            .forEach((item) => {    
                if (item.indexOf('://') > -1) {
                    auxiliaryContent = auxiliaryContent.replace('http' + item, '<a target="_blank" href="http' + item + '">http' + item + '</a>');
                }
        });
    });

    return `<div class="col s12 m12 l6 message" ` + (display && image == null ? '' : 'style="display: none;"') + ` data-message="` + id + `">
                <div class="card bg-dark-3 card-box">` + (LOGGED_IN ? `
                    <button
                        type="button"
                        class="btn-floating halfway-fab mid-card-fab waves-effect waves-light ` + (reported ? 'grey' : 'red') + ` tooltipped"
                        data-position="left"` + (reported ? `
                        data-tooltip="Ya lo reportaste"` : `
                        data-tooltip="Reportar"
                        onclick="reportMessage(` + id + `);"`) + `
                    >
                        <i class="material-icons mid-card-fab-icon">flag</i>
                    </button>` : ``) + (image == null ? '' : `
                    <div class="card-image">
                        <img
                            class="materialboxed ` + (verified ? '' : 'unverified-img') + `"
                            alt="Imagen adjunta"
                            src="` + image + `"
                            onload="
                                $(this)
                                    .parent()
                                    .parent()
                                    .parent()
                                    .fadeIn(() => {
                                        if ($('.message:visible').length == $('.message').length) {
                                            resetMessageInputs();

                                            setupMaterializeImages();
                                        }
                                    });

                                reloadLayout();
                            "
                            onerror="
                                $(this)
                                    .parent()
                                    .hide();

                                $(this)
                                    .parent()
                                    .parent()
                                    .parent()
                                    .fadeIn(() => {
                                        if ($('.message:visible').length == $('.message').length) {
                                            resetMessageInputs();
                                        }
                                    });
                                    reloadLayout();
                            "
                        >
                    </div>`) + `
                    <div class="card-content white-text">
                        <span class="card-title roboto">` + (declaredName == null ? 'Anónimo' : declaredName) + `</span>
                        <p class="lato word-wrap process-whitespaces overflow-ellipsis">` + 
                            (auxiliaryContent.length > MESSAGES['MAX_LENGTH'] ? auxiliaryContent.substr(0, MESSAGES['MAX_LENGTH']) + '…' : auxiliaryContent) + `
                        </p>
                        <div class="message-spacer"></div>
                        <a
                            class="custom-link thin small"
                            href="view.php?message=` + id + (RECIPIENT == null ? '' : '&private=true') + `"
                        >
                            Ver más
                        </a>` + (verified || image == null ? `` : `
                        <div class="message-spacer"></div>
                        <p class="red-text thin small">* Verificación pendiente</p>`) + `
                    </div>
                    <div class="card-action center">
                        <span class="lato thin small">` + dayjs(created == null ? new Date() : created).format('L LT') + `</span>
                    </div>
                </div>
             </div>`;
}

function reportMessage(id) {
    toReport = id;

    $('#reportMessageModal').modal('open');
}

function postMessage(messageContent, declaredName, token, image = null) {
    run('messagesManager', 'postMessage', {
        'content'       : messageContent,
        'declaredName'  : declaredName,
        'recipient'     : RECIPIENT,
        'image'         : image,
        'token'         : token
    }, () => {
        disable($('#messageInput, #declaredName, #createPostBtn, label[for="imageInput"]'));
    })
    .done(function (response) {
        console.log(response);

        switch (response.status) {
            case SUSPICIOUS_OPERATION:
                toast('Necesitamos que resuelvas un desafío.');

                break;
            case OK:
                callable = () => {
                    $('#recentsContainer')
                        .find('.row')
                        .prepend(
                            getRenderedMessage(
                                response.result.id, messageContent, declaredName, null, false, false, response.result.image, false
                            )
                        );

                    if (response.result.image == null) {
                        $('.message')
                            .first()
                            .fadeIn();

                        $('#messageInput, #declaredName, #messageInput, #declaredName')
                            .removeClass('valid')
                            .val('');
            
                        reloadLayout();
                    }
                }

                if ($('#recentsContainer').find('.removableWarning').length > 0) {
                    $('#recentsContainer')
                        .find('.removableWarning')
                        .fadeOut(callable);
                } else {
                    callable();
                }
                break;
            case ERROR:
                toast('Algo anda mal, probá otra vez.');

                break;
        }
    })
    .always(() => {
        enable($('#messageInput, #declaredName, #createPostBtn, label[for="imageInput"]'));
    });
}

$(document).ready(function () {
    createPostBtn   = $('#createPostBtn');
    imageInput      = $('#imageInput');
    
    setupMaterializeImages();

    $(window).on('scroll', function () {
        if (
            $('.message').length > 0
            &&
            (
                $(window).scrollTop() > (
                    $('.message').last().offset()['top']
                    -
                    (
                        (SCROLLTOP_THRESHOLD * (document.documentElement.scrollHeight - document.documentElement.clientHeight)) / 100
                    )
                )
                ||
                $(window).scrollTop() == document.documentElement.scrollHeight - document.documentElement.clientHeight
            )
            &&
            !isPullingChunks
        ) {
            run('messagesManager', 'getRecent', {
                after:      getLastMessageId(),
                recipient:  RECIPIENT
            }, () => { isPullingChunks = true; })
            .done(function (response) {
                console.log(response);

                if (response.result.length > 0) {
                    let renderedHTML = '';
                    
                    response.result.forEach((message) => {
                        renderedHTML += getRenderedMessage(
                            message['id'], message['content'], message['declaredName'], message['created'], true, parseInt(message['reported']) == 1, message['image'], parseInt(message['verified']) == 1
                        );
                    });

                    $('#recentsContainer')
                        .find('.row')
                        .append(renderedHTML);

                    reloadLayout(renderedHTML);
                } else {
                    isPullingChunks = true;

                    return;
                }

                isPullingChunks = false;
            })
            .fail(function (error) {
                isPullingChunks = false;

                console.error(error);
            });
        }
    });

    $('#messageInput, #declaredName').on('keyup change', function (event) {
        if (event.ctrlKey && event.key == 'Enter') {
            $('#createPostBtn').click();
        } else {
            length = $(this).val().trim().length;

            if (length > 0) {
                if (
                    typeof($(this).attr('data-length')) == 'undefined'
                    ||
                    length <= parseInt($(this).attr('data-length'))
                ) {
                    markValid($(this));
                } else {
                    $(this)
                        .parent()
                        .find('.helper-text')
                        .attr('data-error', 'El mensaje es muy largo');

                    markInvalid($(this));
                }
            } else {
                markInvalid($(this));

                $(this)
                    .parent()
                    .find('.helper-text')
                    .attr('data-error', 'Tenés que escribir un mensaje');
            }
        }
    });

    $('#createPostBtn').on('click', function () {
        let messageInput    = $('#messageInput');
        let declaredName    = $('#declaredName').val();
            declaredName    = declaredName.length > 0 ? declaredName : null;

        let messageContent  = $('<span>' + messageInput.val().trim() + '</span>').text();

        if (messageContent.length > 65535) {
            markInvalid(messageInput);
            messageInput
                .parent()
                .find('.helper-text')
                .attr('data-error', 'El mensaje es muy largo');

            return;
        } else {
            if (messageContent.length > 0) {
                markValid(messageInput);
            } else {
                markInvalid(messageInput);

                messageInput
                    .parent()
                    .find('.helper-text')
                    .attr('data-error', 'Tenés que escribir un mensaje');
                
                return;
            }
        }

        if (typeof(grecaptcha) == 'undefined') {
            toast('No podemos validar tu sesión, parece que hay problemas con tu conexión.');

            return;
        }

        grecaptcha.ready(() => {
            grecaptcha.execute(RECAPTCHA_PUBLIC_KEY, {action: 'submit'}).then((token) => {
                if (
                    $('#imageInput')[0].files.length > 0
                    &&
                    $('#imageInput')[0].files[0].type.includes('image')
                ) {
                    var reader  = new FileReader();

                    reader.onloadend = () => {
                        postMessage(messageContent, declaredName, token, reader.result);
                    }

                    reader.readAsDataURL(
                        $('#imageInput')[0].files[0]
                    );
                } else {
                    postMessage(messageContent, declaredName, token);
                }
            });
        });
    });

    let idleRunner = null;
    function initializeIdleRunner() {
        if (idleRunner != null) {
            clearInterval(idleRunner);

            idleRunner = null;
        }

        idleRunner = setTimeout(() => {

            localStorage.setItem('hasSeenFeatures', true);

            if ($('.sidenav-trigger').is(':visible')) {
                $('.sidenav').sidenav('open');

                $('#loginBtnMobile').addClass('pulse bg-dark-1');

                toast('Recibí mensajes, reportá y mucho más.');
            } else {
                if ($(window).scrollTop() > 0) {
                    $('html, body').animate({ 'scrollTop' : 0 }, SCROLLTOP_DURATION, () => {
                        $('.tap-target[data-target="loginBtn"]').tapTarget('open');
                    });
                }
            }
        }, IDLE_TIMEOUT);
    }

    function hasSeenFeatures() {
        return localStorage.getItem('hasSeenFeatures') != null;
    }

    if (!hasSeenFeatures()) {
        console.info('FeatureDiscovery will fire in ' + (IDLE_TIMEOUT / 1000) + ' seconds, since we don\'t know if this is a new user.');

        $(window).on('scroll click keyup keydown keypress change wheel', function () {
            if (!hasSeenFeatures()) {
                initializeIdleRunner();
            }
        });

        initializeIdleRunner();
    }

    $('#messageInput').focus();

    console.info('document: success fetching critical assets.');

    loader = () => {
        console.info('index/window: success loading assets.');

        enable(createPostBtn);

        resetMessageInputs();

        $('#imageInput').on('change', function () {
            let files = $(this)[0].files;

            if (files.length > 0) {
                if (files[0].type.includes('image')) {
                    $('label[for="imageInput"]')
                        .removeClass('bg-dark-1')
                        .addClass('green');

                    $('label[for="imageInput"]')
                        .find('.material-icons')
                        .html('check');

                    toast('Listo, agregaste ' + files[0].name + '.');
                } else {
                    resetMessageInputs();

                    toast('El archivo seleccionado no es una imagen');
                }
            } else {
                $('label[for="imageInput"]')
                    .removeClass('green')
                    .addClass('bg-dark-1');

                $('label[for="imageInput"]')
                    .find('.material-icons')
                    .html('add_a_photo');
            }
        });

        $('.tap-target').tapTarget({
            onOpen: () => {
                $('.tap-target-origin').addClass('black-text');
            }
        });

        $('#reportMessageModal').modal({
            onCloseEnd: () => {
                $('input[name="reportReason"]').prop('checked', false);
            }
        });

        $('#sendReportBtn').on('click', () => {
            let markedRadio = $('input[name="reportReason"]:checked');

            if (markedRadio.length > 0) {
                run('messagesManager', 'reportMessage', {
                    id      : toReport,
                    reason  : parseInt(markedRadio.val()),
                    private : RECIPIENT != null
                }, () => {
                    disable($('input[name="reportReason"], #sendReportBtn'));
                })
                .done((response) => {
                    console.log(response);

                    switch (response.status) {
                        case OK:
                            toast('¡Gracias! Ya recibimos tu reporte.');

                            $('.message[data-message=' + toReport + ']')
                                .find('.tooltipped')
                                .removeClass('red')
                                .addClass('grey')
                                .attr('data-tooltip', 'Ya lo reportaste')
                                .removeAttr('onclick');

                            break;
                        case ALREADY_EXISTS:
                            toast('Ya reportaste este mensaje.');

                            break;
                        case NOT_ALLOWED:
                            toast('No podés reportar mensajes, probá recargando la página.');

                            break;
                    }
                })
                .always(() => {
                    enable($('input[name="reportReason"], #sendReportBtn'));

                    $('#reportMessageModal').modal('close');
                });
            } else {
                toast('Para enviar tu reporte, seleccioná una opción.');
            }
        });
    };

    // Day.js loader
    let timeParsers = [
        'https://unpkg.com/dayjs@1.8.21/dayjs.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.8.35/plugin/localizedFormat.min.js'
    ];

    let loaded = 0;

    let postScript = null;

    timeParsers.forEach((target) => {
        postScript          = document.createElement('script');
        postScript.src      = target;
        postScript.onload   = () => {
            loaded++;

            if (loaded == timeParsers.length) {
                loaded = 0;

                timeParsers = [
                    'https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.8.35/locale/es.min.js',
                    'https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.8.35/locale/en.min.js'
                ];

                timeParsers.forEach((target) => {
                    loaded++;

                    if (loaded == timeParsers.length) {
                        postScript          = document.createElement('script');
                        postScript.src      = target;
                        postScript.onload   = () => {
                            dayjs.extend(dayjs_plugin_localizedFormat);
                        
                            dayjs.locale(
                                (window.navigator.userLanguage || window.navigator.language).split('-')[0]
                            );
                    
                            if (typeof(dayjs.locale()) == 'undefined') {
                                dayjs.locale('en'); // Fallback to English.
                            }
    
                            loadPreloadedRecents();
                        }

                        postScript.setAttribute('defer', true);
                        postScript.setAttribute('async', true);
        
                        document.getElementsByTagName('body')[0].appendChild(postScript);
                    }
                });
            }
        }

        postScript.onerror = () => {
            toast('Algunos módulos no fueron cargados, si falla algo, intentá recargando la página.');
        }

        postScript.setAttribute('defer', true);
        postScript.setAttribute('async', true);

        document.getElementsByTagName('body')[0].appendChild(postScript);
    });

    function loadPreloadedRecents() {
        if (RECENTS.length > 0) {
            RECENTS.forEach((message) => {
                $('#recentsContainer')
                    .find('.row')
                    .append(
                        getRenderedMessage(
                            message['id'],
                            message['content'],
                            message['declaredName'],
                            message['created'],
                            true,
                            parseInt(message['reported']) == 1,
                            message['image'],
                            parseInt(message['verified']) == 1
                        )
                    );
            });

            reloadLayout();
        }
    }
});