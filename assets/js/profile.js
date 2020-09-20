function validateUsername() {
    let username = $('#username');

    if (username.val().length > 0) {
        markValid(username);
    } else {
        markInvalid(username);
    }
}

function validateMailAddress() {
    if (isMailAddressValid($('#mailAddress').val())) {
        markValid($('#mailAddress'));
    } else {
        markInvalid($('#mailAddress'));
    }
}

function validatePassword() {
    let password = $('#password').val();

    if (password.length > 0 && password == $('#passwordVerifier').val()) {
        markValid($('#password, #passwordVerifier'));
    } else if (password.length > 0 && password != $('#passwordVerifier').val()) {
        markInvalid($('#password, #passwordVerifier'));
    } else {
        $('#password, #passwordVerifier').removeClass('valid invalid');
    }
}

$(document).ready(() => {
    let isAllowedToDelete = false;

    $('#profileUpdateTryBtn').on('click', () => {
        let password            = $('#password').val();
        let passwordVerifier    = $('#passwordVerifier').val();

        if (
            password == passwordVerifier
            ||
            (password.length == 0 && passwordVerifier.length == 0)
        ) {
            let username            = $('#username').val();
            let mailAddress         = $('#mailAddress').val();

            run('accountManager', 'profileUpdateTry', {
                username:       username,
                mailAddress:    mailAddress,
                password:       password
            }, () => {
                disable($('#profileUpdateTryBtn'));

                $('#profileUpdateTryBtn').html('Guardando');
            })
            .done((response) => {
                console.log(response);

                switch (response.status) {
                    case OK:
                        if (response.result.needsMailVerification) {
                            toast('Mirá ' + mailAddress + ' para confirmar el cambio.');
                        } else {
                            toast('¡Listo!');
                        }

                        let passwordFields = $('#password, #passwordVerifier');

                        passwordFields.val('');
                        passwordFields.change();

                        break;
                    case ALREADY_EXISTS:
                        if (response.result.matchesUsername) {
                            toast('Ya existe otra cuenta usando ese nombre de usuario.');
                        } else {
                            toast('Ya existe otra cuenta usando esa dirección de correo electrónico.');
                        }

                        break;
                }
            })
            .always(() => {
                enable($('#profileUpdateTryBtn'));

                $('#profileUpdateTryBtn').html('Guardar');
            });
        }
    });

    $('#requestDataDownloadBtn').on('click', () => {
        run('accountManager', 'requestDataDownload', undefined, () => {
            disable($('#requestDataDownloadBtn, #requestAccountRemovalBtn'));

            $('#requestDataDownloadBtn').find('span').html('Generando');
        })
        .done((response) => {
            console.info(response);

            switch (response.status) {
                case OK:
                    toast('¡Listo! Te lo dejamos en ' + response.result.mailAddress + '.');

                    break;
                case NOT_ALLOWED:
                    toast('No tenés permitido hacer eso.');

                    break;
                case ERROR:
                    toast('Algo anda mal, ya reportamos el problema.');

                    break;
            }
        })
        .always(() => {
            enable($('#requestDataDownloadBtn, #requestAccountRemovalBtn'));

            $('#requestDataDownloadBtn').find('span').html('Generar informe');
        });
    });

    $('#requestAccountRemovalBtn').on('click', () => {
        run('accountManager', 'requestAccountRemoval', { deleteNow: isAllowedToDelete }, () => {
            disable($('#requestDataDownloadBtn, #requestAccountRemovalBtn'));

            if (!isAllowedToDelete) {
                $('#requestAccountRemovalBtn').find('span').html('Preparando');
            }
        })
        .done((response) => {
            console.info(response);

            switch (response.status) {
                case OK:
                    if (typeof(response.result.waitFor) == 'undefined') {
                        isAllowedToDelete = false;

                        enable($('#requestDataDownloadBtn, #requestAccountRemovalBtn'));

                        toast('¡Listo! Confirmálo en ' + response.result.mailAddress + '.');
                    } else {
                        let remaining = response.result.waitFor;

                        countdown = setInterval(() => {
                            if (remaining >= 0) {
                                $('#requestAccountRemovalBtn').find('span').html(
                                    'Esperá ' + remaining + ' segundo' + (remaining == 1 ? '' : 's')
                                );

                                remaining -= 1;
                            } else {
                                clearInterval(countdown);

                                $('#requestAccountRemovalBtn')
                                    .find('span')
                                    .html('Eliminar ahora');

                                isAllowedToDelete = true;

                                enable($('#requestDataDownloadBtn, #requestAccountRemovalBtn'));
                            }
                        }, 1000);
                    }

                    break;
                case NOT_ALLOWED:
                    toast('No tenés permitido hacer eso.');

                    break;
                case ERROR:
                    toast('Algo anda mal, ya reportamos el problema.');

                    enable($('#requestDataDownloadBtn, #requestAccountRemovalBtn'));
        
                    $('#requestAccountRemovalBtn').find('span').html('Eliminar cuenta');

                    break;
            }
        });
    });

    $('#password, #passwordVerifier').on('keyup change', validatePassword);

    $('#mailAddress').on('keyup change', validateMailAddress);

    validateUsername();
    validateMailAddress();
});