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

let nsfwMode            = typeof(NSFW) == 'undefined' ? false : NSFW;
let readyForNSFW        = localStorage.getItem('canSeeNSFW') != null;

function resetMessageInputs() {
    $('#messageInput, #declaredName')
        .removeClass('valid')
        .val('');

    $('label[for="imageInput"]')
        .removeClass('green')
        .addClass('bg-dark-1')
        .find('.material-icons')
        .html('add_a_photo');

    $('.tooltipped').each(function () {
        M.Tooltip.init($(this)[0]).close();
    });

    $('#removeFileBtn').css({ 'right' : '-3.4em', 'opacity': 0 });

    $('#imageInput').val(null);

    enable($('#messageInput, #declaredName, label[for="imageInput"]'));
}

function toggleMessageLike(messageId) {
    run('messagesManager', 'toggleLike', { id: messageId })
    .then((response) => {
        console.info(response);

        if (response.data.status == OK) {
            likesCountElement = $('[data-message="' + messageId + '"]').find('.likesCount');

            likesCount = parseInt(likesCountElement.html());

            M.Toast.dismissAll();

            if (response.data.result.wasLiked) {
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

    M.Modal.getInstance($('#reportMessageModal')[0]).open();
}

function abortPost() {
    $('.message[data-message="null"]').fadeOut(() => {
        $('.message[data-message="null"]').remove();

        reloadLayout();
    });
}

function postMessage(messageContent, declaredName, token, image = null) {
    let createMessageModal = M.Modal.getInstance($('#createMessageModal')[0]);

    if (!isPosting) {
        isPosting = true;

        $('#removeFileBtn').css({ 'right' : '-3.4em', 'opacity': 0 });

        let previousHtml = createPostBtn.html();

        run('messagesManager', 'postMessage', {
            'content'       : messageContent,
            'declaredName'  : declaredName,
            'recipient'     : RECIPIENT,
            'image'         : image,
            'token'         : token,
            'nsfw'          : (RECIPIENT == null && typeof(nsfwMode) != 'undefined') ? nsfwMode : undefined
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

            reloadLayout();
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
        .then((response) => {
            console.log(response);

            switch (response.data.status) {
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
                            response.data.result.id, messageContent, declaredName, null, false, false, image, false
                        )
                    );

                    isGoingTop = true;
                    $('html, body')[0].scrollTo({
                        top: $('.message[data-message="' + response.data.result.id + '"]').position()['top']
                    });

                    isGoingTop = false;

                    $('.message')
                        .first()
                        .fadeIn();

                    $('#messageInput, #declaredName, #messageInput, #declaredName')
                        .removeClass('valid')
                        .val('');
        
                    reloadLayout();

                    createMessageModal.close();

                    resetMessageInputs();

                    break;
                case ERROR:
                    toast('Algo anda mal, probá otra vez.');

                    abortPost();

                    break;
            }
        })
        .catch(abortPost)
        .then(() => {
            createPostBtn
                .addClass('waves-effect waves-light')
                .html(previousHtml);

            postProgressBar = $(postProgressBar);

            postProgressBar.css({ 'width' : createPostBtn.outerWidth() });

            setTimeout(() => {
                isPosting = false;

                enable($('#messageInput, #declaredName, label[for="imageInput"]'));

                postProgressBar.fadeOut();
                
                createMessageModal.options.dismissible = true;
            }, MATERIALIZE_TRANSITION_TIME);
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

function switchToNSFW() {
    nsfwMode     = true;
    readyForNSFW = true;

    localStorage.setItem('canSeeNSFW', true);

    $('#nsfwSwitch')
        .prop('checked', true)
        .change();

    M.Modal.getInstance($('#nsfwSwitchModal')[0]).close();
}

$(document).ready(() => {
    createPostBtn   = $('#createPostBtn');
    imageInput      = $('#imageInput');

    M.Modal.init($('#createMessageModal')[0], {
        onOpenEnd: () => {
            messageInput = $('#messageInput')[0];

            messageInput.focus();
            messageInput.click();

            attachProgressBar();
        }
    });

    if (getStatusForNSFW() && localStorage.getItem('canSeeNSFW') == null) {
        animateRedirect(SYSTEM_HOSTNAME + 'exceptions/forbidden');
    }

    let fabDOM = $('.fixed-action-btn');

    fab = M.FloatingActionButton.init(fabDOM, { hoverEnabled: false });

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
                            M.Modal.getInstance($('#createMessageModal')[0]).open();

                            preloader.fadeOut(() => {
                                preloader.removeClass('active');

                                icon.fadeIn();

                                $(createMessageBtn.el)
                                    .off('click')
                                    .on('click', () => {
                                        M.Modal.getInstance($('#createMessageModal')[0]).open();
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
                document.scrollingElement.scrollTop > (
                    $('.message').last().offset()['top']
                    -
                    (
                        (SCROLLTOP_THRESHOLD * (document.documentElement.scrollHeight - document.documentElement.clientHeight)) / 100
                    )
                )
                ||
                document.scrollingElement.scrollTop == document.documentElement.scrollHeight - document.documentElement.clientHeight
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
            .getInstance($('#createMessageModal')[0])
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
                        .css({ 'right' : '-0.3em' });

                    if ($('.toast').length < 1) {
                        toast('Listo, agregaste ' + files[0].name + '.');
                    }
                } else {
                    $('#removeFileBtn').css({ 'right' : '-3.4em', 'opacity': 0 });

                    toast('El archivo seleccionado no es una imagen');
                }
            } else {
                $('label[for="imageInput"]')
                    .removeClass('green')
                    .addClass('bg-dark-1');

                $('label[for="imageInput"]')
                    .find('.material-icons')
                    .html('add_a_photo');

                $('#removeFileBtn').css({ 'right' : '-3.4em', 'opacity': 0 });
            }
        });

        $('#removeFileBtn').on('click', function () {
            try {
                $('.tooltipped').each(function () {
                    instance = M.Tooltip.getInstance($(this)[0]);

                    if (instance != null) {
                        instance.close();
                    }
                });
            } catch (exception) {
                console.warn('removeFileBtn/click: unable to close tooltips due to the following reason: \n\n', exception);
            }

            $(this).css({ 'right' : '-3.4em', 'opacity': 0 });

            $('label[for="imageInput"]')
                .removeClass('green')
                .addClass('bg-dark-1');

            $('label[for="imageInput"]')
                .find('.material-icons')
                .html('add_a_photo');

            $('#imageInput').val(null);
        });

        M.Modal.init($('#reportMessageModal')[0], {
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
                .then((response) => {
                    console.log(response);

                    switch (response.data.status) {
                        case OK:
                            toast('¡Gracias! Ya recibimos tu reporte.');

                            $('.message[data-message="' + toReport + '"]')
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
                .then(() => {
                    enable($('input[name="reportReason"], #sendReportBtn'));

                    M.Modal.getInstance($('#reportMessageModal')[0]).close();
                });
            } else {
                toast('Para enviar tu reporte, seleccioná una opción.');
            }
        });

        if (RECIPIENT == null) {
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

            function sortByFromTab(tab) {
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

            tabs = $('.tabs');

            M.Tabs.init(tabs[0]);

            tabs
                .find('span')
                .removeClass('hide');

            $('.tab').on('click', (event) => {
                tab = $(event.currentTarget).find('a');

                tabHint = tab.find('span');

                if (tabHint.css('display') == 'none') {
                    M.Toast.dismissAll();

                    toast('Ordenando ' + tabHint.text());
                }

                if (!tab.hasClass('active')) {
                    sortByFromTab(tab);
                }
            });
        }
    };

    if (localStorage.getItem('hasTriedFAB') == null) {
        $('#createMessageBtn')
            .addClass('pulse')
            .on('click', function () {
                if ($(this).hasClass('pulse')) {
                    localStorage.setItem('hasTriedFAB', true);

                    $(this).removeClass('pulse');
                }
            });
    }

    $(window).on('resize', calculateOnscreenImages);

    $('.tooltipped').each(function () {
        M.Tooltip.init($(this)[0]);
    });
});