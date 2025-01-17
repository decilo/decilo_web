let notReadyWrapper = null;

$(document).ready(() => {
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
        .addClass('bg-dark-7 bg-light-10');

    progressBar
        .find('.indeterminate')
        .removeClass('red')
        .addClass('bg-dark-1 bg-light-1')

    isRetrying = false;

    setInterval(() => {
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
                        .then((response) => {
                            console.log(response);

                            animateRedirect(SYSTEM_HOSTNAME);
                        })
                        .catch(() => {
                            $('#remainingTime').html(5);
                        })
                        .then(() => {
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