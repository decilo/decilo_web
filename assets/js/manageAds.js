$(document).ready(() => {
    reportAdImpressions = false;

    let isDirty = false;

    let company = null;

    let onReview = 0;

    pausedWarningCollection     = $('#pausedWarningCollection');
    reviewingWarningCollection  = $('#reviewingWarningCollection');

    directLink                  = $('#directLink');
    directLinkLabel             = $('#directLinkLabel');
    directLinkLabelContainer    = $('#directLinkLabelContainer');

    function attachADsActions() {
        $('button[data-ad]').on('click', (event) => {
            button = $(event.currentTarget);

            $('#confirmAdRemovalBtn').data({ 'ad': button.data('ad') });

            M.Modal.getInstance($('#requestRemovalModal')[0]).open();
        });
    }

    $('select').on('change', function () {
        value = $(this).val();

        COMPANIES.forEach((currentCompany) => {
            if (value == currentCompany.id) {
                company = currentCompany;

                return;
            }
        });
    });

    $('select').trigger('change');

    $('select').each(function () {
        M.FormSelect.init($(this)[0]);
    });

    $('.select-dropdown')
        .addClass('light-4 dark-5')
        .find('span')
        .addClass('light-4 dark-5');

    $('input').each(function () {
        $(this).on('keyup keydown change', () => {
            content = $('#content').val();

            directLinkLabel = $('#directLinkLabel');

            directLinkVal   = directLink.val();
            directLinkLabel = directLinkLabel.val();

            $('#adPreview').html(
                getRenderedAd(
                    null,
                    content.length > 0 ? content : 'Contenido del anuncio',
                    company['name'],
                    null,
                    null,
                    's12 m8 offset-m2',
                    { bgClass: 'bg-dark-10 bg-light-11', text: 'Publicidad' },
                    0,
                    false,
                    directLinkVal.length > 0 ? directLinkVal : null,
                    directLinkLabel.length > 0 ? directLinkLabel : null
                )
            );

            // if (
            //     $(this).attr('id') == 'directLink'
            //     ||
            //     $(this).attr('id') == 'directLink'
            // )
        });
    });

    $('#content').on('keyup change', function () {
        if ($(this).val().length > 0) {
            markValid($(this));

            isDirty = true;
        } else {
            isDirty = false;

            markInvalid($(this));
        }
    });

    $('#createAdBtn').on('click', () => {
        content = $('#content');

        directLinkLabel = $('#directLinkLabel');

        directLinkVal   = directLink.val();

        if (directLinkVal.length < 1) {
            directLinkVal = null;
        }

        directLinkLabel = directLinkLabel.val();

        if (directLinkLabel.length < 1) {
            directLinkLabel = null;
        }

        if (content.hasClass('valid')) {
            run('adsManager', 'createAd', {
                'company':          company['id'],
                'content':          content.val(),
                'directLink':       directLinkVal,
                'directLinkLabel':  directLinkLabel
            }, () => {
                disable($('#createAdBtn'));
            })
            .then((response) => {
                console.info(response);

                switch (response.data.status) {
                    case OK:
                        container = $('.gridContainer');

                        container
                            .find('.removableWarning')
                            .fadeOut(() => {
                                container.prepend(
                                    getRenderedAd(
                                        response.data.result.id,
                                        content.val(),
                                        company['name'],
                                        null,
                                        null,
                                        's12 onReview',
                                        company['isBillingEnabled'] == 1
                                            ? { bgClass: 'orange darken-3 dark-5 light-10',     text: 'En revisión' }
                                            : { bgClass: 'light-blue darken-2 dark-5 light-10', text: 'En pausa'    },
                                        null,
                                        true,
                                        directLinkVal,
                                        directLinkLabel
                                    )
                                );

                                reloadLayout();

                                content
                                    .val('')
                                    .removeClass('valid invalid');

                                if (company['isBillingEnabled'] == 1) {
                                    reviewingWarningCollection
                                        .show()
                                        .parent()
                                        .fadeIn();

                                    onReview++;

                                    collection = $('#reviewingWarningCollection');

                                    collection
                                        .find('.reviewingHint')
                                        .html(onReview == 1 ? 'un' : onReview);

                                    if (onReview > 1) {
                                        collection
                                            .find('.plurals')
                                            .show();
                                    }
                                } else {
                                    pausedADs = $('.message[data-ad]');

                                    filteredPausedADs = [];

                                    pausedADs.each(function () {
                                        if ($(this).data('ad') != null) {
                                            filteredPausedADs.push($(this));
                                        }
                                    });

                                    if (filteredPausedADs.length > 1) {
                                        pausedWarningCollection.find('.plurals').show();
                                    } else {
                                        pausedWarningCollection.find('.plurals').hide();
                                    }

                                    pausedWarningCollection.parent().fadeIn();
                                }

                                attachADsActions();

                                goBackToTop();
                            });

                        break;
                    case ERROR:
                        toast('Algo salió mal, por favor probá de nuevo.');

                        break;
                    case NOT_ALLOWED:
                        toast('No podés hacer eso, por favor probá recargando la página.');

                        break;
                    case NO_SUCH_ELEMENT:
                        toast('La empresa que seleccionaste no existe o no te pertenece.');

                        break;
                    default:
                        toast('Algo salió mal, por favor probá de nuevo.');
                }
            })
            .then(() => {
                enable($('#createAdBtn'));
            });
        } else {
            content.addClass('invalid');

            toast('No podés crear un anuncio vacío.');
        }
    });

    window.onbeforeunload = function () {
        return isDirty
            ? 'Si cerrás esta pestaña, vas a perder lo que estabas haciendo.'
            : null;
    }

    if (company['isBillingEnabled'] != 1) {
        pausedWarningCollection
            .show()
            .parent()
            .show();
    }

    if (ADS.length > 0) {
        let adSet = '';

        ADS.forEach((ad) => {
            adSet += getRenderedAd(
                ad['id'],
                ad['content'],
                ad['companyName'],
                ad['created'],
                null,
                's12 ' + (
                    company['isBillingEnabled'] == 1 && ad['approved'] == null
                        ? 'onReview'
                        : ''
                ),
                company['isBillingEnabled'] == 1
                    ? (
                        ad['approved'] == null
                            ? { bgClass: 'orange darken-3 dark-5 light-10', text: 'En revisión' }
                            : (
                                ad['approved'] == 1
                                    ? { bgClass: 'green darken-2 dark-5 light-10', text: 'Publicado'   }
                                    : { bgClass: 'red dark-5 light-10'           , text: 'No aprobado' }
                            )
                    )
                    : { bgClass: 'light-blue darken-2 dark-5 light-10', text: 'En pausa' },
                ad['approved'] == null
                    ? null
                    : (
                        ad['approved'] == 1
                            ? ad['impressions']
                            : null
                    ),
                true,
                ad['directLink'],
                ad['directLinkLabel']
            );

            if (ad['approved'] == null) {
                onReview++;
            }
        });

        if (company['isBillingEnabled'] == 1 && onReview > 0) {
            let collection = $('#reviewingWarningCollection');

            collection
                .show()
                .parent()
                .show();

            collection
                .find('.reviewingHint')
                .html(onReview == 1 ? 'un' : onReview)

            if (onReview > 1) {
                collection
                    .find('.plurals')
                    .show();
            }
        }

        container = $('.gridContainer');

        container
            .find('.removableWarning')
            .remove();

        container
            .find('.row')
            .append(adSet);

        if (ADS.length > 1) {
            pausedWarningCollection.find('.plurals').show();
        }

        attachADsActions();

        reloadLayout();
    } else {
        pausedWarningCollection.parent().hide();

        displayRemovableWarning('¡Nada por acá! Publicá tu primer anuncio.');
    }

    $('#confirmAdRemovalBtn').on('click', (event) => {
        requestRemovalModal = M.Modal.getInstance($('#requestRemovalModal')[0]);

        button  = $(event.currentTarget);
        ad      = button.data('ad');

        if (ad != -1 && !isNaN(parseInt(ad))) {
            run('adsManager', 'tryToRemoveAd', { 'id': ad }, () => {
                disable(button);

                button.html('Eliminando');
            })
            .then((response) => {
                console.info(response);

                switch (response.data.status) {
                    case OK:
                        toast('¡Listo! El anuncio fue eliminado.');

                        target = $('.message[data-ad="' + ad + '"]');

                        target.fadeOut(() => {
                            target.remove();

                            reloadLayout();

                            if (company['isBillingEnabled'] == 1) {
                                onReview = $('.onReview').length;

                                adList = $('.message[data-ad]');

                                filteredAdList = [];

                                adList.each(function () {
                                    if ($(this).data('ad') != null) {
                                        filteredAdList.push($(this));
                                    }
                                });

                                if (filteredAdList.length == 0) {
                                    reviewingWarningCollection.parent().fadeOut(() => {
                                        reviewingWarningCollection.parent().hide();

                                        displayRemovableWarning('¡Nada por acá! Publicá tu primer anuncio.');

                                        $('.gridContainer')
                                            .find('.removableWarning')
                                            .fadeIn();
                                    });
                                } else if (onReview == 0) {
                                    reviewingWarningCollection.fadeOut(() => {
                                        if (!pausedWarningCollection.css('display') != 'none') {
                                            reviewingWarningCollection.parent().hide();
                                        }
                                    });
                                } else {
                                    reviewingWarningCollection.find('.reviewingHint').html(
                                        onReview == 1 ? 'un' : onReview
                                    );

                                    plurals = reviewingWarningCollection.find('.plurals');

                                    if (onReview > 1) {
                                        plurals.show();
                                    } else {
                                        plurals.hide();
                                    }
                                }
                            } else {
                                pausedADs = $('.message').filter('[data-ad]');

                                let filteredPausedADs = [];

                                pausedADs.each(function () {
                                    if ($(this).data('ad') != null) {
                                        filteredPausedADs.push($(this));
                                    }
                                });

                                if (filteredPausedADs.length > 1) {
                                    pausedWarningCollection.find('.plurals').show();
                                } else if (pausedADs == 1) {
                                    pausedWarningCollection.find('.plurals').hide();
                                } else {
                                    pausedWarningCollection.parent().fadeOut();

                                    displayRemovableWarning('¡Nada por acá! Publicá tu primer anuncio.');

                                    $('.gridContainer')
                                        .find('.removableWarning')
                                        .fadeIn();
                                }
                            }
                        });

                        break;
                    case ERROR:
                        toast('Algo salió mal, por favor probá de nuevo.');

                        break;
                    case NO_SUCH_ELEMENT:
                        toast('El anuncio que intentaste eliminar no existe.');

                        break;
                    case NOT_ALLOWED:
                        toast('No tenés permitido eliminar anuncios, probá recargando la página.');

                        break;
                }
            })
            .then(() => {
                enable(button);

                requestRemovalModal.close();

                setTimeout(() => {
                    button.html('Eliminar');
                }, MATERIALIZE_TRANSITION_TIME);
            });
        } else {
            toast('Algo anda mal, por favor probá de nuevo.');

            requestRemovalModal.close();
        }
    });

    directLink.on('change keyup', (event) => {
        target = $(event.target);

        if (target.val().length > 0) {
            if (directLinkLabelContainer.css('display') == 'none') {
                directLinkLabelContainer.fadeIn();
            }
        } else {
            directLinkLabelContainer.fadeOut();
        }
    });

    $('input.select-dropdown').trigger('change');
});