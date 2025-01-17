$(document).ready(() => {
    dayjs.locale('es');

    /*
     * This code should be based off constants and all that stuff, 
     * but let's face it, the author of this library will make it 100%
     * responsive at some point, and the time spent on coding it myself
     * would've been pointless. That said, enjoy your spaghetti.
     */

    const CARD_WIDTH = window.innerWidth <= 600 // mobile
        ? (
            $('.container').width() - (
                parseFloat(
                    $('#billingForm').css('paddingLeft').replace('px', '')
                )
            * 13)
        )
        : (
            $('.container').width() / 2
        ) - (
            parseFloat(
                $('#billingForm').css('paddingLeft').replace('px', '')
            )
        * 13.5);

    let company = null;

    let paymentMethodId = null;
    let issuerBank = null;
    let installments = null;

    let isProcessingTransaction = false;

    paymentMethodsContainer = $('#paymentMethodsContainer');

    function getRenderedSubscription(internalId, active = true, modified = null) {
        if (!active && modified != null)  {
            modified = dayjs(modified);
        }

        return `
        <li class="collection-item avatar bg-dark-12 ` + (active ? `` : `half-opacity`) + `">
            <i class="material-icons deferred-icon circle bg-dark-1 bg-light-1"> payment </i>

            <span class="title"> <b> Subscripción ` + (active ? `activa` : `cancelada`) + ` </b> </span>

            <p>
                ` + (active ? `Estás pagando` : `Pagabas`) + ` ` + MERCADOPAGO_SUBSCRIPTION_COST + ` AR$ por mes. <br>
                ` + (active
                        ? `Podés darte de baja cuando quieras`
                        : `Cancelaste tu subscripción` +
                            (modified == null
                                ? ``
                                : ` el ` + modified.format('LL') + ' a las ' + modified.format('HH:MM')
                            )
                    ) + `.  <br>` + (active ? `
                <br>
                <a class="hand" data-subscription-id="` + internalId + `">
                    Cancelar subscripción
                </a>` : ``) + `
            </p>
        </li>`;
    }

    function attachProgressBar() {
        saveBillingMethodBtn = $('#saveBillingMethodBtn');

        $('#transactionProgressBar').css({
            'top'    : saveBillingMethodBtn.position()['top'],
            'left'   :
                saveBillingMethodBtn.position()['left']
                +
                parseFloat(saveBillingMethodBtn.css('marginLeft').replace('px', '')),
            'height' : saveBillingMethodBtn.height()
        });
    }

    function attachSubscriptionsActions() {
        paymentMethodsContainer.find('a').on('click', (event) => {
            run('companyManager', 'cancelSubscription', {
                id: $(event.currentTarget).data('subscription-id')
            }, () => {
                $(event.currentTarget)
                    .addClass('grey-text')
                    .html('Cancelando subscripción...');
            })
            .then((response) => {
                console.info(response);

                switch (response.data.status) {
                    case OK:
                        paymentMethodsContainer.find('.collection').fadeOut(() => {
                            $(event.currentTarget)
                                .parent()
                                .parent()
                                .remove();

                            toast('Tu subscripción fue cancelada con éxito.');

                            $('#billingForm').fadeIn();

                            $('#alreadySubscribedCollection').fadeOut(() => {
                                $('#tariffsInformationCollection, #trialInformationCollection').fadeIn();
                            });
    
                            displayRemovableWarning('No agregaste ningún método de pago.', paymentMethodsContainer);
                        });

                        break;
                    case ERROR:
                        toast('No pudimos cancelar tu subscripción, por favor probá de nuevo o contactános.');

                        $(event.currentTarget)
                            .html('Cancelar subscripción')
                            .removeClass('grey-text');
                }
            });
        });
    }

    function initializeSelects() {
        $('select').each(function () {
            M.FormSelect.init($(this)[0]);
        });

        $('.select-dropdown')
            .addClass('light-4 dark-5 valid')
            .find('span')
            .addClass('light-4 dark-5');
    }

    initializeSelects();

    $('#billingForm').card({
        // a selector or DOM element for the container
        // where you want the card to appear
        container: '.card-wrapper', // *required*

        formSelectors: {
            numberInput: 'input#cardNumber',
            expiryInput: 'input#cardExpiration',
            cvcInput: 'input#cardCVC',
            nameInput: 'input#cardFullName'
        },

        width: CARD_WIDTH, // optional — default 350px
        formatting: true, // optional - default true

        // Strings for translation - optional
        messages: {
            validDate: 'fecha de\nexpiración', // optional - default 'valid\nthru'
            monthYear: 'mm/yy', // optional - default 'month/year'
        },

        // Default placeholders for rendered fields - optional
        placeholders: {
            number: '•••• •••• •••• ••••',
            name: 'Nombre completo',
            expiry: '••/••',
            cvc: '•••'
        },

        masks: {
            cardNumber: '•' // optional - mask card number
        }
    });

    function validateCardCVC(cvc, target) {
        if (
            $.payform.validateCardCVC(
                cvc,
                $.payform.parseCardType(
                    $('#cardNumber').val()
                )
            )
        ) {
            markValid(target);

            return true;
        } else {
            markInvalid(target);

            return false;
        }
    }

    $('#billingForm input, #billingForm select').on('keyup change input', function () {
        value = $(this).val();

        switch ($(this).attr('id')) {
            case 'companySelect':
                COMPANIES.forEach((currentCompany) => {
                    if (value == currentCompany.id) {
                        company = currentCompany;

                        return;
                    }
                });

                break;
            case 'cardNumber':
                cardNumber = value.replaceAll(' ', '');

                if ($.payform.validateCardNumber(cardNumber)) {
                    $('input[data-checkout="cardNumber"]')
                        .val(cardNumber)
                        .addClass('valid');

                    markValid($(this));

                    cardCVC = $('#cardCVC');

                    if (cardCVC.val().length > 0) {
                        validateCardCVC(cardCVC.val(), cardCVC);
                    }

                    $('#cardExpiration')[0].focus();
                } else {
                    markInvalid($(this));
                }

                break;
            case 'cardExpiration':
                expiration = value.replaceAll(' ', '').split('/');

                /*
                 * expiration
                 *  0 => month
                 *  1 => year
                 */

                if ($.payform.validateCardExpiry(expiration[0], expiration[1])) {
                    $('#cardExpirationMonth').val(expiration[0]).addClass('valid');
                    $('#cardExpirationYear').val(expiration[1]).addClass('valid');

                    markValid($(this));

                    $('#cardCVC')[0].focus();
                } else {
                    $('#cardExpirationMonth').removeClass('valid');
                    $('#cardExpirationYear').removeClass('valid');

                    markInvalid($(this));
                }

                break;
            case 'cardCVC':
                if (validateCardCVC(value, $(this))) {
                    $('#cardFullName')[0].focus();
                }

                break;
            case 'cardFullName':
                if (value.length > 0) {
                    markValid($(this));
                } else {
                    markInvalid($(this));
                }

                break;
            case 'mailAddress':
                if (isMailAddressValid(value.trim())) {
                    markValid($(this));
                } else {
                    markInvalid($(this));
                }

                break;
            case 'documentTypeSelect':
                markValid($(this));

                break;
            case 'documentNumber':
                documentNumber = value.trim().replaceAll('.', '');

                if (
                    documentNumber.length < 11
                    &&
                    documentNumber.length > 7
                    &&
                    !isNaN(parseInt(documentNumber))
                ) {
                    markValid($(this));
                } else {
                    markInvalid($(this));
                }

                break;
        }

        if (
            $('#billingForm input').length
            ==
            $('#billingForm input').filter('.valid').length
        ) {
            if (!$('#saveBillingMethodBtn').hasClass('pulse')) {
                $('#saveBillingMethodBtn').addClass('pulse')[0].focus();
            }
        } else {
            if ($('#saveBillingMethodBtn').hasClass('pulse')) {
                $('#saveBillingMethodBtn').removeClass('pulse');
            }
        }
    });

    $('#documentNumber').on('keydown', (event) => {
        if (
            isNaN(parseInt(event.key))
            &&
            (
                event.key != '.'
                ||
                event.currentTarget.value.split('.').length > 2
            )
            &&
            event.key != 'Backspace'
        ) {
            event.preventDefault();
        }
    });

    let hasActiveSubscription = false;

    SUBSCRIPTIONS.forEach((subscription) => {
        if (subscription['active'] == 1) {
            hasActiveSubscription = true;
        }
    });

    renderedHTML = '';

    SUBSCRIPTIONS.forEach((subscription) => {
        renderedHTML += getRenderedSubscription(
            subscription['id'],
            subscription['active'] == 1,
            subscription['modified']
        );
    });

    paymentMethodsContainer
        .find('.collection')
        .html(renderedHTML);

    attachSubscriptionsActions();

    paymentMethodsContainer.show();

    if (hasActiveSubscription) {
        $('#alreadySubscribedCollection').show();
    } else {
        $('#tariffsInformationCollection, #trialInformationCollection').show();

        $('#billingForm').fadeIn();

        displayRemovableWarning('No agregaste ningún método de pago.', paymentMethodsContainer);

        paymentMethodsContainer.find('.collection').fadeOut();

        paymentMethodsContainer.show();
    }

    window.Mercadopago.setPublishableKey(MERCADOPAGO_PUBLIC_KEY);

    console.info('Mercado Pago: a public key has been set.');

    window.Mercadopago.getIdentificationTypes((status, response) => {
        console.info('Mercado Pago/getIdentificationTypes:', status, response);

        if (status == 200) {
            documentTypeSelect = $('#documentTypeSelect');

            documentTypeSelect
                .find('option')
                .remove();

            response.forEach((documentType) => {
                documentTypeSelect.append(
                    (new Option(documentType.name, documentType.id)).outerHTML
                );
            });

            instance = M.FormSelect.getInstance(documentTypeSelect[0]);
            
            if (instance != null) {
                instance.destroy();
            }

            initializeSelects();

            enable($('#saveBillingMethodBtn'));
        } else {
            toast('Algo anda mal, por favor probá de nuevo más tarde.');

            isProcessingTransaction = false;

            console.error('Mercado Pago/getIdentificationTypes:', response);
        }
    });

    function getInstallments(paymentMethodId, transactionAmount, issuerId) {
        window.Mercadopago.getInstallments({
            'payment_method_id': paymentMethodId,
            'amount':            parseFloat(transactionAmount),
            'issuer_id':         parseInt(issuerId)
        }, (status, response) => {
            console.info('Mercado Pago/getInstallments:', status, response);

            if (status == 200) {
                let payer_cost = response[0].payer_costs[0];

                invoiceHint = $('#invoiceHint');

                invoiceHint.fadeIn();

                invoiceHint
                    .find('blockquote')
                    .html(
                        payer_cost.recommended_message + ' por mes. <br><br>' + payer_cost.labels[0].replace('|', ' | ').replaceAll('_', ' ')
                    );
            } else {
                toast('Algo anda mal, por favor probá de nuevo más tarde.');

                isProcessingTransaction = false;

                console.error('Mercado Pago/getInstallments:', response);
            }
        });
    }

    function getIssuers(paymentMethodId) {
        window.Mercadopago.getIssuers(
            paymentMethodId,
            (status, response) => {
                console.info('Mercado Pago/getIssuers:', status, response);

                if (status == 200) {
                    let issuer = response[0];

                    issuerBank = issuer.id;

                    getInstallments(
                        paymentMethodId,
                        MERCADOPAGO_SUBSCRIPTION_COST,
                        issuerBank
                    );
                } else {
                    toast('Algo anda mal, por favor probá de nuevo más tarde.');

                    isProcessingTransaction = false;

                    console.error('Mercado Pago/getIssuers:', response);
                }
            }
        );
    }

    function guessPaymentMethod() {
        let cardnumber = document.getElementById("cardNumber").value.replace(' ', '');

        if (cardnumber.length >= 6) {
            let bin = cardnumber.substring(0, 6);

            window.Mercadopago.getPaymentMethod({ 'bin': bin }, (status, response) => {
                console.info('Mercado Pago/guessPaymentMethod:', status, response);

                if (status == 200) {
                    let paymentMethod = response[0];

                    paymentMethodId = paymentMethod.id;

                    getIssuers(paymentMethod.id);
                } else {
                    toast('Algo anda mal, por favor probá de nuevo más tarde.');

                    isProcessingTransaction = false;

                    console.error('Mercado Pago/getPaymentMethod:', response);
                }
            });
        }
    };

    $('#cardNumber').on('change', guessPaymentMethod);

    function getCardToken(onSuccess = () => {}) {
        window.Mercadopago.createToken($('#paymentForm')[0], (status, response) => {
            if (status == 200) {
                console.log(status, response);

                onSuccess(status, response);
            } else {
                toast('Algo anda mal, por favor probá de nuevo más tarde.');

                isProcessingTransaction = false;

                console.error('Mercado Pago/createToken:', response);
            }
        });
    }

    $('#saveBillingMethodBtn').on('click', function () {
        if (!isProcessingTransaction) {
            saveBillingMethodBtn = $(this);

            if (saveBillingMethodBtn.hasClass('pulse')) {
                saveBillingMethodBtn.removeClass('pulse');
            }

            if (
                $('#billingForm input').length
                ==
                $('#billingForm input').filter('.valid').length
            ) {
                isProcessingTransaction = true;

                window.Mercadopago.clearSession();

                getCardToken((status, response) => {
                    if (status == 200) {
                        let transactionProgressInterval = null;

                        transactionProgressBar  = $('#transactionProgressBar');

                        run('companyManager', 'saveSubscription', {
                            cardToken:      response.id,
                            mailAddress:    $('#mailAddress').val()
                        }, async () => {
                            percentageWidth         = saveBillingMethodBtn.width() / 100;
                    
                            width                   = transactionProgressBar.width() + percentageWidth;

                            attachProgressBar();

                            transactionProgressInterval = setInterval(() => {
                                if (transactionProgressBar.width() < saveBillingMethodBtn.outerWidth()) {
                                    width += percentageWidth;

                                    transactionProgressBar.css({ 'width' : width });
                                }
                            }, PROGRESSBAR_TRIGGER_INTERVAL);
                        })
                        .then((response) => {
                            console.info(response);

                            switch (response.data.status) {
                                case OK:
                                    toast('¡Listo!');

                                    removableWarning = paymentMethodsContainer.find('.removableWarning');

                                    removableWarning
                                        .fadeOut(() => {
                                            removableWarning.remove();

                                            paymentMethodsContainer
                                                .find('.collection')
                                                .html(
                                                    getRenderedSubscription(response.data.result.subscription.internalId)
                                                )
                                                .fadeIn();

                                            attachSubscriptionsActions();
                                        });

                                    $('#tariffsInformationCollection, #trialInformationCollection').fadeOut(() => {
                                        $('#alreadySubscribedCollection').fadeIn();
                                    });

                                    $('#billingForm').fadeOut();

                                    break;
                                case ERROR:
                                    switch (response.data.result.subscription.code) {
                                        case 'cc_rejected_bad_filled_card_number':
                                            toast('Revisá el número de tu tarjeta.');

                                            break;
                                        case 'cc_rejected_bad_filled_date':
                                            toast('Revisá la fecha de vencimiento de tu tarjeta.');

                                            break;
                                        case 'cc_rejected_bad_filled_other':
                                            toast('Revisá los datos que ingresaste.');

                                            break;
                                        case 'cc_rejected_bad_filled_security_code':
                                            toast('Revisá el código de seguridad de tu tarjeta.');

                                            break;
                                        case 'cc_rejected_blacklist':
                                            toast('No podemos procesar tu pago, por favor probá con otra tarjeta.');

                                            break;
                                        case 'cc_rejected_call_for_authorize':
                                            toast('Es necesario que autorices el pago desde tu correo electrónico.');

                                            break;
                                        case 'cc_rejected_card_disabled':
                                            toast('Es necesario que llames al banco y actives tu tarjeta.');

                                            break;
                                        case 'cc_rejected_card_error':
                                            toast('No podemos procesar tu pago, por favor probá con otra tarjeta.');

                                            break;
                                        case 'cc_rejected_duplicated_payment':
                                            toast('Ya hiciste un pago exactamente igual.');

                                            break;
                                        case 'cc_rejected_high_risk':
                                            toast('No podemos procesar el pago con esa tarjeta, por favor probá con otra tarjeta.');

                                            break;
                                        case 'cc_rejected_insufficient_amount':
                                            toast('Tu tarjeta no tiene suficientes fondos.');

                                            break;
                                        case 'cc_rejected_invalid_installments':
                                            toast('Tu tarjeta no acepta pagos en ' + installments + ' cuotas.');

                                            break;
                                        case 'cc_rejected_max_attempts':
                                            toast('Ya intentaste muchas veces con esa tarjeta.');

                                            break;
                                        case 'cc_rejected_other_reason':
                                            toast('No podemos procesar el pago ahora, por favor probá más tarde o con otra tarjeta.');

                                            break;
                                    }

                                    break;
                                case ALREADY_EXISTS:
                                    toast('Ya te subscribiste, por favor probá recargando la página.');

                                    break;
                            }
                        })
                        .then(() => {
                            transactionProgressBar.width(0);

                            clearInterval(transactionProgressInterval);

                            isProcessingTransaction = false;
                        });
                    } else {
                        isProcessingTransaction = false;
                    }
                });
            } else {
                toast('Por favor completá todos los datos y probá de nuevo.');
            }
        }
    });
});