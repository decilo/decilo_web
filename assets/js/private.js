let grid = null;

let createPostBtn       = null;
let maxScrollTop        = null;
let isPullingChunks     = false;

let toRemove = null;

let sortBy = SORTING_METHODS['BY_DATE'];

isPrivate = true;

function requestRemoval(id) {
    toRemove = id;

    M.Modal.getInstance($('#requestRemovalModal')[0]).open();
}

function tryToRemove() {
    if (toRemove == null) {
        toast('Tenés que seleccionar un mensaje a eliminar');

        console.warn('tryToRemove: I was called but "toRemove" was null, there\'s either a severe problem or it\'s just that the user is having fun with the console.');
    } else {
        run('messagesManager', 'tryToRemove', { id: toRemove }, () => {
            disable($('#requestRemovalModal').find('button'));
        })
        .then((response) => {
            console.info(response);

            switch (response.data.status) {
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
                    M.Modal.getInstance($('#requestRemovalModal')[0]).close();

                    $('.message[data-message="' + toRemove + '"]').fadeOut(() => {
                        $('.message[data-message="' + toRemove + '"]').remove();

                        toRemove = null;

                        toast('¡Eliminado!');

                        if ($('.message').length < 1) {
                            displayRemovableWarning(NO_MESSAGES_HINT);
                        } else {
                            reloadLayout();
                        }
                    });

                    break;
            }
        })
        .then(() => {
            enable($('#requestRemovalModal').find('button'));
        });
    }
}

$(document).ready(() => {
    createPostBtn = $('#createPostBtn');

    document.addEventListener('scroll', () => {
        if (
            $('.message').length > 0
            &&
            (
                $(window)[0].scrollTop > (
                    $('.message').last().offset()['top']
                    -
                    (
                        (SCROLLTOP_THRESHOLD * (document.documentElement.scrollHeight - document.documentElement.clientHeight)) / 100
                    )
                )
                ||
                $(window)[0].scrollTop == document.documentElement.scrollHeight - document.documentElement.clientHeight
            )
            &&
            !isPullingChunks
        ) {
            tryToPullChunks();
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

    $('#createPostBtn').on('click',  () => {
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
        .then((response) => {
            console.log(response);

            switch (response.data.status) {
                case OK:
                    $('.gridContainer')
                        .find('.row')
                        .prepend(
                            getRenderedMessage(
                                response.data.id, response.data.content, declaredName
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
        .then(() => {
            setTimeout(() => {
                enable($('#createPostBtn'));
            }, INDEX['POST_OK_COOLDOWN']);
        });
    });

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

    $('[data-src]').each(function () {
        $(this)
            .attr('src', $(this).data('src'))
            .prev()
            .css({
                width:  $(this).parent().width(),
                height: $(this).parent().width()
            });
    });

    $(window).on('resize', () => {
        $('[data-src]').each(function () {
            $(this)
                .prev()
                    .css({
                    width:  $(this).parent().width(),
                    height: $(this).parent().width()
                });
        });
    });

    loader = () => {
        console.info('index/window: success loading assets.');

        M.Modal.init($('#requestRemovalModal')[0]);
    };

    let searchParams = new URL(window.location).searchParams;

    if (searchParams.has('fromLogin')) {
        toast('Iniciaste sesión');

        newURL = window.location.href.split('?')[0]; // get the current URL without parameters

        // Quick workaround to drop the parameter once loaded.
        window.history.replaceState(
            null, // no additional data for this state
            document.getElementsByTagName('title')[0].innerHTML.trim(), // get the <title>
            newURL.substr(0, newURL.length - 1) // get the new URL without the trailing slash
        );
    }
});