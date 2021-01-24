var jQuery = $;

const NO_MESSAGES_HINT = 
    `¡Nada por acá!
     <br>
     <br>
     Pasále el link a tus amigos y empezá a recibir mensajes.`;

const NO_INTERNET_HINT = 'No tenés conexión a internet.';
const BACK_ONLINE_HINT = 'Ya tenés internet.';

let isPrivate = false;

let loader = () => {
    console.info('loader: no loader was specified.');
};

let deferredPreloader = null;

let viewportThreshold = (VIEWPORT_VISIBLE_THRESHOLD * $(window).height()) / 100;

let isOnline = navigator.onLine;
let heartbeatLooper = null;

let canPullChunks   = true;
let deferredFetcher = null;

let reportAdImpressions = true;

let markdownConverter = null;

let loginTarget = null;

let gotHistoryPushState = false;

let userNavBackground = null;

const FONTS = [
    {
        family:  'Lato',
        weight:  300,
        src:     'url(https://fonts.gstatic.com/s/lato/v17/S6u9w4BMUTPHh7USSwiPGQ.woff2) format(\'woff2\')'
    },
    {
        family:  'Lato',
        weight:  400,
        src:     'url(https://fonts.gstatic.com/s/lato/v17/S6uyw4BMUTPHjx4wXg.woff2) format(\'woff2\')'
    },
    {
        family:  'Lato',
        weight:  700,
        src:     'url(https://fonts.gstatic.com/s/lato/v17/S6u9w4BMUTPHh6UVSwiPGQ.woff2) format(\'woff2\')'
    },
    {
        family:  'Roboto',
        weight:  300,
        src:     'url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmSU5fBBc4.woff2) format(\'woff2\')'
    },
    {
        family:  'Robot',
        weight:  400,
        src:     'url(https://fonts.gstatic.com/s/roboto/v20/KFOmCnqEu92Fr1Mu5mxKOzY.woff2) format(\'woff2\')'
    },
    {
        family:  'Roboto',
        weight:  700,
        src:     'url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmWUlfABc4EsA.woff2) format(\'woff2\')'
    },
    {
        family:  'Material Icons',
        weight:  400,
        src:     'url(https://fonts.gstatic.com/s/materialicons/v55/flUhRq6tzZclQEJ-Vdg-IuiaDsNc.woff2) format(\'woff2\')'
    }
];

let loadedFonts = [];

FONTS.forEach((font) => {
    let fontFace = new FontFace(font['family'], font['src'], {
        style:          font['style'],
        weight:         font['weight']
    });

    fontFace
        .load()
        .then(() => {
            loadedFonts.push(fontFace);

            if (loadedFonts.length == FONTS.length) {
                console.info('FontFace: loaded', loadedFonts.length, 'fonts (out of', FONTS.length, 'required fonts).');

                loadedFonts.forEach((loadedFont) => {
                    document.fonts.add(loadedFont);
                });
            }
        });
});

$.fn.fadeIn = function(callback = () => {}, opacity = 1, duration = MATERIALIZE_TRANSITION_TIME) {
    $(this).css({ display: '', opacity: opacity });

    setTimeout(callback, duration);
}

$.fn.fadeOut = function(callback = () => {}, opacity = 0) {
    element = $(this);

    element.css({
        transition: 'opacity ' + (MATERIALIZE_TRANSITION_TIME / 1000) + 's linear',
        opacity: opacity
    });

    setTimeout(() => {
        if (element.css('display') != 'none') {
            element.data('data-display', element.css('display'));
        }

        element.css({ display: 'none' });

        callback();
    }, MATERIALIZE_TRANSITION_TIME);
}

// Workaround missing "bind" on cash-dom.
$.fn.bind = $.fn.on;

function pushFakeHistory() {
    if (!gotHistoryPushState) {
        history.pushState(null, document.title, location.href);

        gotHistoryPushState = true;
    }
}

function displayNoInternetFAB() {
    $('#noInternetBtn')
        .css({ oapcity: 0, display: 'block'})
        .css({ opacity: 0.5 });
}

function run(url, action, values, before = () => {}, overridesFailure = false, onUploadProgress = () => {}) {
    console.info('run \n\nurl:', url, 'action:', action, 'values:', values, 'before:', before);

    return axios.post('services/' + url + '.php', JSON.stringify({
        action: action,
        values: values
    }), {
        transformRequest: [ (data) => {
            before();

            return data;
        }],
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .catch((error) => {
        if (!overridesFailure) {
            console.error(error);

            if ($('.toast').length < 1) {
                if (isOnline && error.status != 0) {
                    toast('Algo salió mal, por favor probá otra vez.');
                } else {
                    isOnline = false;

                    displayNoInternetFAB();

                    toast(NO_INTERNET_HINT);
                }
            }
        }
    });
}

function toast(html) {
    if (typeof(M) != 'undefined') {
        return M.toast({ html: html });
    } else {
        console.warn('toast: unable to display toast, there must be a slow connection, since the M class isn\'t available yet.\n\nhtml: ' + html);

        return null;
    }
}

function redirect(url, timeout = null) {
    setTimeout(() => {
        if (url == window.location.href) {
            window.location.reload();
        } else {
            window.location.href = url;
        }
    }, timeout = null ? MATERIALIZE_TRANSITION_TIME : timeout);
}

function animateRedirect(url, fullBody = false, timeout = null) {
    setTimeout(() => {
        redirCall = () => {
            $(fullBody ? 'body' : 'main').fadeOut(() => {
                redirect(url, 0);
            });
        };

        sidenav = M.Sidenav.getInstance($('.sidenav')[0]);

        if (typeof(sidenav) != 'undefined') {
            sidenav.close();

            setTimeout(redirCall, sidenav.options.outDuration);
        } else {
            redirCall();
        }
    }, timeout = null ? MATERIALIZE_TRANSITION_TIME : timeout);
}

function disable(element) {
    element.attr('disabled', '');
}

function enable(element) {
    element
        .removeAttr('disabled')
        .removeClass('disabled');
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

function reloadLayout(toAppend = null) {
    function setupInstance() {
        if (toAppend == null) {
            grid.reloadItems();
            grid.layout();
        } else {
            grid.appended(
                $('.message').not('[style^="position"]')
            );
        }
    }

    if (typeof(Masonry) != 'undefined') {
        if (toAppend == null || grid == null) {
            console.info('reloadLayout: grid initialization started.');

            grid = new Masonry(
                '.gridContainer',
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

            setupInstance();

            $('.tooltipped').each(function () {
                M.Tooltip.init($(this)[0]);
            });
        }

        calculateOnscreenImages();

        recentsContainer = $('.gridContainer');

        if (recentsContainer.length > 0) {
            recentsContainer
                .find('.message-image')
                .each(function () {
                    let clickedOnce = false;
                    let image       = $(this);

                    image
                        .css({
                            transition: 'filter linear 0.5s'
                        })
                        .off('click')
                        .on('click', () => {
                            if (image.hasClass('unverified-img')) {
                                if (clickedOnce) {
                                    image.removeClass('unverified-img');
                                } else {
                                    toast('Tocá la imagen de nuevo para verla.');

                                    clickedOnce = true;
                                }
                            } else {
                                image.toggleClass('message-image-full');
                            }
                        });
                });
        }
    } else {
        console.warn('Cannot update layout, Masonry isn\'t ready.');
    }
}

function displayRemovableWarning(html, target = $('.gridContainer')) {
    target
        .find('.removableWarning')
        .remove();

    target.prepend(
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

function isElementInViewport(element) {
    element        = $(element);

    elementTop     = element.offset().top;
    elementBottom  = elementTop + element.outerHeight();

    viewportTop    = document.scrollingElement.scrollTop;
    viewportBottom = viewportTop + $(window).height();

    return elementBottom > (viewportTop - viewportThreshold) && (elementTop - viewportThreshold) < viewportBottom;
}

function getRenderedMessage(id, content, declaredName, created = null, display = false, reported, image = null, verified = true, comments = 0, deferImage = false, likes = 0, position = -1) {
    content = content.toString();

    return `<div
                class="col s12 ` + (PRIVATE ? `m12 l6` : `m6 l3`) + ` message ` + (id == null ? 'not-posted' : '') + `"
                ` + (display ? '' : 'style="display: none;"') + `
                data-message="` + id + `"
                data-position="` + position + `"
            >
                <div class="card bg-dark-3 card-box">` + (PRIVATE ? `
                    <button
                        type="button"
                        class="btn-floating halfway-fab mid-card-fab waves-effect waves-light btn-hfab"
                        onclick="requestRemoval(` + id + `);"
                    >
                        <i class="material-icons mid-card-fab-icon">delete</i>
                    </button>` : (LOGGED_IN && id != null ? `
                    <button
                        type="button"
                        class="` + (reported ? 'btn-hfab-disabled' : 'btn-hfab') + ` btn-floating halfway-fab mid-card-fab waves-effect waves-light tooltipped"
                        data-position="left"` + (reported ? `
                        data-tooltip="Ya lo reportaste"` : `
                        data-tooltip="Reportar"
                        onclick="reportMessage(` + id + `);"`) + `
                    >
                        <i class="material-icons mid-card-fab-icon">flag</i>
                    </button>` : ``)) + (image == null ? '' : `
                    <div class="card-image">
                        <div class="message-image valign-wrapper">
                            <div class="preloader-wrapper small active center-block">
                                <div class="spinner-layer border-dark-5 border-light-9">
                                    <div class="circle-clipper left">
                                    <div class="circle"></div>
                                    </div><div class="gap-patch">
                                    <div class="circle"></div>
                                    </div><div class="circle-clipper right">
                                    <div class="circle"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <img
                            ` + (deferImage ? `data-src="` + image + `"` : `src="` + image + `"`) + `
                            style="display: none; width: 0px; height: 0px;"
                            onload="
                                $(this)
                                    .prev()` + (verified ? '' : `.addClass('unverified-img')`) + `
                                    .css({ background: 'url(` + image + `)' })
                                    .find('.preloader-wrapper')
                                    .remove();
                            "
                            onerror="
                                $(this).parent().hide();

                                reloadLayout();
                            "
                        >
                    </div>`) + `
                    <div class="card-content white-text">
                        <span class="card-title roboto light-3">` + (declaredName == null ? 'Anónimo' : declaredName) + `</span>
                        <p class="lato thin word-wrap process-whitespaces overflow-ellipsis message-content light-4">` + 
                            (
                                content.length > MESSAGES['MAX_LENGTH']
                                    ? convertMDtoHTML(
                                        content.substr(0, MESSAGES['MAX_LENGTH']) + '…'
                                    )
                                    : convertMDtoHTML(content)
                            ) + `
                        </p>
                        <div class="message-spacer"></div>
                        <a
                            class="custom-link regular small"
                            href="` + (id == null ? '#' : `view/` + (RECIPIENT == null && !PRIVATE ? (getStatusForNSFW() ? `nsfw/` : ``) : 'private/') + id) + `"
                        >
                            Ver más
                        </a>` + (verified || image == null ? `` : `
                        <div class="message-spacer"></div>
                        <p class="red-text thin small">* Verificación pendiente</p>`) + `
                    </div>
                    <div class="card-action center">
                        <span class="lato regular small">` + dayjs(created == null ? new Date() : created).format('L LT') + `</span>
                    </div>
                    <ul class="collection with-header bg-dark-7 border-dark-7 hand">
                        <li class="collection-header bg-light-5 bg-dark-7 border-dark-7 no-select">
                            <div class="d-flex flex-center">` + (PRIVATE || RECIPIENT != null ? `` : `
                                <div class="no-select likes-toggle" ` + (id == null ? '' : `onclick="toggleMessageLike(` + id + `);"`) + `>
                                    <span class="counter likesCount">` + likes + `</span> <i class="material-icons small collection-icon"> thumb_up </i>
                                </div>
                                <div class="flex-divider bg-light-10 bg-dark-8"></div>`) + `
                                <div class="no-select comments-open-btn ` + (PRIVATE || RECIPIENT != null ? `force-center` : ``) + `" ` + (id == null ? '' : `onclick="openCommentsModal(` + id + `, ` + PRIVATE + `);"`) + `>
                                    <i class="material-icons small collection-icon"> comment </i> <span class="counter commentCount">` + comments + `</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
             </div>`;
}

function getRenderedComment(id = null, declaredName = null, content, active = false, likes = 0) {
    return  `<li data-comment="` + (id == null ? 'null' : id) + `" ` + (active ? 'class="active"' : '') + `>
                <div class="collapsible-header bg-dark-7 border-dark-8">
                    <span class="collection-header-main full-width">
                        ` + (declaredName == null ? 'Anónimo' : declaredName) + ` • <span class="thin add-space"> ` + dayjs().format('L LT') +` </span>
                    </span>
                    <span class="collection-header-extra-wrapper border-dark-8 border-light-10" onclick="toggleCommentLike(event, ` + id  + `);">
                        <span class="collection-header-extra">
                            <i class="material-icons small collection-icon force-inline no-select"> thumb_up </i>
                            <span class="counter likesCount collection-counter no-select">` + likes + `</span>
                        </span>
                    </span>
                </div>
                <div class="collapsible-body bg-dark-7 border-dark-8">
                    <span class="thin word-wrap process-whitespaces overflow-ellipsis">` + 
                        convertMDtoHTML(content) + `
                    </span>
                </div>
             </li>`;
}

function openCommentsModal(message, private) {
    loadRecaptcha();

    M.Modal.getInstance($('#commentsModal')[0]).open();

    let card = $('.message[data-message="' + message + '"]');
    let image = card.find('img');

    $('#commentsMessageWrapper').html(
        `<ul class="collection border-dark-8">
            <li class="collection-item avatar ` + (image.length > 0 ? '' : 'no-avatar') + ` bg-dark-7">` + (image.length > 0 ? `
                <img
                    src="` + image.prop('src') + `"
                    alt="` + image.prop('alt') + `"
                    class="circle"
                >` : '') + `
                <div class="collection-spacer"></div>
                <span class="title"> ` + card.find('.card-title').text() +` </span>
                <div class="collection-spacer"></div>
                <div class="collection-spacer"></div>
                <p class="thin word-wrap process-whitespaces">` + 
                    card.find('.card-content').find('p').text().trim() + `
                </p>
                <div class="collection-spacer"></div>
            </li>
         </ul>

         <div class="divider"></div>
         <div class="collection-spacer"></div>
         <div class="collection-spacer"></div>

         <div class="row">
            <div class="input-field col s12">
                <textarea id="commentInput" class="materialize-textarea dark-5" data-length="65535"></textarea>
                <label for="commentInput"> Dejá tu comentario </label>
                <span class="helper-text" data-error="Tenés que escribir un comentario" data-success="Ya podés publicarlo">Escribí tu comentario</span>
            </div>

            <div class="input-field col s12 m9">
                <input id="commentDeclaredName" type="text" placeholder="Anónimo" class="validate dark-5">
                <label for="commentDeclaredName" class="active"> Tu nombre </label>
            </div>

            <div class="col s6 offset-s6 m3">
                <button id="sendCommentBtn" type="button" class="btn waves-effect waves-light col right btn-block bg-light-1 bg-dark-1 dark-5 fixed-width-btn fixed-height-btn ` + (getStatusForNSFW() ? `bg-nsfw` : ``) + `">
                    Enviar
                </button>
            </div>
         </div>

         <ul
            id="commentsCollapsible"
            class="collapsible border-dark-8"
            style="
                height: 0px;
                transition: height 2s ease-out;
                overflow: hidden;
            "
         >
         </ul>`
    );

    $('#commentInput').on('change keyup keydown', function () {
        if ($(this).val().length > 0) {
            $(this)
                .removeClass('invalid')
                .addClass('valid');
        } else {
            $(this)
                .addClass('invalid')
                .removeClass('valid');
        }
    });

    $('#commentInput, #commentDeclaredName').on('change keyup', (event) => {
        if (event.ctrlKey && event.key == 'Enter') {
            $('#sendCommentBtn').click();
        }
    });

    $('#sendCommentBtn').on('click', () => {
        content         = $('#commentInput').val();
        declaredName    = $('#commentDeclaredName').val();
        declaredName    = declaredName.length > 0 ? declaredName : null;

        if (content.length > 0) {
            if (isOnline) {
                run('commentsManager', 'postComment', {
                    content:        content,
                    declaredName:   declaredName,
                    message:        message,
                    private:        private || (typeof(RECIPIENT) != 'undefined' && RECIPIENT != null)
                }, () => {
                    disable($('#commentInput, #commentDeclaredName, #sendCommentBtn'));
                })
                .then((response) => {
                    console.info(response);

                    if (response.data.status == OK) {
                        console.info('postComment: alright, we got a response!');

                        $('#commentInput, #commentDeclaredName')
                            .val('')
                            .trigger('change');

                        console.info('postComment: now, we\'ll clean up those inputs.');

                        commentsCollapsible = $('#commentsCollapsible');

                        instance = M.Collapsible.getInstance($('#commentsCollapsible')[0]);

                        console.log(instance);

                        for (let index = 0; index < commentsCollapsible.find('li').length; index++) {
                            instance.close(index);
                        }

                        console.info('postComment: ok, we have re-initialized the collapsible and closed its items.');

                        commentsCollapsible
                            .find('.active')
                            .removeClass('active');

                        commentsCollapsible.prepend(
                            getRenderedComment(response.data.result.id, declaredName, content)
                        );

                        console.info('postComment: cool, we got the DOM changed.');
                        
                        // Reset the container and the instance because of reasons.
                        commentsCollapsible = M.Collapsible.init(commentsCollapsible[0], { accordion: false });
                        commentsCollapsible.open(0);

                        console.info('postComment: so we\'ve just re-initialized the instance back again, this is getting frustrating.');

                        firstComment = $(commentsCollapsible.el).find('li').first();

                        $('#commentsModal').find('.modal-content')[0].scrollTo({
                            top: firstComment.offset()['top'] - firstComment.find('.collapsible-header').height()
                        });

                        commentCount = $('.message[data-message="' + message + '"]').find('.commentCount');
                        commentCount.html(
                            parseInt(commentCount.html()) + 1
                        );

                        console.info('postComment: finally, I did it, we did it, what we all waited for so long is finally here, we\'re done!');

                        $('#commentInput').removeClass('invalid valid');

                        toast('¡Listo! Publicaste tu comentario.');
                    } else {
                        toast('Algo anda mal, probá de nuevo.');
                    }
                })
                .then(() => {
                    enable($('#commentInput, #commentDeclaredName, #sendCommentBtn'));
                });
            } else {
                if ($('.toast').length < 1) {
                    toast(NO_INTERNET_HINT);
                }
            }

            $('#commentInput')
                .removeClass('invalid')
                .addClass('valid');
        } else {
            $('#commentInput')
                .addClass('invalid')
                .removeClass('valid');
        }
    });

    M.Collapsible.init(commentsCollapsible[0], { accordion: false });

    if (isOnline) {
        run('commentsManager', 'getComments', {
            message: message,
            private: private || (typeof(RECIPIENT) != 'undefined' && RECIPIENT != null)
        })
        .then((response) => {
            console.info(response);

            commentsCollapsible = $('#commentsCollapsible');

            switch (response.data.status) {
                case OK:
                    let renderedHTML = '';

                    response.data.result.forEach((comment) => {
                        renderedHTML += getRenderedComment(comment['id'], comment['declaredName'], comment['content'], false, comment['likes']);
                    });

                    commentsCollapsible.append(renderedHTML);
                    commentsCollapsible.css({ 'height': '' });

                    M.Collapsible.init(commentsCollapsible[0], { accordion: false });

                    if (commentsCollapsible.find('li').length > 0) {
                        M.Collapsible
                            .getInstance($('#commentsCollapsible')[0])
                            .open(0);
                    }

                    break;
            }
        });
    } else {
        if ($('.toast').length < 1) {
            toast(NO_INTERNET_HINT);
        }
    }
}

function loadRecaptcha(defer = false, async = false, then = () => {}) {
    if (isOnline) {
        if (typeof(grecaptcha) == 'undefined') {
            // Google reCaptcha v3
            script          = document.createElement('script');
            script.src      = 'https://www.google.com/recaptcha/api.js?render=' + RECAPTCHA_PUBLIC_KEY;
            script.onload   = () => {
                console.log('reCaptcha v3: successfully loaded.');

                loader();

                then();
            };

            if (defer) {
                script.defer = true;
            }

            if (async) {
                script.async = true;
            }

            script.onerror  = () => {
                toast('Algunos módulos no fueron cargados, si falla algo, intentá recargando la página.');
            }
        
            document.getElementsByTagName('head')[0].appendChild(script);
        } else {
            then();
        }
    } else {
        console.info('loadRecaptcha: you\'re offline, we won\'t try to load reCaptcha v3 for now.');
    }
}

function calculateOnscreenImages() {
    $('.message').each(function () {
        if (isElementInViewport(this)) {
            img = $(this).find('img');

            if (img.length > 0 && typeof(img.attr('data-src')) != 'undefined') {
                img
                    .attr('src', img.data()['src'])
                    .removeAttr('data-src');
            }
        }
    });
}

function checkIfOnline(onSuccess = () => {}, onError = () => {
    console.warn('onlineTest: we\'re offline, setting the dedicated flag.');

    isOnline = false;

    displayNoInternetFAB();

    toast(NO_INTERNET_HINT);
}) {
    fetch(new Request('/?onlineTest', { headers: { 'x-online-test': true } }))
    .then(() => {
        console.info('onlineTest: waiting ' + Math.round(FAILURE_RETRY_INTERVAL / 1000) + ' seconds before trying to update caches...');

        setTimeout(() => {
            if (isOnline) {
                console.log('onlineTest: we\'re online, updating caches.');

                caches.delete('response-store');
                caches.open('response-store').then((cache) => {
                    urls = [
                        '/',
                        '/privacy',
                        '/profile',
                        '/private',
                        '/exceptions/not_found',
                        '/exceptions/bad_request',
                        '/exceptions/forbidden',
                        '/exceptions/failed_dependency',
                        '/exceptions/internal_server_error',
                        '/exceptions/maintenance',
                        '/assets/css/maintenance.min.css'
                    ];

                    return cache.addAll(urls)
                });
            } else {
                console.warn('onlineTest: we\'re offline, waiting for the next promise to resolve.');
            }
        }, FAILURE_RETRY_INTERVAL);

        $('#noInternetBtn').fadeOut();

        onSuccess();
    })
    .catch(() => {
        if (heartbeatLooper == null) {
            heartbeatLooper = setInterval(() => {
                if (!isOnline) {
                    checkIfOnline(() => {
                        isOnline = true;

                        if ($('.toast').length < 1) {
                            toast(BACK_ONLINE_HINT);

                            clearInterval(heartbeatLooper);

                            heartbeatLooper = null;
                        }
                
                        // This is ugly, I know, but I'm not rewriting that core part.
                        document.dispatchEvent(new Event('scroll'));
                    }, () => {});
                }
            }, FAILURE_RETRY_INTERVAL);
        }

        displayNoInternetFAB();

        onError();
    });
}

function getRenderedAd(
    id,
    content,
    companyName,
    created = null,
    image = null,
    forceClass = null,
    badgeData = { bgClass: 'bg-dark-10 bg-light-11', text: 'Publicidad' },
    impressions = null,
    showRemoveButton = false
) {
    return `<div class="col ` + (forceClass == null ? (isPrivate ? 's12 m12 l6' : 's12 m6 l3') : forceClass) + ` message" data-ad="` + id + `">
                <div class="card bg-dark-3 card-box">` + (showRemoveButton ? `
                    <button
                        type="button"
                        class="btn-floating halfway-fab mid-card-fab-ad waves-effect waves-light btn-hfab"
                        data-ad="` + id + `"
                    >
                        <i class="material-icons mid-card-fab-icon">delete</i>
                    </button>` : '') + (image == null ? '' : `
                    <div class="card-image">
                        <img
                            class="` + (id == null ? '' : 'materialboxed') + ' ' + (verified ? '' : 'unverified-img') + `"
                            alt="Imagen adjunta"
                            src="` + image + `"
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
                        <span class="card-title roboto light-3">` +
                            (companyName == null ? 'Anónimo' : companyName) + `
                            <span class="badge ` + badgeData.bgClass + ` ads-badge-text"> ` + badgeData.text + (impressions == null ? `` : ` 
                                | <i class="material-icons badge-icon tiny"> visibility </i> ` + impressions) + `
                            </span>
                        </span>
                        <p class="lato thin word-wrap process-whitespaces overflow-ellipsis message-content light-4">` + 
                            (
                                content.length > MESSAGES['MAX_LENGTH']
                                    ? convertMDtoHTML(
                                        content.substr(0, MESSAGES['MAX_LENGTH']) + '…' 
                                    )
                                    : convertMDtoHTML(content)
                            ) + `
                        </p>
                    </div>
                    <div class="card-action center">
                        <span class="lato regular small">` + dayjs(created == null ? new Date() : created).format('L LT') + `</span>
                    </div>
                </div>
            </div>`;
}

function tryToPushRandomAd(then = () => {}) {
    run('adsManager', 'getRandomAd', {}, () => {}, true)
    .then((response) => {
        console.info(response);

        messages = $('.message');

        if (messages.length > 0) {
            if (response.data.result) {
                messages.eq(
                    Math.round(
                        Math.random() * (messages.length - 1)
                    )
                ).after(
                    getRenderedAd(
                        response.data.result.id,
                        response.data.result.content,
                        response.data.result.companyName
                    )
                );

                reloadLayout();
            } else {
                console.warn('tryToPushRandomAd: unable to push, unexpected response.');
            }
        } else {
            console.info('tryToPushRandomAd: there are no messages in this view, this is a sad moment for advertisers. :(');
        }
    })
    .catch((error) => {
        console.error(error);
    })
    .then(then);
}

async function tryToPullChunks(firstCall = false, then = () => {}) {
    if (canPullChunks) {
        if (isOnline) {
            if (isPullingChunks) {
                console.info('tryToPullChunks: cannot pull now, there\'s a pending request.');
            } else {
                lastMessageData = getLastMessageData();

                gridContainer = $('.gridContainer');

                preloader = gridContainer.find('.preloader-container');

                if (typeof(nsfwMode) == 'undefined') {
                    nsfwMode = getStatusForNSFW();
                    
                    if (!nsfwMode) {
                        nsfwMode = undefined;
                    }
                }
 
                run('messagesManager', 'getRecent', {
                    after:      typeof(lastMessageData) != 'undefined' ? lastMessageData.message  : undefined,
                    startAt:    typeof(lastMessageData) != 'undefined' ? lastMessageData.position : undefined,
                    recipient:  RECIPIENT,
                    private:    PRIVATE,
                    sortBy:     sortBy,
                    nsfw:       nsfwMode
                }, () => { isPullingChunks = true; })
                .then((response) => {
                    console.log(response);

                    if (response.data.result.length > 0) {
                        let renderedHTML = '';
                        
                        response.data.result.forEach((message) => {
                            renderedHTML += getRenderedMessage(
                                message['id'], message['content'], message['declaredName'], message['created'], true, parseInt(message['reported']) == 1, message['image'], parseInt(message['verified']) == 1, message['comments'], true, message['likes'], message['position']
                            );
                        });

                        row = gridContainer.find('.row');

                        deferredActions = () => {
                            row.append(renderedHTML).fadeIn();

                            reloadLayout(renderedHTML);

                            if (
                                $('.message').last().position()['top'] > document.documentElement.clientHeight
                                &&
                                deferredFetcher != null
                            ) {
                                clearInterval(deferredFetcher);

                                deferredFetcher = null;
                            }

                            console.info('tryToPullChunks: successfully pulled ' + response.data.result.length + ' chunks.');

                            then();

                            tryToPushRandomAd();

                            let tryToFillViewport = false; let checked = 0;

                            $(
                                $('.message').get().reverse()
                            ).each(function () {
                                if (checked <= MESSAGES['VIEWPORT_CHECK_LENGTH']) {
                                    tryToFillViewport = (
                                        $(this).offset()['top'] + $(this).height() < window.innerHeight
                                        &&
                                        canPullChunks
                                    );

                                    checked++;
                                }
                            });

                            if (tryToFillViewport) {
                                tryToPullChunks();
                            }
                        };

                        if (preloader.css('display') != 'none') {
                            preloader.fadeOut(() => {
                                preloader.hide();

                                deferredActions();
                            });
                        } else {
                            deferredActions();
                        }
                    } else {
                        isPullingChunks = true;
                        canPullChunks   = false;

                        console.info('tryToPullChunks: nothing to pull, shutting down...');

                        deferredActions = () => {
                            displayRemovableWarning(
                                PRIVATE
                                    ? NO_MESSAGES_HINT
                                    : '¡Nada por acá, publicá primero!'
                            );
                        }

                        if (firstCall) {
                            if (preloader.css('display') != 'none') {
                                preloader.fadeOut(() => {
                                    preloader.hide();

                                    deferredActions();
                                });
                            } else {
                                deferredActions();
                            }
                        }
                    }

                    isPullingChunks = false;
                })
                .catch((error) => {
                    isPullingChunks = false;

                    console.error(error);
                });
            }
        } else {
            console.info('tryToPullChunks: you\'re offline, so you can\'t try to pull chunks now.');
        }
    }
}

function getLastMessageData() {
    return  $('*[data-message]')
                .last()
                .data();
}

function cum() {
    $('*').css({ color: 'white', backgroundColor: 'white' });
}

function tryToDeferAutoTooltip() {
    createMessageBtn = M.Tooltip.init($('#createMessageBtn')[0]);

    $(window).on('load', () => {
        if (typeof(createMessageBtn) != 'undefined' && createMessageBtn != null) {
            createMessageBtn = M.Tooltip.getInstance($('#createMessageBtn')[0]);
            
            if (typeof(createMessageBtn) != 'undefined') {
                createMessageBtn.open();
            }
        }
    });
}

let idleRunner = null;
function initializeIdleRunner() {
    if (idleRunner != null) {
        clearInterval(idleRunner);

        idleRunner = null;
    }

    idleRunner = setTimeout(() => {
        M.TapTarget.getInstance($('.tap-target')).open();

        localStorage.setItem('hasSeenFeatures', true);

        if (document.scrollingElement.scrollTop > 0) {
            $('html, body')[0].scrollTo({ 'top' : 0 });
        }
    }, IDLE_TIMEOUT);
}

function hasSeenFeatures() {
    return localStorage.getItem('hasSeenFeatures') != null;
}

function goBackToTop() {
    $('html, body').each(function () {
        $(this)[0].scrollTo({ top: 0 });
    });
}

function convertMDtoHTML(content) {
    newContent = $(markdownConverter.makeHtml(content));

    newContent.find('p').addClass('light-4');

    return newContent.html();
}

function toggleCommentLike(event, commentId) {
    event.stopPropagation();

    run('commentsManager', 'toggleLike', {
        id: commentId, private: PRIVATE || (typeof(RECIPIENT) != 'undefined' && RECIPIENT != null)
    })
    .then((response) => {
        console.info(response);

        if (response.data.status == OK) {
            likesCountElement = $('[data-comment="' + commentId + '"]').find('.likesCount');

            likesCount = parseInt(likesCountElement.html());

            M.Toast.dismissAll();

            if (response.data.result.wasLiked) {
                toast('Ya no te gusta este comentario.');

                likesCount--;
            } else {
                toast('Te gusta este comentario.');

                likesCount++;
            }

            likesCountElement.html(likesCount);
        } else {
            toast('Algo anda mal, por favor probá otra vez.');
        }
    });
}

function getStatusForNSFW() {
    return (
        typeof(nsfwMode) == 'undefined'
            ? (typeof(NSFW) == 'undefined' ? false : NSFW)
            : nsfwMode
    );
}

$(document).ready(() => {
    // Initialize Day.js
    dayjs.extend(dayjs_plugin_localizedFormat);

    dayjs.locale(
        (window.navigator.userLanguage || window.navigator.language).split('-')[0]
    );

    if (typeof(dayjs.locale()) == 'undefined') {
        dayjs.locale('en'); // Fallback to English.
    }

    // Initialize Showdown
    showdown.setOption('simplifiedAutoLink', true);
    showdown.setOption('strikethrough', true);
    showdown.setOption('openLinksInNewWindow', true);
    showdown.setOption('emoji', true);
    showdown.setOption('underline', true);

    markdownConverter = new showdown.Converter();

    // Grab preloaded CSS.
    $('link[as="style"]')
        .prop('rel', 'stylesheet')
        .removeAttr('as');

    $('[class^="btn-"]').each(function () {
        if ($(this).hasClass('pulse')) {
            let btn = $(this);

            btn.on('click', function () {
                btn.removeClass('pulse');
            });
        }
    });

    if (reportAdImpressions) {
        localStorage.setItem('reportedImpressions', JSON.stringify([]));
    }

    let isReportingImpressions = false;
    let reportedImpressions = [];

    document.addEventListener('scroll', () => {
        // Prevent this error, it doesn't really matter.
        try {
            if ($('.tooltipped').length > 0) {
                $('.tooltipped').each(function () {
                    M.Tooltip.getInstance($(this)[0]).close();
                });
            }
        } catch (exception) {}

        if (reportAdImpressions) {
            $('[data-ad]').each(function () {
                id = $(this).data('ad');

                if (isElementInViewport($(this)[0]) && !isReportingImpressions && !reportedImpressions.includes(id)) {
                    run('adsManager', 'reportImpression', { id: id }, () => {
                        isReportingImpressions = true;
                    }, true)
                    .then((response) => {
                        console.info(response);

                        if (response.data.status == OK) {
                            reportedImpressions.push(id);
                        }
                    })
                    .catch((error) => {
                        console.error(error);
                    })
                    .then(() => { isReportingImpressions = false; });
                }
            });
        }
    }, { passive: true });

    // toast(navigator.userAgent);

    // if (navigator.userAgent.includes('Android')) {
    //     toast('Hey there, it\'s an Android device!');
    //  }

    M.Sidenav.init($('.sidenav')[0], {
        onOpenStart: () => {
            $('.tooltipped').each(function () {
                instance = M.Tooltip.getInstance($(this));

                if (instance != null) {
                    instance.close();
                }
            });
        },
        onCloseEnd: () => {
            createMessageBtn = M.Tooltip.getInstance($('#createMessageBtn')[0]);

            if (createMessageBtn != null && $(createMessageBtn.el).hasClass('pulse')) {
                createMessageBtn.open();
            }
        }
    });

    $('#continueLoginBtn, #tryAccountRecoveryBtn').on('click', (event) => {
        loginPassword = $('#loginPassword');

        loadRecaptcha();

        if (
            event.target.id == 'continueLoginBtn'
            &&
            loginPassword.parent().parent().css('maxHeight') != '85px'
        ) {
            if (loginPassword.val().length > 0) {
                deferLoginPreloader();

                disable($('#tryAccountRecoveryBtn, #continueLoginBtn, #loginMailAddress, #loginPassword'));

                if (typeof(grecaptcha) == 'undefined') {
                    toast('No podemos validar tu sesión, parece que hay problemas con tu conexión.');

                    enable($('#tryAccountRecoveryBtn, #continueLoginBtn, #loginMailAddress, #loginPassword'));

                    return;
                }

                grecaptcha.ready(() => {
                    grecaptcha.execute(RECAPTCHA_PUBLIC_KEY, {action: 'submit'}).then((token) => {
                        run('accountManager', 'tryLogin', {
                            'mailAddress'   : $('#loginMailAddress').val(),
                            'password'      : $('#loginPassword').val(),
                            'token'         : token
                        })
                        .then((response) => {
                            console.log(response);

                            switch (response.data.status) {
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

                                    animateRedirect(
                                        loginTarget == null
                                            ? SYSTEM_HOSTNAME + 'private/?fromLogin'
                                            : loginTarget
                                    );

                                    break;
                            }
                        })
                        .then(() => {
                            enable($('#tryAccountRecoveryBtn, #continueLoginBtn, #loginMailAddress, #loginPassword'));

                            stopPreloader();
                        });
                    })
                    .catch((error) => {
                        console.warn('Failed to complete login due to a reCaptcha v3 server error.\n\nThe following information may help investigate the issue: \n\n' + error);

                        enable($('#tryAccountRecoveryBtn, #continueLoginBtn, #loginMailAddress, #loginPassword'));

                        stopPreloader();
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

                    $('.tooltipped').each(function () {
                        M.Tooltip
                            .getInstance($(this)[0])
                            .close();
                    });

                    disable($('#tryAccountRecoveryBtn, #continueLoginBtn, #loginMailAddress, #loginPassword'));
                })
                .then((response) => {
                    console.log(response);

                    switch (response.data.status) {
                        case BAD_REQUEST:
                            toast('Algo anda mal, probá otra vez.');

                            break;
                        case ALREADY_EXISTS:
                            loginPassword
                                .parent()
                                .parent()
                                .css({ maxHeight: '' });

                            setTimeout(() => {
                                loginPassword = loginPassword[0];

                                loginPassword.focus();
                                loginPassword.click();
                            }, MATERIALIZE_TRANSITION_TIME);

                            $('#continueLoginBtn').html('Iniciar sesión');
                            $('#tryAccountRecoveryBtn').fadeIn();

                            break;
                        case OK:
                            M.Modal.getInstance($('#loginModal')[0]).close();

                            loginMailAddress = $('#loginMailAddress').val();

                            noPasswordLoginModal = $('#noPasswordLoginModal');
                            noPasswordLoginModal.find('.mailAddress').html(loginMailAddress);
                            noPasswordLoginModal.find('.modal-footer a').attr(
                                'href',
                                'https://' + loginMailAddress.split('@')[1]
                            );

                            M.Modal.getInstance(noPasswordLoginModal[0]).open();

                            break;
                        case ERROR:
                            toast('Hubo un problema, probá otra vez.');

                            break;
                    }
                    
                })
                .then(() => {
                    enable($('#tryAccountRecoveryBtn, #continueLoginBtn, #loginMailAddress, #loginPassword'));

                    $('#signupMailAddress').val('');

                    $('.tooltipped').each(function () {
                        M.Tooltip
                            .getInstance($(this)[0])
                            .close();
                    });

                    stopPreloader();
                });
            }
        }
    });

    $('#loginBtn, #loginBtnMobile').on('click', () => {
        M.Sidenav
            .getInstance($('.sidenav')[0])
            .close();

        M.Modal.getInstance($('#loginModal')[0]).open();
    });

    $('.modal').each(function () {
        M.Modal.init($(this)[0], {
            onOpenStart: () => {
                try {
                    $('.tooltipped').each(function () {
                        M.Tooltip
                            .getInstance($(this)[0])
                            .close();
                    });
                } catch (exception) {
                    console.warn('modal/onOpenStart: unable to close tooltips due to the following reason: \n\n', exception);
                }
            },
            onOpenEnd: pushFakeHistory
        });
    });

    M.Modal.init($('#commentsModal')[0], {
        onOpenEnd: () => {
            $('#commentsModal')
                .find('.modal-content')[0]
                .scrollTo({ top: 0 });

            pushFakeHistory();
        }
    });

    M.Modal.init($('#loginModal')[0], {
        onOpenStart: () => {
            $('.tooltipped').each(function () {
                M.Tooltip
                    .getInstance($(this)[0])
                    .close();
            });

            $('#loginForm').css({
                maxHeight: '85px' // MaterializeCSS' default size + margins
            });
        },
        onOpenEnd: () => {
            loginMailAddress = $('#loginMailAddress')[0];

            loginMailAddress.focus();
            loginMailAddress.click();

            pushFakeHistory();
        },
        onCloseEnd: () => {
            $('#loginModal')
                .css({ bottom : '' })
                .find('#loginForm')
                .css({ opacity: 1 })
                .find('.input-field')
                .first()
                .css({ maxHeight: '85px' });

            $('#loginStatus').html('');

            loginMailAddress = $('#loginMailAddress');
            loginMailAddress.val('');
            loginMailAddress.removeClass('valid invalid');

            loginPassword = $('#loginPassword');
            loginPassword.val('');
            loginPassword.removeClass('valid invalid');

            $('#tryAccountRecoveryBtn').hide();
            $('#continueLoginBtn').html('Continuar');

            createMessageBtn = $('#createMessageBtn');

            if (createMessageBtn.length > 0 && $(createMessageBtn.el).hasClass('pulse')) {
                M.Tooltip.getInstance(createMessageBtn).open();
            }

            allowDeferredPreloader = true;
        }
    });

    $('#loginMailAddress, #loginPassword').on('keyup', (event) => {
        if (event.key == 'Enter') {
            $('#continueLoginBtn')[0].click();
        }
    });

    $('#logoutBtn, #logoutBtnMobile').on('click', () => {
        M.Sidenav.getInstance($('.sidenav')[0]).close();

        if (isOnline) {
            run('accountManager', 'tryLogout', undefined)
            .then((response) => {
                if (response.data.status == OK) {
                    animateRedirect(SYSTEM_HOSTNAME);
                }
            });
        } else {
            toast(NO_INTERNET_HINT);
        }
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

    function registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker
                .getRegistrations()
                .then((registrations) => {
                    navigator.serviceWorker
                        .register(SYSTEM_HOSTNAME + 'serviceWorker.js')
                        .then(() => {
                            if (registrations.length > 0) {
                                console.info('serviceWorker: the registered worker is up to date.');
                            } else {
                                console.info('serviceWorker: registration succeded.');
                            }

                            checkIfOnline();
                        })
                        .catch((exception) => {
                            console.warn('We were unable to set up a service worker due to the following reason: \n\n', exception);
                        });
                });
        }
    }

    async function pushLoader() {
        console.log('common/window: success loading assets.');

        $('.custom-link').on('click', function (event) {
            event.preventDefault();

            animateRedirect(
                $(this).attr('href')
            );
        });

        $('input[type="text"], textarea').each(function () {
            if (typeof($(this).attr('data-length')) != 'undefined') {
                M.CharacterCounter.init($(this)[0]);
            }
        });
    
        if ($('.tooltipped').length > 0) {
            $('.tooltipped').each(function () {
                M.Tooltip.init($(this)[0]);
            });

            $('.tooltip-content').addClass('thin');
        }

        M.updateTextFields();

        if ($('textarea').length > 0) {
            M.textareaAutoResize($('textarea'));
        }

        wallpaper = $('.wallpaper');
        wallpaper.attr('src', wallpaper.attr('data-src'));

        $('.material-icons').removeClass('deferred-icon');

        loader();

        if (DO_NOT_TRACK) {
            console.info('common/pushLoader: DNT header found, analytics tracking have been disabled for this session.');
        } else {
            console.info('common/pushLoader: Google Tag Manager will be injected in ' + (IDLE_TIMEOUT / 1000) + ' seconds.');

            setTimeout(() => {
                console.info('common/pushLoader: trying to load Google Tag Manager.');

                var script = null;

                // Global Tag Manager (gtag.js)
                script          = document.createElement('script');
                script.src      = 'https://www.googletagmanager.com/gtag/js?id=' + GOOGLE_ANALYTICS_KEY;
                script.onload   = setupGoogleAnalytics;
                script.onerror  = () => {
                    console.warn('common/pushLoader: cannot track this session, we couldn\'t download GTM. Is the user having strict privacy settings without exposing a DNT header?\n\nFor more information, please refer to the console error above.');
                }

                script.defer    = true;

                document.getElementsByTagName('body')[0].appendChild(script);
            }, IDLE_TIMEOUT);
        }
    }
    
    if (document.readyState != 'complete') {
        $(window).on('load', pushLoader);
    } else {
        pushLoader();
    }

    if (EXCEPTION != null) {
        switch (parseInt(EXCEPTION)) {
            case COMPANY['REMOVAL_SUCCEEDED']:
                toast('¡Listo! Tu empresa fue eliminada.');

                break;
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

    $('.brand-logo').on('click', () => {
        if (document.scrollingElement.scrollTop >= document.documentElement.clientHeight) {
            goBackToTop();
        } else {
            animateRedirect(SYSTEM_HOSTNAME + (getStatusForNSFW() ? 'nsfw' : ''));
        }
    });

    $(window).on('resize', () => {
        viewportThreshold = (VIEWPORT_VISIBLE_THRESHOLD * $(window).height()) / 100;

        $('.tooltipped').each(function () {
            tooltip = M.Tooltip.getInstance($(this));

            if (typeof(tooltip) != 'undefined' && tooltip.isOpen) {
                tooltip.close();
            }
        });
    });

    window.addEventListener('popstate', function (event) {
        let areModalsOpen = false;

        $('.modal').each(function () {
            if ($(this).css('display') != 'none') {
                areModalsOpen = true;
            }
        });

        if (areModalsOpen) {
            $('.modal').each(function () {
                M.Modal.getInstance($(this)[0]).close();
            });

            gotHistoryPushState = false;
        } else {
            history.back();
        }
    });

    window.addEventListener('online', () => {
        checkIfOnline(() => {
            isOnline = true;

            if ($('.toast').length < 1) {
                toast(BACK_ONLINE_HINT);
            }
    
            // This is ugly, I know, but I'm not rewriting that core part.
            document.dispatchEvent(new Event('scroll'));
        });
    });

    window.addEventListener('offline', () => {
        checkIfOnline(() => {}, () => {
            isOnline = false;

            if ($('.toast').length < 1) {
                toast(NO_INTERNET_HINT);
            }
        });
    });

    if (
        !window.matchMedia('(prefers-color-scheme: light)').matches
        &&
        !window.matchMedia('(prefers-color-scheme: dark)').matches
    ) {
        $('style').each(function () {
            $(this).text(
                $(this).text().replace('screen /*--x-light*/', '(prefers-color-scheme: light)')
            );

            $(this).text(
                $(this).text().replace('(prefers-color-scheme: dark)', 'screen /*--x-dark*/')
            );

            $(this).text(
                $(this).text().replace('/*--x-light*/', 'prefers-color-scheme: dark')
            );

            $(this).text(
                $(this).text().replace('prefers-color-scheme: light', '/*--x-dark*/')
            );
        });
    }

    function loadModulesAfterGDPR() {
        if ($('#recentsContainer').length > 0) {
            if (document.readyState == 'complete') {
                tryToPullChunks(true);
            } else {
                $(window).on('load', () => {
                    tryToPullChunks(true);
                });
            }
        }

        if (IS_XMAS) {
            script          = document.createElement('script');
            script.src      = SYSTEM_HOSTNAME + 'assets/js/xmas.min.js';
            script.onload   = () => {
                loadSnowfall();
            }

            document.getElementsByTagName('body')[0].appendChild(script);
        }

        registerServiceWorker();

        if (!hasSeenFeatures()) {
            console.info('FeatureDiscovery will fire in ' + (IDLE_TIMEOUT / 1000) + ' seconds, since we don\'t know if this is a new user.');

            $(window).on('scroll click keyup keydown keypress change wheel', () => {
                if (!hasSeenFeatures()) {
                    initializeIdleRunner();
                }
            });

            initializeIdleRunner();
        }

        let searchParams = new URL(window.location).searchParams;

        if (searchParams.has('then')) {
            switch (searchParams.get('then')) {
                case 'displaySignupModal':
                    if (searchParams.has('onLogin')) {
                        switch (searchParams.get('onLogin')) {
                            case 'setupCompany':
                                loginTarget = '/company';

                                break;
                        }
                    }

                    if (!LOGGED_IN) {
                        if (document.readyState == 'complete') {
                            $('#loginBtnMobile').click();
                        } else {
                            $(window).on('load', () => {
                                $('#loginBtnMobile').click();
                            });
                        }
                    } else if (loginTarget != null) {
                        redirect(loginTarget);
                    }

                    break;
            }
        }

        $('.sidenav-trigger, #nav-mobile, main').fadeIn();

        $('.tap-target').each(function () {
            M.TapTarget.init($(this)[0], {
                onOpen: () => {
                    $('.tap-target-origin').addClass('black-text');
                }
            });
        });
    }

    if (DISPLAY_GDPR_MODAL && localStorage.getItem('acceptedGDPR') == null) {
        main = $('main');

        main.css({ filter: 'blur(0.5rem)' });

        gdprModal = document.getElementById('gdprModal');

        M.Modal.init(gdprModal, { dismissible: false });

        gdprModal = M.Modal.getInstance(gdprModal).open();

        $('#acceptCollectionBtn').on('click', () => {
            localStorage.setItem('acceptedGDPR', true);

            main.css({ transition: MATERIALIZE_TRANSITION_TIME + 'ms filter linear', filter: '' });

            gdprModal.close();

            tryToDeferAutoTooltip();

            toast('¡Listo! Ya podés seguir navegando.');

            loadModulesAfterGDPR();
        });
    } else {
        tryToDeferAutoTooltip();

        loadModulesAfterGDPR();
    }

    function setupNav() {
        let nav = $('nav');

        $('main').css({ 'padding-top' : nav.height() });

        $('.dropdown-button').each(function () {
            M.Dropdown.init($(this)[0], {
                constrainWidth: false,
                hover: false
            });
        });

        userNavBackground = $('.sidenav .user-view .background');

        $(window).on('resize', () => {
            if (
                userNavBackground != null
                &&
                Object.keys(userNavBackground.data()).includes('backgroundUrl')
                &&
                window.innerWidth < 993 // MaterializeCSS' tablet or smaller
            ) {
                userNavBackground.css({
                    'background': 'url(' + userNavBackground.data('background-url') + ')'
                });

                userNavBackground.data('backgroundUrl', null);

                console.log('setupNav: small screen detected, loading user-view background.');
            }
        });

        $(window).trigger('resize');
    }

    if (document.readyState == 'complete') {
        setupNav();
    } else {
        $(window).on('load', setupNav);
    }
});