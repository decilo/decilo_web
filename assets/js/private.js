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

                    $('.message[message="' + toRemove + '"]').fadeOut(() => {
                        toRemove = null;

                        $('.message[message="' + toRemove + '"]').remove();

                        toast('¡Eliminado!');

                        if ($('.message:visible').length < 1) {
                            displayRemovableWarning(
                                `¡Nada por acá!
                                <br>
                                <br>
                                Pasále el link a tus amigos y empezá a recibir mensajes.`
                            );
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
        }
    } else {
        console.warn('Cannot update layout, Masonry isn\'t ready.');
    }
}

function getLastMessageId() {
    return parseInt(
        $('.message')
            .last()
            .attr('message')
    );
}

function getRenderedMessage(id, content, declaredName, created = null, display = false) {
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

    return `<div class="col s12 m12 l6 message" ` + (display ? '' : 'style="display: none;"') + ` message="` + id + `">
                <div class="card bg-dark-3 card-box">
                    <button
                        type="button"
                        class="btn-floating halfway-fab mid-card-fab waves-effect waves-light red"
                        onclick="requestRemoval(` + id + `);"
                    >
                        <i class="material-icons mid-card-fab-icon">delete</i>
                    </button>
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

$(document).ready(function () {
    createPostBtn = $('#createPostBtn');

    $(window).on('scroll', function () {
        maxScrollTop = document.documentElement.scrollHeight - document.documentElement.clientHeight;

        if (
            $(window).scrollTop() > (maxScrollTop / 2)
            &&
            !isPullingChunks
            &&
            $('.message').length > 0
        ) {
            run('messagesManager', 'getRecent', { private: true, after: getLastMessageId() }, () => { isPullingChunks = true; })
            .done(function (response) {
                console.log(response);

                if (response.result.length > 0) {
                    let renderedHTML = '';
                    
                    response.result.forEach((message) => {
                        renderedHTML += getRenderedMessage(
                            message['id'], message['content'], message['declaredName'], message['created'], true
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

        $('#preloader').fadeIn(() => {
            createPostBtn.removeAttr('disabled');

            $('.tap-target').tapTarget({
                onOpen: () => {
                    $('.tap-target-origin').addClass('black-text');
                }
            });

            run('messagesManager', 'getRecent', { private: true }, () => {}, true)
            .done(function (response) {
                console.info(response);

                switch (response.status) {
                    case OK:
                        if (response.result.length > 0) {
                            $('#preloader').fadeOut(() => {
                                let renderedHTML = '';

                                response.result.forEach((message) => {
                                    renderedHTML += getRenderedMessage(
                                        message['id'], message['content'], message['declaredName'], typeof(message['created']) == 'undefined' ? null : message['created']
                                    );
                                });

                                $('#preloader')
                                    .next()
                                    .append(renderedHTML)
                                    .find('.message')
                                    .fadeIn();

                                reloadLayout();
                            });
                        } else {
                            displayRemovableWarning(
                                `¡Nada por acá!
                                 <br>
                                 <br>
                                 Pasále el link a tus amigos y empezá a recibir mensajes.`
                            );
                        }

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

            $('#requestRemovalModal').modal();
        });
    };
});