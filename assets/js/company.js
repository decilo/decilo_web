$(document).ready(() => {
    deleteCompanyModal = $('#deleteCompanyModal');

    loadRecaptcha(false, false, () => {
        enable($('#companyUpdateBtn'));
    });

    function initializeCompanyRemover() {
        deleteCompanyModal.find('.companyName').html(company['name']);
        deleteCompanyModal.find('.companyLegalName').html(company['legalName']);
        deleteCompanyModal.find('#deleteCompanyBtn').on('click', () => {
            if ($('#toDeleteCompanyLegalName').val() == company['legalName']) {
                deleteCompanyBtn = $('#deleteCompanyBtn');

                run('companyManager', 'tryToDeleteCompany', { company: company['id'] }, () => {
                    disable(deleteCompanyBtn);

                    deleteCompanyBtn.html('Eliminando');
                })
                .done((response) => {
                    console.info(response);

                    if (response.status != OK) {
                        enable(deleteCompanyBtn);
        
                        deleteCompanyBtn.html('Eliminar');
                    }

                    switch (response.status) {
                        case OK:
                            animateRedirect('/?e=' + COMPANY['REMOVAL_SUCCEEDED']);

                            break;
                        case NOT_READY:
                            toast('Antes de eliminar tu empresa, tenés que cancelar tus subscripciones.');

                            break;
                        case NO_SUCH_ELEMENT:
                            toast('La empresa que querés eliminar no existe o no te pertenece.');

                            break;
                    }
                });
            } else {
                toast('El nombre no coincide, por favor verificálo y probá otra vez.');
            }
        });

        $('#requestCompanyRemovalBtn').on('click', () => {
            if (company['isBillingEnabled'] == 1) {
                toast('Antes de eliminar tu empresa, tenés que cancelar tus subscripciones.');
            } else {
                deleteCompanyModal.modal('open');
            }
        });

        $('#toDeleteCompanyLegalName').on('keyup change input', (event) => {
            target = event.currentTarget;

            if (target.value == company['legalName']) {
                markValid($(target));
            } else {
                markInvalid($(target));
            }
        });
    }

    $('#companyName').on('keyup change', function () {
        if ($(this).val().length > 0) {
            markValid($(this));
        } else {
            markInvalid($(this));
        }
    });

    $('#companyLegalName').on('keyup change', function () {
        if ($(this).val().length > 0) {
            markValid($(this));
        } else {
            markInvalid($(this));
        }
    });

    $('#companyIdentifier').on('keyup change', function () {
        value = $(this).val();

        if (value.length == 11 || (value.length == 13 && value.indexOf('-') > -1)) {
            markValid($(this));
        } else {
            markInvalid($(this));
        }
    });

    $('#companyUpdateBtn').on('click', () => {
        companyId           = company == null ? null : company['id'];
        companyName         = $('#companyName').val();
        companyLegalName    = $('#companyLegalName').val();
        companyIdentifier   = $('#companyIdentifier').val();

        if (
            companyName.length > 0
            &&
            companyLegalName.length > 0
            &&
            (companyIdentifier.length == 11 || (companyIdentifier.length == 13 && companyIdentifier.indexOf('-') > -1))
        ) {
            run('companyManager', 'saveCompany', {
                id:         companyId,
                name:       companyName,
                legalName:  companyLegalName,
                identifier: companyIdentifier
            }, () => {
                disable($('#companyUpdateBtn'));
            })
            .done((response) => {
                console.info(response);

                switch (response.status) {
                    case OK:
                        if (response.result.isIdentifierValid) {
                            toast('¡Listo!');

                            $('input').removeClass('valid invalid');

                            if (companyId == null) {
                                $('#companyStatusHeader').html('Tu empresa');
                                $('#companyUpdateBtn').html('Actualizar datos');

                                $('.tooltipped').tooltip('destroy');

                                enable($('.btn'));

                                company = response.result.companies[0];
                            }

                            initializeCompanyRemover();

                            $('.ads-menu, .billing-menu').removeClass('hide');

                            $('header').find('.divider').removeClass('hide');
                        } else {
                            toast('El CUIT ingresado no es válido.');

                            $('#companyIdentifier').addClass('invalid');
                        }

                        break;
                    case NO_SUCH_ELEMENT:
                        toast('Esa compañía ya no existe o no tenés permiso para modificarla.');

                        break;
                    case ALREADY_EXISTS:
                        toast('Ese CUIT ya fue registrado.');

                        break;
                }
            })
            .always(() => {
                enable($('#companyUpdateBtn'));
            });
        } else {
            toast('Verificá tus datos y probá de nuevo.');
        }
    });

    if (company != null) {
        initializeCompanyRemover();
    }
});