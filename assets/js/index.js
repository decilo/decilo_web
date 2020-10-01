let grid = null;

let createPostBtn       = null;
let imageInput          = null;
let maxScrollTop        = null;
let isPullingChunks     = false;
let isPosting           = false;

let toReport            = null;

let fab                 = null;

let deferredFetcher     = null;

const LOADS_RECAPTCHA   = true;

function reloadLayout(toAppend = null) {
    if (typeof(Masonry) != 'undefined') {
        if (toAppend == null || grid == null) {
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

    $('.tooltipped').tooltip();
    $('.tooltipped').tooltip('close');

    $('#removeFileBtn').animate({ 'right' : '-3.4em' }, () => {
        $('#removeFileBtn').fadeOut();
    });

    $('#imageInput').val(null);

    enable($('#messageInput, #declaredName, label[for="imageInput"]'));
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
                    auxiliaryContent = auxiliaryContent.replace('http' + item, '<a target="_blank" href="http' + item + '" rel="noreferrer">http' + item + '</a>');
                }
        });
    });

    return `<div class="col s12 m6 l3 message ` + (id == null ? 'not-posted' : '') + `" ` + (display ? '' : 'style="display: none;"') + ` data-message="` + id + `">
                <div class="card bg-dark-3 card-box">` + (LOGGED_IN && id != null ? `
                    <button
                        type="button"
                        class="` + (reported ? 'grey' : 'red') + ` btn-floating halfway-fab mid-card-fab waves-effect waves-light tooltipped"
                        data-position="left"` + (reported ? `
                        data-tooltip="Ya lo reportaste"` : `
                        data-tooltip="Reportar"
                        onclick="reportMessage(` + id + `);"`) + `
                    >
                        <i class="material-icons mid-card-fab-icon">flag</i>
                    </button>` : ``) + (image == null ? '' : `
                    <div class="card-image" style="display: none;">
                        <img
                            class="` + (id == null ? '' : 'materialboxed') + ' ' + (verified ? '' : 'unverified-img') + `"
                            alt="Imagen adjunta"
                            data-src="` + image + `"
                            onload="
                                if ($(this).parent().css('display') == 'none') {
                                    $(this)
                                        .parent()
                                        .fadeIn(() => {
                                            setupMaterializeImages();
                                        });
                                } else {
                                    $(this)
                                        .parent()
                                        .parent()
                                        .fadeIn(() => {
                                            setupMaterializeImages();
                                        });
                                }

                                reloadLayout();
                            "
                            onerror="
                                $(this)
                                    .parent()
                                    .parent()
                                    .hide();

                                reloadLayout();
                            "
                        >
                    </div>`) + `
                    <div class="card-content white-text">
                        <span class="card-title roboto">` + (declaredName == null ? 'Anónimo' : declaredName) + `</span>
                        <p class="lato word-wrap process-whitespaces overflow-ellipsis message-content">` + 
                            (auxiliaryContent.length > MESSAGES['MAX_LENGTH'] ? auxiliaryContent.substr(0, MESSAGES['MAX_LENGTH']) + '…' : auxiliaryContent) + `
                        </p>
                        <div class="message-spacer"></div>
                        <a
                            class="custom-link thin small"
                            href="` + (id == null ? '#' : `view.php?message=` + id + (RECIPIENT == null ? '' : '&private=true')) + `"
                        >
                            Ver más
                        </a>` + (verified || image == null ? `` : `
                        <div class="message-spacer"></div>
                        <p class="red-text thin small">* Verificación pendiente</p>`) + `
                    </div>
                    <div class="card-action center">
                        <span class="lato thin small">` + dayjs(created == null ? new Date() : created).format('L LT') + `</span>
                    </div>` +
                    // <ul class="collection with-header bg-dark-7 border-dark-7 hand">
                    //    <li class="collection-header bg-dark-7 border-dark-7 no-select" ` + (id == null ? '' : `onclick="openCommentsModal(` + id + `, false);"`) + `> Comentarios (0) </li>
                    // </ul>
                    `
                </div>
             </div>`;
}

function reportMessage(id) {
    toReport = id;

    $('#reportMessageModal').modal('open');
}

function abortPost() {
    $('.message[data-message="null"]').fadeOut(() => {
        $('.message[data-message="null"]').remove();

        reloadLayout();
    });
}

function postMessage(messageContent, declaredName, token, image = null) {
    let createMessageModal = M.Modal.getInstance($('#createMessageModal'));

    if (!isPosting) {
        isPosting = true;

        $('#removeFileBtn').animate({ 'right' : '-3.4em' }, () => {
            $('#removeFileBtn').fadeOut();
        });

        let previousHtml = createPostBtn.html();

        let postProgressBar = $('#postProgressBar');

        postProgressBar
            .width(0)
            .show();

        let postingAnimation = setInterval(() => {
            if (postProgressBar.width() < createPostBtn.outerWidth()) {
                postProgressBar.css({ 'width' : postProgressBar.width() + 1 });
            } else {
                clearInterval(postingAnimation);

                postingAnimation = null;
            }
        }, PROGRESSBAR_TRIGGER_INTERVAL);

        run('messagesManager', 'postMessage', {
            'content'       : messageContent,
            'declaredName'  : declaredName,
            'recipient'     : RECIPIENT,
            'image'         : image,
            'token'         : token
        }, () => {
            disable($('#messageInput, #declaredName, label[for="imageInput"]'));
            
            createPostBtn
                .removeClass('waves-effect waves-light')
                .html('Publicando');

            $('#recentsContainer')
                .find('.row')
                .prepend(
                    getRenderedMessage(
                        null, messageContent, declaredName, null, false, false, image, false
                    )
                );

            if (image == null) {
                $('.message')
                    .first()
                    .fadeIn();

                $('#messageInput, #declaredName, #messageInput, #declaredName')
                    .removeClass('valid')
                    .val('');

                reloadLayout();
            }
        })
        .done(function (response) {
            console.log(response);

            switch (response.status) {
                case SUSPICIOUS_OPERATION:
                    toast('Necesitamos que resuelvas un desafío.');

                    abortPost();

                    break;
                case OK:
                    if ($('#recentsContainer').find('.removableWarning').length > 0) {
                        $('#recentsContainer')
                            .find('.removableWarning')
                            .fadeOut();
                    }

                    $('.message[data-message="null"]').replaceWith(
                        getRenderedMessage(
                            response.result.id, messageContent, declaredName, null, false, false, image, false
                        )
                    );

                    isGoingTop = true;
                    $('html, body').animate({
                        'scrollTop' : $('.message[data-message="' + response.result.id + '"]').position()['top']
                    }, SCROLLTOP_DURATION, () => {
                        isGoingTop = false;
                    });

                    if (image == null) {
                        $('.message')
                            .first()
                            .fadeIn();

                        $('#messageInput, #declaredName, #messageInput, #declaredName')
                            .removeClass('valid')
                            .val('');
            
                        reloadLayout();
                    }

                    createMessageModal.close();

                    break;
                case ERROR:
                    toast('Algo anda mal, probá otra vez.');

                    abortPost();

                    break;
            }
        })
        .fail(abortPost)
        .always(() => {
            createPostBtn
                .addClass('waves-effect waves-light')
                .html(previousHtml);

            if (postingAnimation == null) {
                clearInterval(postingAnimation);
            }

            postProgressBar.animate({ 'width' : createPostBtn.outerWidth() }, () => {
                setTimeout(() => {
                    isPosting = false;

                    enable($('#messageInput, #declaredName, label[for="imageInput"]'));

                    postProgressBar.fadeOut();
                    
                    createMessageModal.options.dismissible = true;
                }, MATERIALIZE_TRANSITION_TIME);
            });
        });
    }
}

function attachProgressBar() {
    $('#postProgressBar').css({
        'top'    : createPostBtn.position()['top'],
        'left'   : createPostBtn.position()['left'],
        'height' : createPostBtn.height()
    });
}

$(document).ready(function () {
    createPostBtn   = $('#createPostBtn');
    imageInput      = $('#imageInput');
    
    setupMaterializeImages();

    $('#createMessageModal').modal({
        onOpenEnd: () => {
            $('#messageInput')
                .focus()
                .click();

            attachProgressBar();
        }
    });

    let fabDOM = $('.fixed-action-btn');

    fabDOM.floatingActionButton({ hoverEnabled: false });

    fab = M.FloatingActionButton.getInstance($('.fixed-action-btn'));

    fabDOM.on('click', () => {
        if (fab.isOpen) {
            fabDOM
                .find('.btn-large')
                .find('i')
                .css({ 'transform' : 'rotate(0deg)' });
        } else {
            fabDOM
                .find('.btn-large')
                .find('i')
                .css({ 'transform' : 'rotate(90deg)' });
        }

        if (typeof(grecaptcha) == 'undefined') {
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
        
            document.getElementsByTagName('head')[0].appendChild(script);
        }
    });

    fabDOM
        .find('#createMessageBtn')
        .on('click', () => {
            $('#createMessageModal').modal('open');
        });

    function tryToPullChunks() {
        if (isPullingChunks) {
            console.info('tryToPullChunks: cannot pull now, there\'s a pending request.');
        } else {
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

                    if (
                        $('.message').last().position()['top'] > document.documentElement.clientHeight
                        &&
                        deferredFetcher != null
                    ) {
                        clearInterval(deferredFetcher);

                        deferredFetcher = null;
                    }

                    console.info('tryToPullChunks: successfully pulled ' + response.result.length + ' chunks.');
                } else {
                    isPullingChunks = true;

                    console.info('tryToPullChunks: nothing to pull, shutting down...');

                    return;
                }


                isPullingChunks = false;
            })
            .fail(function (error) {
                isPullingChunks = false;

                console.error(error);
            });
        }
    }

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
        ) {
            tryToPullChunks();
        }

        $('.message').each(function () {
            if (isElementInViewport(this)) {
                img = $(this).find('img');

                if (img.length > 0 && typeof(img.attr('src')) == 'undefined') {
                    img.attr('src', img.data()['src']);
                }
            }
        });
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

        disable($('#messageInput, #declaredName, label[for="imageInput"]'));

        M.Modal
            .getInstance($('#createMessageModal'))
            .options.dismissible = false;

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
                $('.tap-target[data-target="loginBtn"]').tapTarget('open');
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

    console.info('document: success fetching critical assets.');

    loader = () => {
        console.info('index/window: success loading assets.');

        enable(createPostBtn);

        resetMessageInputs();

        attachProgressBar();

        $(window).on('resize', attachProgressBar);

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

                    $('#removeFileBtn')
                        .css({ 'display' : 'block' })
                        .animate({ 'right' : '-0.3em' });

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

        $('#removeFileBtn').on('click', function () {
            $('.tooltipped').tooltip('close');

            $(this).animate({ 'right' : '-3.4em' }, () => {
                $(this).fadeOut();
            });

            $('label[for="imageInput"]')
                .removeClass('green')
                .addClass('bg-dark-1');

            $('label[for="imageInput"]')
                .find('.material-icons')
                .html('add_a_photo');

            $('#imageInput').val(null);
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
            let renderedHTML = '';

            RECENTS.forEach((message) => {
                renderedHTML += getRenderedMessage(
                    message['id'],
                    message['content'],
                    message['declaredName'],
                    message['created'],
                    true,
                    parseInt(message['reported']) == 1,
                    message['image'],
                    parseInt(message['verified']) == 1
                );
            });
            
            $('#recentsContainer')
                .find('.row')
                .append(renderedHTML);

            reloadLayout(renderedHTML);

            if ($('.message').last().position()['top'] < document.documentElement.clientHeight) {
                deferredFetcher = setInterval(tryToPullChunks, 1000);
            }

            $('.message').each(function () {
                if (isElementInViewport(this)) {
                    img = $(this).find('img');

                    if (img.length > 0 && typeof(img.attr('src')) == 'undefined') {
                        img.attr('src', img.data()['src']);
                    }
                }
            });

            reloadLayout();
        }
    }
});