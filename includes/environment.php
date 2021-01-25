<?php

define('EXPECTED_ENVIRONMENT', [
    // System
    'SYSTEM_HOSTNAME',
    'MASTER_MAGIC',
    'WHATSAPP_LINK',

    // Database
    'DATABASE_HOSTNAME',
    'DATABASE_PORT',
    'DATABASE_NAME',
    'DATABASE_ENCODING',
    'DATABASE_USERNAME',
    'DATABASE_PASSWORD',

    // Third-party services
    'RECAPTCHA_PRIVATE_KEY',
    'RECAPTCHA_PUBLIC_KEY',
    'GOOGLE_ANALYTICS_KEY',
    'SENDGRID_NOREPLY_KEY',
    'ORACLE_OS_REGION',
    'ORACLE_OS_MAIL_ADDRESS',
    'ORACLE_OS_AUTH_TOKEN',
    'ORACLE_OS_PREAUTH_TOKEN',
    'ORACLE_OS_NAMESPACE',
    'ORACLE_OS_CONTAINER',
    'MERCADOPAGO_PUBLIC_KEY',
    'MERCADOPAGO_PRIVATE_KEY',
    'MERCADOPAGO_SUBSCRIPTION_COST',
    'MERCADOPAGO_SUBSCRIPTION_PLAN_ID'
]);

$missingConstants = ''; $expectedConstants = count(EXPECTED_ENVIRONMENT);

foreach (EXPECTED_ENVIRONMENT as $index => $expectedConstant) {
    if (isset($_SERVER[$expectedConstant])) {
        define($expectedConstant, $_SERVER[$expectedConstant]);
    } else {
        $missingConstants .= '<li> ' . $expectedConstant . ' </li>';
    }
}

if (!empty($missingConstants)) {
    print
        'FATAL: This installation is broken since the following constants are missing: <br>
         <ul>
            ' . $missingConstants . '
         </ul>
         Supply them to the virtual host, restart the server and try again.';

    exit();
}

?>