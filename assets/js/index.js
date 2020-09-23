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

            grid.appended($(toAppend));
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

    enable($('label[for="imageInput"]'));
}

function getLastMessageId() {
    return  $('.message')
                .last()
                .data('message');
}

function getRenderedMessage(id, content, declaredName, created = null, display = false, reported, image = null) {
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

    return `<div class="col s12 m12 l6 message" ` + (display ? '' : 'style="display: none;"') + ` data-message="` + id + `">
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
                            src="` + image + `"
                            onload="
                                $(this)
                                    .parent()
                                    .parent()
                                    .parent()
                                    .fadeIn();

                                reloadLayout();
                                
                                resetMessageInputs();
                            "
                            onerror="
                                $(this)
                                    .parent()
                                    .hide();

                                $(this)
                                    .parent()
                                    .parent()
                                    .parent()
                                    .fadeIn();

                                reloadLayout();
                                
                                resetMessageInputs();
                            "
                        >
                    </div>`) + `
                    <div class="card-content white-text">
                        <span class="card-title roboto">` + (declaredName == null ? 'Anónimo' : declaredName) + `</span>
                        <p class="lato word-wrap process-whitespaces overflow-ellipsis">` + auxiliaryContent + `</p>
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
        disable($('#createPostBtn, label[for="imageInput"]'));
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
                                response.result.id, messageContent, declaredName, null, false, false, response.result.image
                            )
                        );

                    if (response.result.image == null) {
                        $('.message')
                            .first()
                            .fadeIn();

                        $('#messageInput, #declaredName')
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
        enable($('#createPostBtn, label[for="imageInput"]'));
    });
}

$(document).ready(function () {
    createPostBtn   = $('#createPostBtn');
    imageInput      = $('#imageInput');

    $(window).on('scroll', function () {
        if (
            $('.message').length > 0
            &&
            $(window).scrollTop() > (
                $('.message').last().offset()['top']
                -
                (
                    (SCROLLTOP_TRESHOLD * (document.documentElement.scrollHeight - document.documentElement.clientHeight)) / 100
                )
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
                            message['id'], message['content'], message['declaredName'], message['created'], true, message['reported'] == 1
                        );
                    });

                    $('#recentsContainer')
                        .find('.row')
                        .append(renderedHTML);

                    reloadLayout(renderedHTML);
                } else {
                    $(window).off('scroll');
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
            $('.tap-target').tapTarget('open');

            localStorage.setItem('hasSeenFeatures', true);

            if ($(window).scrollTop() > 0) {
                $('html, body').animate({ 'scrollTop' : 0 }, SCROLLTOP_DURATION);
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

        $('#preloader').fadeIn(() => {
            run('messagesManager', 'getRecent', { recipient: RECIPIENT }, () => {}, true)
            .done(function (response) {
                console.info(response);

                switch (response.status) {
                    case OK:
                        $('#preloader').fadeOut(() => {
                            if (response.result.length > 0) {
                                let renderedHTML = '';

                                response.result.forEach((message) => {
                                    renderedHTML += getRenderedMessage(
                                        message['id'],
                                        message['content'],
                                        message['declaredName'],
                                        typeof(message['created']) == 'undefined' ? null : message['created'],
                                        false,
                                        message['reported'] == 1,
                                        message['image']
                                    );
                                });

                                $('#preloader')
                                    .next()
                                    .append(renderedHTML);

                                $('.message').each(function () {
                                    if ($(this).find('img').length < 1) {
                                        $(this).fadeIn();
                                    }
                                });

                                reloadLayout();
                            } else {
                                displayRemovableWarning(
                                    `¡Nada por acá!
                                     <br>
                                     <br>
                                     Dejá un mensaje y empezá a conversar.`
                                );
                            }

                            $('.tooltipped').tooltip();
                        });

                        break;
                    default:
                        displayRemovableWarning(
                            `Estamos teniendo problemas para obtener los últimos mensajes.
                             <br>
                             <br>
                             Por favor esperá un rato, vamos a seguir intentándolo.`
                        );

                        queueRetry();

                        break;
                }
            })
            .fail(function (error) {
                console.error(error);

                displayRemovableWarning(
                    `Estamos teniendo problemas para obtener los últimos mensajes.
                     <br>
                     <br>
                     Por favor esperá un rato, vamos a seguir intentándolo.`
                );

                queueRetry();
            })
            .always(() => {})
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
});