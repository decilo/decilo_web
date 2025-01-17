function loadSnowfall() {
    script          = document.createElement('script');
    script.src      = 'https://rawcdn.githack.com/loktar00/JQuery-Snowfall/d22ba78f76804e21404bc000142c019d6c10973d/dist/snowfall.jquery.min.js';
    script.defer    = true;
    script.onload   = () => {
        console.info('Snowfall: loaded successfully.');

        $('.nav-wrapper').snowfall({
            flakeCount  : SNOWFALL['FLAKE_COUNT'],
            maxSpeed    : SNOWFALL['MAX_SPEED']
        });
    }

    document
        .getElementsByTagName('body')[0]
        .appendChild(script);
}