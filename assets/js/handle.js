onload = function () {
    switch (parseInt(EXCEPTION_CODE)) {
        case 1000:
            if (checkES6()) {
                animateRedirect(SYSTEM_HOSTNAME);
            }

            break;
    }
};

if (document.readyState != 'complete') {
    document.onload = onload;
} else {
    onload();
}