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

const FONTS = [
    {
        family:  'Lato',
        weight:  300,
        src:     'url(https://fonts.gstatic.com/s/lato/v17/S6u9w4BMUTPHh7USSwaPGR_p.woff2) format(\'woff2\')'
    },
    {
        family:  'Lato',
        weight:  300,
        src:     'url(https://fonts.gstatic.com/s/lato/v17/S6u9w4BMUTPHh7USSwiPGQ.woff2) format(\'woff2\')'
    },
    {
        family:  'Lato',
        weight:  400,
        src:     'url(https://fonts.gstatic.com/s/lato/v17/S6uyw4BMUTPHjxAwXjeu.woff2) format(\'woff2\')'
    },
    {
        family:  'Lato',
        weight:  400,
        src:     'url(https://fonts.gstatic.com/s/lato/v17/S6uyw4BMUTPHjx4wXg.woff2) format(\'woff2\')'
    },
    {
        family:  'Lato',
        weight:  700,
        src:     'url(https://fonts.gstatic.com/s/lato/v17/S6u9w4BMUTPHh6UVSwaPGR_p.woff2) format(\'woff2\')'
    },
    {
        family:  'Lato',
        weight:  700,
        src:     'url(https://fonts.gstatic.com/s/lato/v17/S6u9w4BMUTPHh6UVSwiPGQ.woff2) format(\'woff2\')'
    },
    {
        family:  'Roboto',
        weight:  300,
        src:     'url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmSU5fCRc4EsA.woff2) format(\'woff2\')'
    },
    {
        family:  'Roboto',
        weight:  300,
        src:     'url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmSU5fABc4EsA.woff2) format(\'woff2\')'
    },
    {
        family:  'Roboto',
        weight:  300,
        src:     'url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmSU5fCBc4EsA.woff2) format(\'woff2\')'
    },
    {
        family:  'Roboto',
        weight:  300,
        src:     'url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmSU5fBxc4EsA.woff2) format(\'woff2\')'
    },
    {
        family:  'Roboto',
        weight:  300,
        src:     'url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmSU5fCxc4EsA.woff2) format(\'woff2\')'
    },
    {
        family:  'Roboto',
        weight:  300,
        src:     'url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmSU5fChc4EsA.woff2) format(\'woff2\')'
    },
    {
        family:  'Roboto',
        weight:  300,
        src:     'url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmSU5fBBc4.woff2) format(\'woff2\')'
    },
    {
        family:  'Roboto',
        weight:  400,
        src:     'url(https://fonts.gstatic.com/s/roboto/v20/KFOmCnqEu92Fr1Mu72xKOzY.woff2) format(\'woff2\')'
    },
    {
        family:  'Robot',
        weight:  400,
        src:     'url(https://fonts.gstatic.com/s/roboto/v20/KFOmCnqEu92Fr1Mu5mxKOzY.woff2) format(\'woff2\')'
    },
    {
        family:  'Roboto',
        weight:  700,
        src:     'url(https://fonts.gstatic.com/s/roboto/v20/KFOlCnqEu92Fr1MmWUlfCRc4EsA.woff2) format(\'woff2\')'
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

                $('body').fadeIn(() => {}, 1, 50);
            }
        });
});

jQuery.fn.fadeIn = function(callback = () => {}, opacity = 1, duration = MATERIALIZE_TRANSITION_TIME) {
    $(this).css({ display: '', opacity: opacity });

    setTimeout(callback, duration);
}

jQuery.fn.fadeOut = function(callback = () => {}, opacity = 0) {
    element = $(this);

    element.animate({ opacity: opacity });

    setTimeout(() => {
        if (element.css('display') != 'none') {
            element.data('data-display', element.css('display'));
        }

        element.css({ display: 'none' });

        callback();
    }, MATERIALIZE_TRANSITION_TIME);
}

function displayNoInternetFAB() {
    $('#noInternetBtn')
        .css({ oapcity: 0, display: 'block'})
        .animate({ opacity: 0.5 });
}

function run(url, action, values, before = () => {}, overridesFailure = false, xhr = () => {
    return $.ajaxSettings.xhr();
}) {
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
            ),
            xhr: xhr
        }
    )
    .done((response, status, xhr) => {
        if (!overridesFailure && !xhr.getResponseHeader('content-type').includes('json')) {
            console.error(response);

            if ($('.toast').length < 1) {
                if (isOnline) {
                    toast('Algo salió mal, por favor probá otra vez.');
                } else {
                    toast(NO_INTERNET_HINT);
                }
            }
        }
    })
    .fail((error) => {
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

        sidenav = $('.sidenav');

        if (sidenav.length > 0) {
            sidenav = M.Sidenav.getInstance(sidenav);

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

            setupInstance();

            $('.tooltipped').tooltip();
        }

        calculateOnscreenImages();

        $('#recentsContainer')
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
    } else {
        console.warn('Cannot update layout, Masonry isn\'t ready.');
    }
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

function isElementInViewport(element) {
    element        = $(element);

    elementTop     = element.offset().top;
    elementBottom  = elementTop + element.outerHeight();

    viewportTop    = $(window).scrollTop();
    viewportBottom = viewportTop + $(window).height();

    return elementBottom > (viewportTop - viewportThreshold) && (elementTop - viewportThreshold) < viewportBottom;
}

function getRenderedComment(id = null, declaredName = null, content, active = false) {
    return  `<li data-comment="` + (id == null ? 'null' : id) + `" ` + (active ? 'class="active"' : '') + `>
                <div class="collapsible-header bg-dark-7 border-dark-8">
                    ` + (declaredName == null ? 'Anónimo' : declaredName) + ` • <span class="thin add-space"> ` + dayjs().format('L LT') +` </span>
                </div>
                <div class="collapsible-body bg-dark-7 border-dark-8">
                    <span class="thin word-wrap process-whitespaces overflow-ellipsis">` + content + `</span>
                </div>
             </li>`;
}

function openCommentsModal(message, private) {
    loadRecaptcha();

    $('#commentsModal').modal('open');

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
                    card.find('.card-content').find('p[class*="message-content"]').text().trim() + `
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
                <button id="sendCommentBtn" type="button" class="btn waves-effect waves-light col right btn-block bg-light-1 bg-dark-1 dark-5 fixed-width-btn fixed-height-btn">
                    Enviar
                </button>
            </div>
         </div>

         <ul id="commentsCollapsible" class="collapsible border-dark-8" style="display: none;"> </ul>`
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
                    private:        private
                }, () => {
                    disable($('#commentInput, #commentDeclaredName, #sendCommentBtn'));
                })
                .done((response) => {
                    console.info(response);

                    if (response.status == OK) {
                        console.info('postComment: alright, we got a response!');

                        $('#commentInput, #commentDeclaredName')
                            .val('')
                            .change();

                        console.info('postComment: now, we\'ll clean up those inputs.');

                        commentsCollapsible = $('#commentsCollapsible');

                        instance = M.Collapsible.getInstance($('#commentsCollapsible'));

                        for (let index = 0; index < commentsCollapsible.find('li').length; index++) {
                            instance.close(index);
                        }

                        console.info('postComment: ok, we have re-initialized the collapsible and closed its items.');

                        commentsCollapsible
                            .find('.active')
                            .removeClass('active');

                        commentsCollapsible.prepend(
                            getRenderedComment(response.result.id, declaredName, content)
                        );

                        console.info('postComment: cool, we got the DOM changed.');
                        
                        // Reset the container and the instance because of reasons.
                        commentsCollapsible.collapsible({ accordion: false });
                        commentsCollapsible = $('#commentsCollapsible');

                        instance = M.Collapsible.getInstance($('#commentsCollapsible'));

                        instance.open(0);
                        
                        console.info('postComment: so we\'ve just re-initialized the instance back again, this is getting frustrating.');

                        firstComment = commentsCollapsible.find('li').first();

                        $('#commentsModal')
                            .find('.modal-content')
                            .animate({
                                scrollTop:
                                    firstComment.offset()['top']
                                    -
                                    firstComment.find('.collapsible-header').height()
                            });

                        commentCount = $('.message[data-message=' + message + ']').find('.commentCount');
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
                .always(() => {
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

    commentsCollapsible = $('#commentsCollapsible');
    commentsCollapsible.collapsible({ accordion: false });

    if (isOnline) {
        run('commentsManager', 'getComments', {
            message: message,
            private: private
        })
        .done((response) => {
            console.info(response);

            commentsCollapsible = $('#commentsCollapsible');

            switch (response.status) {
                case OK:
                    let renderedHTML = '';

                    response.result.forEach((comment) => {
                        renderedHTML += getRenderedComment(comment['id'], comment['declaredName'], comment['content']);
                    });

                    commentsCollapsible.append(renderedHTML);
                    commentsCollapsible.slideDown();

                    commentsCollapsible.collapsible({ accordion: false });
            
                    if (commentsCollapsible.find('li').length > 0) {
                        M.Collapsible
                            .getInstance($('#commentsCollapsible'))
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

function loadRecaptcha(defer = false, async = false) {
    if (isOnline && typeof(grecaptcha) == 'undefined') {
        // Google reCaptcha v3
        script          = document.createElement('script');
        script.src      = 'https://www.google.com/recaptcha/api.js?render=' + RECAPTCHA_PUBLIC_KEY;
        script.onload   = () => {
            console.log('reCaptcha v3: successfully loaded.');

            loader();
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
    }
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

function getRenderedAd(id, content, companyName, created = null, image = null) {
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

    return `<div class="col ` + (isPrivate ? 's12 m12 l6' : 's12 m6 l3') + ` message" data-ad="` + id + `">
                <div class="card bg-dark-3 card-box">` + (image == null ? '' : `
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
                            (companyName == null ? 'Anónimo' : companyName) + ` <span class="badge bg-dark-10 bg-light-11 ads-badge-text"> Publicidad </span>
                        </span>
                        <p class="lato thin word-wrap process-whitespaces overflow-ellipsis message-content light-4">` + 
                            (auxiliaryContent.length > MESSAGES['MAX_LENGTH'] ? auxiliaryContent.substr(0, MESSAGES['MAX_LENGTH']) + '…' : auxiliaryContent) + `
                        </p>
                    </div>
                    <div class="card-action center">
                        <span class="lato regular small">` + dayjs(created == null ? new Date() : created).format('L LT') + `</span>
                    </div
                </div>
            </div>`;
}

function tryToPushRandomAd() {
    run('adsManager', 'getRandomAd', {}, () => {}, true)
    .done((response) => {
        console.info(response);

        messages = $('.message');

        if (messages.length > 0) {
            if (response.result) {
                messages.eq(
                    Math.round(
                        Math.random() * (messages.length - 1)
                    )
                ).after(
                    getRenderedAd(response['result']['id'], response['result']['content'], response['result']['companyName'])
                );

                reloadLayout();
            } else {
                console.warn('tryToPushRandomAd: unable to push, unexpected response.');
            }
        } else {
            console.info('tryToPushRandomAd: there are no messages in this view, this is a sad moment for advertisers. :(');
        }
    })
    .fail((error) => {
        console.error(error);
    });
}

function getLastMessageId() {
    return  $('*[data-message]')
                .last()
                .data('message');
}

function cum() {
    $('*').animate({ color: 'white', backgroundColor: 'white' });
}

function tryToDeferAutoTooltip() {
    fabToggleBtn = $('#fabToggleBtn');
    fabToggleBtn.tooltip();

    $(window).on('load', () => {
        if (fabToggleBtn.length > 0) {
            fabToggleBtn.tooltip('open');
        }
    });
}

function loadSnowfall() {
    script          = document.createElement('script');
    script.src      = 'https://rawcdn.githack.com/loktar00/JQuery-Snowfall/d22ba78f76804e21404bc000142c019d6c10973d/dist/snowfall.jquery.min.js';
    script.defer    = true;
    script.onload   = () => {
        console.info('Snowfall: loaded successfully.');

        $('nav').snowfall({
            flakeCount  : SNOWFALL['FLAKE_COUNT'],
            maxSpeed    : SNOWFALL['MAX_SPEED']
        });
    }

    document
        .getElementsByTagName('body')[0]
        .appendChild(script);
}

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

$(document).ready(() => {
    // Initialize Day.js
    dayjs.extend(dayjs_plugin_localizedFormat);

    dayjs.locale(
        (window.navigator.userLanguage || window.navigator.language).split('-')[0]
    );

    if (typeof(dayjs.locale()) == 'undefined') {
        dayjs.locale('en'); // Fallback to English.
    }

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

    localStorage.setItem('reportedImpressions', JSON.stringify([]));

    let isReportingImpressions = false;
    let reportedImpressions = [];

    document.addEventListener('scroll', () => {
        // Prevent this error, it doesn't really matter.
        try {
            if ($('.tooltipped').length > 0) {
                $('.tooltipped').tooltip('close');
            }
        } catch (exception) {}

        $('[data-ad]').each(function () {
            id = $(this).data('ad');

            if (isElementInViewport($(this)[0]) && !isReportingImpressions && !reportedImpressions.includes(id)) {
                run('adsManager', 'reportImpression', { id: id }, () => {
                    isReportingImpressions = true;
                }, true)
                .done((response) => {
                    console.info(response);

                    if (response.status == OK) {
                        reportedImpressions.push(id);
                    }
                })
                .fail((error) => {
                    console.error(error);
                })
                .always(() => { isReportingImpressions = false; });
            }
        });
    }, { passive: true });

    // toast(navigator.userAgent);

    // if (navigator.userAgent.includes('Android')) {
    //     toast('Hey there, it\'s an Android device!');
    //  }

    function goBackToTop() {
        $('html, body').scrollTop(0);
    }

    $('.sidenav').sidenav({
        onOpenStart: () => {
            tooltips = $('.tooltipped');

            tooltips.tooltip();
            tooltips.tooltip('close');
        },
        onCloseEnd: () => {
            fabToggleBtn = $('#fabToggleBtn');

            if (fabToggleBtn.length > 0 && fabToggleBtn.hasClass('pulse')) {
                fabToggleBtn.tooltip('open');
            }
        }
    });

    $('#continueLoginBtn, #tryAccountRecoveryBtn').on('click', (event) => {
        loginPassword = $('#loginPassword');

        loadRecaptcha();

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

                    $('.tooltipped').tooltip('close');

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

                                loginModal
                                    .find('.input-field')
                                    .first()
                                    .animate({ height: 0 });

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

                    $('.tooltipped').tooltip('close');

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

    $('#commentsModal').modal({
        onOpenEnd: () => {
            $('#commentsModal')
                .find('.modal-content')
                .scrollTop(0);
        }
    });

    $('#loginModal').modal({
        onOpenStart: () => {
            $('.tooltipped').tooltip('close');
        },
        onOpenEnd: () => {
            $('#loginMailAddress')
                .focus()
                .click();
        },
        onCloseEnd: () => {
            $('#loginModal')
                .css({ bottom : '' })
                .find('#loginForm')
                .css({ opacity: 1 })
                .find('.input-field')
                .first()
                .css({ height: '' });

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

            fabToggleBtn = $('#fabToggleBtn');

            if (fabToggleBtn.length > 0 && fabToggleBtn.hasClass('pulse')) {
                fabToggleBtn.tooltip('open');
            }

            allowDeferredPreloader = true;
        }
    });

    $('#loginMailAddress, #loginPassword').on('keyup', (event) => {
        if (event.key == 'Enter') {
            $('#continueLoginBtn').click();
        }
    });

    $('#logoutBtn, #logoutBtnMobile').on('click', () => {
        $('.sidenav').sidenav('close');

        if (isOnline) {
            run('accountManager', 'tryLogout', undefined)
            .done((response) => {
                if (response.status == OK) {
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
                $(this).characterCounter();
            }
        });
    
        if ($('.tooltipped').length > 0) {
            $('.tooltipped').tooltip();

            $('.tooltip-content').addClass('thin');
        }

        M.updateTextFields();

        if ($('textarea').length > 0) {
            M.textareaAutoResize($('textarea'));
        }

        wallpaper = $('.wallpaper');
        wallpaper.attr('src', wallpaper.attr('data-src'));

        loader();

        tryToPushRandomAd();

        setTimeout(() => {
            var script = null;

            // Global Tag Manager (gtag.js)
            script          = document.createElement('script');
            script.src      = 'https://www.googletagmanager.com/gtag/js?id=' + GOOGLE_ANALYTICS_KEY;
            script.onload   = setupGoogleAnalytics;

            script.defer    = true;

            document.getElementsByTagName('body')[0].appendChild(script);
        }, IDLE_TIMEOUT);
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

    $('.brand-logo').on('click', () => {
        if ($(window).scrollTop() >= document.documentElement.clientHeight) {
            goBackToTop();
        } else {
            animateRedirect(SYSTEM_HOSTNAME);
        }
    });

    $(window).on('resize', () => {
        viewportThreshold = (VIEWPORT_VISIBLE_THRESHOLD * $(window).height()) / 100;
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
        if (typeof(loadPreloadedRecents) != 'undefined') {
            if (document.readyState == 'complete') {
                loadPreloadedRecents();
            } else {
                $(window).on('load', loadPreloadedRecents);
            }
        }

        if (IS_XMAS) {
            loadSnowfall();
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

        nav.pushpin();

        $('main').css({ 'padding-top' : nav.height() });

        $('.dropdown-button').each(function () {
            M.Dropdown.init($(this)[0], {
                constrainWidth: false,
                hover: false
            });
        });
    }

    if (document.readyState == 'complete') {
        setupNav();
    } else {
        $(window).on('load', setupNav);
    }
});