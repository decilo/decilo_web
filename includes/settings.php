<?php

if (!defined('REQUIRES_ENVIRONMENT') || REQUIRES_ENVIRONMENT) {
    require_once 'includes/environment.php';
}

// System information
// TODO: Move these to a single 3D array instead of multiple constants.
define('SYSTEM_TITLE', 'Decilo');
define('USER_ID_STORE', 'userId');
define('USER_NAME_STORE', 'userName');
define('USER_MAIL_ADDRESS_STORE', 'userMailAddress');
define('USER_QR_STORE', 'userQR');
define('USER_THEME_STORE', 'userTheme');
define('USER_WALLPAPER_STORE', 'userWallpaper');
define('ALLOWANCE_LEVEL_STORE', 'allowance');
define('ACCOUNT_DELETION_CD_STORE', 'rmStartTime');
define('LIKED_MESSAGES_STORE', 'likedMessages');
define('LIKED_COMMENTS_STORE', 'likedComments');
define('COMPANIES_BOOLEAN_STORE', 'hasCompanies');

define('RENDER_REPORT_REASONS_CACHE', false);

// Time constraints - [ 0 => year, 1 => month, 2 => day ]
define('CURRENT_TIME_YMD_ARRAY', explode('-', strftime('%Y-%m-%d')));

// Mail addresses
define('SENDGRID_NOREPLY_ADDRESS', 'no-reply@decilo.app');

if (!defined('REQUIRES_ENVIRONMENT') || REQUIRES_ENVIRONMENT) {
    // Cloud storage
    define('ORACLE_OBJECT_STORAGE_AUTH', [
        'ACCOUNT' => [
            'REGION'         => ORACLE_OS_REGION,
            'MAIL_ADDRESS'   => ORACLE_OS_MAIL_ADDRESS,
            'AUTH_TOKEN'     => ORACLE_OS_AUTH_TOKEN
        ],
        'BUCKET' => [
            'PREAUTH_TOKEN'  => ORACLE_OS_PREAUTH_TOKEN,
            'NAMESPACE'      => ORACLE_OS_NAMESPACE,
            'CONTAINER'      => ORACLE_OS_CONTAINER
        ]
    ]);

    // Billing
    define('MERCADOPAGO_KEYS', [
        'PUBLIC'    => MERCADOPAGO_PUBLIC_KEY,
        'PRIVATE'   => MERCADOPAGO_PRIVATE_KEY
    ]);

    define(
        'ORACLE_OBJECT_STORAGE_UPLOAD_URL', 
        'https://objectstorage.{REGION}.oraclecloud.com' .
        '/p/{PREAUTH_TOKEN}'                             .
        '/n/{NAMESPACE}'                                 .
        '/b/{CONTAINER}'                                 .
        '/o/{FILENAME}'
    );

    define(
        'ORACLE_OBJECT_STORAGE_DOWNLOAD_URL', 
        'https://objectstorage.{REGION}.oraclecloud.com' .
        '/n/{NAMESPACE}'                                 .
        '/b/{CONTAINER}'                                 .
        '/o/{FILENAME}'
    );

    // Database authentication
    define('DATABASE', 
        [
            'hostname'  => DATABASE_HOSTNAME,
            'port'      => DATABASE_PORT,
            'name'      => DATABASE_NAME,
            'encoding'  => DATABASE_ENCODING,
            'username'  => DATABASE_USERNAME,
            'password'  => DATABASE_PASSWORD
        ]
    );

    // Shared
    define('SHARED_VALUES', 
        [
            'OK'                     =>  0,
            'ERROR'                  => -1,
            'NOT_ALLOWED'            => -2,
            'NOT_READY'              => -3,
            'STILL_BUSY'             => -4,
            'NO_SUCH_ELEMENT'        => -5,
            'WONT_REPLY'             => -6,
            'BAD_REQUEST'            => -7,
            'ALREADY_EXISTS'         => -8,
            'SUSPICIOUS_OPERATION'   => -9,
            'EXPIRED_TOKEN'          => -10,
            'WHAT_THE_FUCK'          => -100,

            'RECAPTCHA_PUBLIC_KEY'   => RECAPTCHA_PUBLIC_KEY,

            'GOOGLE_ANALYTICS_KEY'   => GOOGLE_ANALYTICS_KEY,

            'GOOGLE_CRAWLER_UAS'     => [ 'Googlebot' ],

            'THEME'                  => [
                'LIGHT' => [ 'DE3FC4', '3E46E5', '16151D', '060708', 'F1F1F1', 'F753DC', '272535', 'CCCCCC6B', '525151', 'DCDCDC', 'F1F1F1', 'DADADA' ],
                'DARK'  => [ 'C121A7', '484FD9', '16151D', '060708', 'F1F1F1', 'F753DC', '272535', 'CCCCCC6B', 'F1F1F1', '272535', '0B0A0E', '16151D' ]
            ],

            'IMAGE_PROCESSING'       => [
                'CROP_WIDTH'        => 1024,
                'CROP_HEIGHT'       => 768,
                'QUALITY'           => 85,
                'FORMAT'            => IMAGETYPE_WEBP,
                'EXTENSION'         => '.webp'
            ],

            'PROFILE'                => [
                'ACCOUNT_DELETION_TIME' => 5 // seconds
            ],

            'QUICKSTART'             => [
                'INVALID_MAIL_ADDRESS'  => -7,
                'MAIL_CHANGE_OK'        =>  0
            ],

            'INDEX'                  => [
                'QUICKLOAD_MESSAGES_LIMIT'  => 20,
                'PUBLIC_MESSAGES_LIMIT'     => 25,
                'POST_OK_COOLDOWN'          => 0,       // ms
                'SWIPE_ENABLE'              => false,
                'SWIPE_THRESHOLD'           => 45       // px
            ],

            'MESSAGES'               => [
                'PUBLIC_MESSAGES_LIMIT' => 50,
                'POST_OK_COOLDOWN'      => 0,   // ms
                'MAX_LENGTH'            => 512, // characters
                'VIEWPORT_CHECK_LENGTH' => 12   // posts
            ],

            'COMPANY'                => [
                'ENABLE'                => false,
                'REMOVAL_SUCCEEDED'     => 100
            ],

            'THEMES'                 => [ 'AUTO' => null, 'DARK' => 0, 'LIGHT' => 1 ],

            'SYSTEM_HOSTNAME'        => SYSTEM_HOSTNAME,
            'STATUS_SERVER'          => 'https://status.decilo.app/',
            'WHATSAPP_LINK'          => WHATSAPP_LINK,

            'IS_XMAS'                => (
                (CURRENT_TIME_YMD_ARRAY[1] == 12 && CURRENT_TIME_YMD_ARRAY[2] > 7) // month = 12 and day greater than 7 [08 - 31]
                ||
                (CURRENT_TIME_YMD_ARRAY[1] == 1  && CURRENT_TIME_YMD_ARRAY[2] < 7) // month = 12 and day less than 7    [01 - 06]
            ),

            'SNOWFALL'               => ['FLAKE_COUNT' => 35, 'MAX_SPEED' => 1],

            'FALLBACK_WALLPAPER'     => 'https://objectstorage.' . ORACLE_OS_REGION . '.oraclecloud.com/n/' . ORACLE_OS_NAMESPACE . '/b/' . ORACLE_OS_CONTAINER . '/o/wallpapers%2Fw19.webp',

            'SORTING_METHODS'        => [
                'BY_RELEVANCE'  => 0,
                'BY_DATE'       => 1,
                'BY_LIKES'      => 2,
                'BY_COMMENTS'   => 3
            ],

            'FAILURE_RETRY_INTERVAL' => 5000, // ms

            'THEME_COLOR'            => 'C121A7',
            'THEME_NSFW_COLOR'       => '272727',

            'DO_NOT_TRACK'           => isset($_SERVER['HTTP_DNT'])
        ]
    );

    define('WEBSITE_MANIFEST', [
        'name'          => SYSTEM_TITLE,
        'short_name'    => SYSTEM_TITLE,
        'icons'         => [
            [
                'src'       => SHARED_VALUES['SYSTEM_HOSTNAME'] . 'assets/icons/android-chrome-192x192.png',
                'sizes'     => '192x192',
                'type'      => 'image/png'
            ],
            [
                'src'       => SHARED_VALUES['SYSTEM_HOSTNAME'] . 'assets/icons/android-chrome-512x512.png',
                'sizes'     => '512x512',
                'type'      => 'image/png'
            ],
            [
                'src'       => SHARED_VALUES['SYSTEM_HOSTNAME'] . 'assets/icons/android-chrome-512x512-maskable.png',
                'sizes'     => '512x512',
                'type'      => 'image/png',
                'purpose'   => 'maskable'
            ]
        ],
        'theme_color'       => '#c121a7',
        'background_color'  => '#c121a7',
        'start_url'         => SHARED_VALUES['SYSTEM_HOSTNAME'],
        'display'           => 'standalone'
    ]);

    define('ORACLE_FONTS_CDN_URL', 'https://objectstorage.' . ORACLE_OS_REGION . '.oraclecloud.com/n/' . ORACLE_OS_NAMESPACE . '/b/' . ORACLE_OS_CONTAINER . '/o/fonts');
}

// Crypto
define('CRYPTO',
    [
        'JWT_ALGO'      => 'HS256',
        'JWT_LIFETIME'  => 86400, // seconds
        'JWT_LEEWAY'    => 120
    ]
);

// Permissions
define('ALLOWANCE_LEVEL', 
    [
        'USER_LEVEL_CUSTOMER'   => 0,
        'USER_LEVEL_EMPLOYEE'   => 1,
        'USER_LEVEL_OWNER'      => 2
    ]
);

// UI
define('UI_SETTINGS', 
    [
        'PROGRESSBAR_TRIGGER_INTERVAL'  => 100,         // ms
        'MATERIALIZE_TRANSITION_TIME'   => 200,         // ms
        'SMALL_SCREEN_WIDTH'            => 600,         // px
        'HEARTBEAT_INTERVAL'            => -1,          // ms
        'SCROLLTOP_DURATION'            => 1500,        // ms
        'SCROLLTOP_THRESHOLD'           => 50,          // %    between last item top offset and viewport
        'VIEWPORT_VISIBLE_THRESHOLD'    => 10,          // %    between viewport edges and element
        'IDLE_TIMEOUT'                  => 7500,        // ms
        'INFOBOX_HIDE_TIMEOUT'          => 15000,       // ms
        'INFORMATION_MODAL_TIMEOUT'     => 2500         // ms
    ]
);

define('CRITICAL_ORIGINS', [
    'https://unpkg.com',
    'https://www.gstatic.com',
    'https://www.google.com',
    'https://www.google-analytics.com',
    'https://www.googletagmanager.com',
    'https://fonts.googleapis.com',
    'https://fonts.gstatic.com/',
    'https://cdn.jsdelivr.net',
    'https://cdnjs.cloudflare.com'
]);

define('USE_BUNDLE', true);
define('BUNDLE_HASH_ALGO', 'crc32');
define('BUNDLE_VERSION', '69ecfd51');

define('CORE_STYLESHEETS', [
    'MaterializeCSS'            => 'https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css',
    'Common'                    => 'assets/css/style.min.css'
]);

define('CORE_SCRIPTS', [
    'Default Passive Events'    => 'https://unpkg.com/default-passive-events@2.0.0/dist/index.umd.js',
    'cash-dom'                  => 'https://cdnjs.cloudflare.com/ajax/libs/cash/8.1.0/cash.min.js',
    'axios'                     => 'https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js',
    'Day.js'                    => 'https://unpkg.com/dayjs@1.8.35/dayjs.min.js',
    'Day.js/localizedFormat'    => 'https://unpkg.com/dayjs@1.8.35/plugin/localizedFormat.js',
    'Day.js/localizedFormat/es' => 'https://unpkg.com/dayjs@1.8.35/locale/es.js',
    'Day.js/localizedFormat/en' => 'https://unpkg.com/dayjs@1.8.35/locale/en.js',
    'MaterializeCSS'            => 'https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js',
    'Masonry'                   => 'https://cdnjs.cloudflare.com/ajax/libs/masonry/4.2.2/masonry.pkgd.min.js',
    'Showdown'                  => 'https://cdnjs.cloudflare.com/ajax/libs/showdown/1.9.1/showdown.min.js',
    'Common'                    => 'assets/js/common.min.js'
]);

?>