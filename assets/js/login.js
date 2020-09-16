let notReadyWrapper = null;

$(document).ready(function () {
    notReadyWrapper = $('#notReadyWrapper');

    notReadyWrapper
        .addClass('scale-transition scale-out')
        .css({ 'display' : 'flex' })
        .removeClass('scale-out')
        .addClass('scale-in');

    notReadyWrapper
        .find('.real-text')
        .css({ 'display' : 'block' });

    let progressBar = 
        notReadyWrapper
            .find('.progress');
    
    progressBar
        .removeClass('red')
        .addClass('blue darken-2');

    progressBar
        .find('.indeterminate')
        .removeClass('red')
        .addClass('blue');

    $('#loginBtn').on('click', function () {
        let btn = $(this);

        if (
            $('#username').val().length > 0
            &&
            $('#password').val().length > 0
        ) {
            run('accountManager', 'loginTry', {
                'username'  : $('#username').val(),
                'password'  : $('#password').val()
            }, function () {
                disable(btn);
            })
            .done(function (response) {
                console.log(response);

                switch (response.status) {
                    case OK:
                        animateRedirect('manage.php?fromLogin', true);

                        break;
                    case NO_SUCH_ELEMENT:
                        toast('Esa combinación de usuario y contraseña no existe.');

                        break;
                }
            })
            .always(function () {
                enable(btn);
            });
        } else {
            toast('No puede haber campos vacíos, completálos y probá otra vez.');
        }
    });

    $('input').on('keypress', function (event) {
        if (event.key == 'Enter') {
            $('#loginBtn').click();
        }
    });
});

$(window).on('load', function () {
    notReadyWrapper = $('#notReadyWrapper');

    notReadyWrapper
        .removeClass('scale-in')
        .addClass('scale-out');

    setTimeout(function () {
        notReadyWrapper.remove();

        $('#loginWrapper')
            .removeClass('scale-out')
            .addClass('scale-in');
    }, MATERIALIZE_TRANSITION_TIME);
});