let grid = null;

let createPostBtn       = null;
let imageInput          = null;
let maxScrollTop        = null;
let isPullingChunks     = false;
let isPosting           = false;

let toReport            = null;

let fab                 = null;

let sortBy              = parseInt(localStorage.getItem('sortBy'));

if (RECIPIENT != null) {
    sortBy = SORTING_METHODS['BY_DATE'];
} else if (isNaN(sortBy)) {
    sortBy = SORTING_METHODS['BY_RELEVANCE'];
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

function toggleMessageLike(messageId) {
    run('messagesManager', 'toggleLike', { id: messageId })
    .done((response) => {
        console.info(response);

        if (response.status == OK) {
            likesCountElement = $('[data-message="' + messageId + '"]').find('.likesCount');

            likesCount = parseInt(likesCountElement.html());

            M.Toast.dismissAll();

            if (response.result.wasLiked) {
                toast('Ya no te gusta esta publicación.');

                likesCount--;
            } else {
                toast('Te gusta esta publicación.');

                likesCount++;
            }

            likesCountElement.html(likesCount);
        } else {
            toast('Algo anda mal, por favor probá otra vez.');
        }
    });
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

            $('.gridContainer')
                .find('.row')
                .prepend(
                    getRenderedMessage(
                        null, messageContent, declaredName, null, false, false, image, false
                    )
                );

            $('.message')
                .first()
                .fadeIn();

            $('#messageInput, #declaredName, #messageInput, #declaredName')
                .removeClass('valid')
                .val('');

            reloadLayout()
        }, false, () => {
            let xhr = $.ajaxSettings.xhr();

            postProgressBar = $('#postProgressBar');
            postProgressBar
                .width(0)
                .show();

            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable) {
                    percentage  = Math.round((event.loaded * 100) / event.total);
                    width       = (percentage * createPostBtn.outerWidth()) / 100;

                    if (postProgressBar.width() < createPostBtn.outerWidth()) {
                        postProgressBar.css({ 'width' : width });
                    }
                }
            }, false);

            return xhr;
        })
        .done((response) => {
            console.log(response);

            switch (response.status) {
                case SUSPICIOUS_OPERATION:
                    toast('Necesitamos que resuelvas un desafío.');

                    abortPost();

                    break;
                case OK:
                    if ($('.gridContainer').find('.removableWarning').length > 0) {
                        $('.gridContainer')
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

                    $('.message')
                        .first()
                        .fadeIn();

                    $('#messageInput, #declaredName, #messageInput, #declaredName')
                        .removeClass('valid')
                        .val('');
        
                    reloadLayout();

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

$(document).ready(() => {
    createPostBtn   = $('#createPostBtn');
    imageInput      = $('#imageInput');

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

    createMessageBtn = fabDOM.find('#createMessageBtn');

    createMessageBtn
        .on('click', () => {
            if (isOnline) {
                icon = fabDOM.find('.material-icons');
                icon.fadeOut(() => {
                    preloader = fabDOM.find('.preloader-wrapper');
                    preloader.fadeIn(() => {
                        preloader.addClass('active');

                        loadRecaptcha(false, false, () => {
                            $('#createMessageModal').modal('open');

                            preloader.fadeOut(() => {
                                preloader.removeClass('active');

                                icon.fadeIn();

                                createMessageBtn
                                    .off('click')
                                    .on('click', () => {
                                        $('#createMessageModal').modal('open');
                                    });
                            });
                        });
                    });
                });
            } else {
                toast(NO_INTERNET_HINT);
            }
        });

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
        ) {
            tryToPullChunks();
        }

        calculateOnscreenImages();
    }, { passive: true });

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

    $('#createPostBtn').on('click', () => {
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

    console.info('document: success fetching critical assets.');

    loader = () => {
        console.info('index/window: success loading assets.');

        enable(createPostBtn);

        attachProgressBar();

        $(window).on('resize', attachProgressBar);

        $('#imageInput').on('change', function () {
            let files = $(this)[0].files;

            if (files.length > 0) {
                if (files[0].type.includes('image')) {
                    $('label[for="imageInput"]')
                        .removeClass('bg-dark-1')
                        .addClass('green')
                        .find('.material-icons')
                        .html('check');

                    $('#removeFileBtn')
                        .css({ 'display' : 'block', 'opacity' : 1 })
                        .animate({ 'right' : '-0.3em' });

                    if ($('.toast').length < 1) {
                        toast('Listo, agregaste ' + files[0].name + '.');
                    }
                } else {
                    $('#removeFileBtn').animate({ 'right' : '-3.4em' }, () => {
                        $('#removeFileBtn').fadeOut();
                    });

                    toast('El archivo seleccionado no es una imagen');
                }
            } else {
                $('label[for="imageInput"]')
                    .removeClass('green')
                    .addClass('bg-dark-1');

                $('label[for="imageInput"]')
                    .find('.material-icons')
                    .html('add_a_photo');

                $('#removeFileBtn').animate({ 'right' : '-3.4em' }, () => {
                    $('#removeFileBtn').fadeOut();
                });
            }
        });

        $('#removeFileBtn').on('click', function () {
            try {
                $('.tooltipped').tooltip('close');
            } catch (exception) {
                console.warn('removeFileBtn/click: unable to close tooltips due to the following reason: \n\n', exception);
            }

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
        

        if (sortBy != SORTING_METHODS['BY_RELEVANCE']) {
            tabs = $('.tabs');

            switch (sortBy) {
                case SORTING_METHODS['BY_RELEVANCE']:
                    tabs.find('#sortByRelevance').addClass('active');

                    break;
                case SORTING_METHODS['BY_DATE']:
                    tabs.find('#sortByDate').addClass('active');

                    break;
                case SORTING_METHODS['BY_LIKES']:
                    tabs.find('#sortByLikes').addClass('active');

                    break;
                case SORTING_METHODS['BY_COMMENTS']:
                    tabs.find('#sortByComments').addClass('active');

                    break;
            }
        }

        $('.tabs')
            .tabs()
            .find('span')
            .removeClass('hide');

        $('.tab').on('click', (event) => {
            tab = $(event.currentTarget).find('a');

            tabHint = tab.find('span');

            if (!tabHint.is(':visible')) {
                M.Toast.dismissAll();

                toast('Ordenando ' + tabHint.text());
            }

            if (!tab.hasClass('active')) {
                switch (tab.attr('id')) {
                    case 'sortByRelevance':
                        sortBy = SORTING_METHODS['BY_RELEVANCE'];
    
                        break;
                    case 'sortByDate':
                        sortBy = SORTING_METHODS['BY_DATE'];

                        break;
                    case 'sortByLikes':
                        sortBy = SORTING_METHODS['BY_LIKES'];

                        break;
                    case 'sortByComments':
                        sortBy = SORTING_METHODS['BY_COMMENTS'];

                        break;
                }

                localStorage.setItem('sortBy', sortBy);

                console.info('setItem: saved "sortBy" (' + sortBy + ').');

                gridContainer = $('.gridContainer');

                messagesRow = gridContainer.find('.row');
                
                messagesRow.fadeOut(() => {
                    messagesRow.html('');

                    gridContainer.find('.preloader-container').fadeIn();

                    canPullChunks   = true;
                    isPullingChunks = false;

                    setTimeout(() => {
                        tryToPullChunks(false, reloadLayout);
                    }, MATERIALIZE_TRANSITION_TIME);
                });
            }
        });
    };

    if (localStorage.getItem('hasTriedFAB') == null) {
        $('#createMessageBtn')
            .addClass('pulse')
            .on('click', function () {
                if (
                    $(this).hasClass('pulse')
                    &&
                    localStorage.getItem('hasTriedFAB') == null
                ) {
                    localStorage.setItem('hasTriedFAB', true);

                    $(this).removeClass('pulse');
                }
            });
    }

    $(window).resize(calculateOnscreenImages);

    $('.tooltipped').tooltip();
});