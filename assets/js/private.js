let grid = null;

let createPostBtn       = null;
let maxScrollTop        = null;
let isPullingChunks     = false;

let toRemove = null;

function requestRemoval(id) {
    toRemove = id;

    $('#requestRemovalModal').modal('open');
}

function tryToRemove() {
    if (toRemove == null) {
        toast('Tenés que seleccionar un mensaje a eliminar');

        console.warn('tryToRemove: I was called but "toRemove" was null, there\'s either a severe problem or it\'s just that the user is having fun with the console.');
    } else {
        run('messagesManager', 'tryToRemove', { id: toRemove }, () => {
            disable($('#requestRemovalModal').find('button'));
        })
        .done((response) => {
            console.info(response);

            switch (response.status) {
                case NOT_ALLOWED:
                    toast('No tenés permitido hacer eso, probá recargando la página.');

                    break;
                case BAD_REQUEST:
                    toast('El servidor no entendió la solicitud, probá recargando la página.');

                    break;
                case ERROR:
                    toast('Algo salió mal, probá recargando la página.');

                    break;
                case OK:
                    $('#requestRemovalModal').modal('close');

                    $('.message[data-message="' + toRemove + '"]').fadeOut(() => {
                        $('.message[data-message="' + toRemove + '"]').remove();

                        toRemove = null;

                        toast('¡Eliminado!');

                        if ($('.message:visible').length < 1) {
                            displayRemovableWarning(NO_MESSAGES_HINT);
                        } else {
                            reloadLayout();
                        }
                    });

                    break;
            }
        })
        .always(() => {
            enable($('#requestRemovalModal').find('button'));
        });
    }
}

function reloadLayout(toAppend = null) {
    if (typeof(Masonry) != 'undefined') {
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

function getLastMessageId() {
    return  $('.message')
                .last()
                .data('message');
}

function getRenderedMessage(id, content, declaredName, created = null, display = false, image = null) {
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

    return `<div class="col s12 m12 l6 message" ` + (display ? '' : 'style="display: none;"') + ` data-message="` + id + `">
                <div class="card bg-dark-3 card-box">
                    <button
                        type="button"
                        class="btn-floating halfway-fab mid-card-fab waves-effect waves-light red"
                        onclick="requestRemoval(` + id + `);"
                    >
                        <i class="material-icons mid-card-fab-icon">delete</i>
                    </button>` + (image == null ? '' : `
                    <div class="card-image">
                        <img
                            alt="Imagen adjunta"
                            src="` + image + `"
                            onload="
                                $(this)
                                    .parent()
                                    .parent()
                                    .parent()
                                    .fadeIn(() => {
                                        setupMaterializeImages();
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
                                    .fadeIn();

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
                            href="view.php?message=` + id + `&private=true"
                        >
                            Ver más
                        </a>
                    </div>
                    <div class="card-action center">
                        <span class="lato thin small">` + dayjs(created == null ? new Date() : created).format('L LT') + `</span>
                    </div>
                </div>
            </div>`;
}

$(document).ready(function () {
    createPostBtn = $('#createPostBtn');

    document.addEventListener('scroll', () => {
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
            run('messagesManager', 'getRecent', { private: true, after: getLastMessageId() }, () => { isPullingChunks = true; })
            .done(function (response) {
                console.log(response);

                if (response.result.length > 0) {
                    let renderedHTML = '';
                    
                    response.result.forEach((message) => {
                        renderedHTML += getRenderedMessage(
                            message['id'], message['content'], message['declaredName'], message['created'], true, message['image']
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
    }, { passive: true });

    $('#messageInput, #declaredName').on('keyup change', function (event) {
        if (event.ctrlKey && event.key == 'Enter') {
            $('#createPostBtn').click();
        } else {
            if ($(this).val().trim().length > 0) {
                markValid($(this));
            } else {
                markInvalid($(this));
            }
        }
    });

    $('#createPostBtn').on('click', function () {
        let messageInput    = $('#messageInput');
        let declaredName    = $('#declaredName').val();
            declaredName    = declaredName.length > 0 ? declaredName : null;

        let messageContent  = $('<span>' + messageInput.val().trim() + '</span>').text();

        if (messageContent.length > 0) {
            markValid(messageInput);
        } else {
            markInvalid(messageInput);
            
            return;
        }

        run('messagesManager', 'postMessage', {
            'content'       : messageContent,
            'declaredName'  : declaredName
        }, () => {
            disable($('#createPostBtn'));
        })
        .done(function (response) {
            console.log(response);

            switch (response.status) {
                case OK:
                    $('#recentsContainer')
                        .find('.row')
                        .prepend(
                            getRenderedMessage(
                                response.id, messageContent, declaredName
                            )
                        );

                    $('.message')
                        .first()
                        .fadeIn();

                    $('#messageInput, #declaredName')
                        .removeClass('valid')
                        .val('');
        
                    reloadLayout();

                    break;
                case ERROR:
                    toast('Algo anda mal, probá otra vez.');

                    break;
            }
        })
        .always(() => {
            setTimeout(() => {
                enable($('#createPostBtn'));
            }, INDEX['POST_OK_COOLDOWN']);
        });
    });

    setupMaterializeImages();

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

    if (typeof(navigator.share) != 'undefined') {
        $('#shareBtnHint').show();
        $('#shareProfileBtn').fadeIn();

        $('#shareProfileBtn').on('click', () => {
            navigator.share({
                title : 'Compartí tu perfil',
                text  : 'Dejáme un mensaje: ',
                url   : $('#shareableLink').val()
            });
        });
    } 

    console.info('document: success fetching critical assets.');

    loader = () => {
        console.info('index/window: success loading assets.');

        $('#requestRemovalModal').modal();
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
                            message['reported'] == 1,
                            message['image']
                        )
                    );
            });

            reloadLayout();
        } else {
            displayRemovableWarning(NO_MESSAGES_HINT);
        }
    }
});