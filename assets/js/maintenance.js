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
        .addClass('bg-dark-3');

    progressBar
        .find('.indeterminate')
        .removeClass('red')
        .addClass('bg-dark-2')

    isRetrying = false;

    setInterval(function () {
        if (!isRetrying) {
            remainingTime = parseInt($('#remainingTime').html());

            if (remainingTime > 0) {
                $('#remainingTime').html(
                    remainingTime - 1
                );
            } else {
                $('#retryCountdown').fadeOut(() => {
                    $('#retryingHint').fadeIn(() => {
                        isRetrying = true;
                        
                        run('accountManager', 'areYouThere?', undefined, () => {}, true)
                        .done(function (response) {
                            console.log(response);

                            animateRedirect(SYSTEM_HOSTNAME);
                        })
                        .fail(function () {
                            $('#remainingTime').html(5);
                        })
                        .always(function () {
                            $('#retryingHint').fadeOut(() => {
                                $('#retryCountdown').fadeIn(() => {
                                    isRetrying = false;
                                });
                            });
                        });
                    });
                });
            }
        }
    }, 1000);
});