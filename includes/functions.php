<?php
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

require_once 'vendor/autoload.php';

function setUserId($id) {
    $_SESSION[USER_ID_STORE] = $id;
}

function getUserId() {
    return isset($_SESSION[USER_ID_STORE]) ? $_SESSION[USER_ID_STORE] : null;
}

function setAllowance($level) {
    $_SESSION[ALLOWANCE_LEVEL_STORE] = $level;
}

function getAllowance() {
    return isset($_SESSION[ALLOWANCE_LEVEL_STORE]) ? $_SESSION[ALLOWANCE_LEVEL_STORE] : null;
}

function getUIConstants($array) {
    $constants = '';

    foreach ($array as $const => $value) {
        $constants .= 'const ' . $const . ' = ' . json_encode($value) . ';';
    }

    return $constants;
}

function reply($values, $status = OK) {
    print json_encode([
        'result'    => $values,
        'status'    => $status
    ]);

    exit();
}

function redirect($url) {
    header('location: ' . $url); 
    exit();
}

function getMonthsInBetween($target) {
    return 
        (new DateTime($target))
            ->diff(new DateTime())
            ->m;
}

function getCurrentUser() {
    $user = getUserId();

    if ($user == null) {
        return null;
    } else {
        $statement =
            $GLOBALS['database']
                ->prepare(
                    'SELECT *
                     FROM   `d_users`
                     WHERE  id = :user'
                );

        $statement->execute(
            [
                'user' => $user
            ]
        );

        $user = $statement->fetch();
        $user['killSwitchMonths'] = null;

        if ($user['killSwitch'] != null) {
            $user['killSwitchMonths'] = getMonthsInBetween($user['killSwitch']);
        }

        return $user;
    }
}

function getRecipientUsername($username) {
    $statement =
        $GLOBALS['database']->prepare(
            'SELECT `username`
             FROM   `d_users`
             WHERE  `d_users`.`username` = :username'
        );

    $statement->execute([ 'username' => $username ]);

    $result = $statement->fetch();

    return $result == null ? null : $result[0];
}

function getUserLink() {
    return SYSTEM_HOSTNAME . '/?to=' . getCurrentUser()['username'];
}

function getUserQR() {
    $options = new QROptions();

    $options->outputType = QRCode::OUTPUT_MARKUP_SVG;
    $options->eccLevel = QRCode::ECC_H;
    $options->markupDark = '#DCE4F2';
    $options->markupLight = '#071540';
    
    // invoke a fresh QRCode instance
    $qrcode = new QRCode($options);
    
    // and dump the output
    return $qrcode->render(getUserLink());
}

function verifyCaptcha($token) {
    $request = curl_init('https://www.google.com/recaptcha/api/siteverify');

    curl_setopt_array($request, [
        CURLOPT_POSTFIELDS      => [
            'secret'    => RECAPTCHA_PRIVATE_KEY,
            'response'  => $token,
            'remoteip'  => $_SERVER['REMOTE_ADDR']
        ],
        CURLOPT_RETURNTRANSFER  => true
    ]);

    $response = json_decode(curl_exec($request));

    $statement =
        $GLOBALS['database']->prepare(
            'INSERT INTO `d_challenges` (
                `ip`,
                `token`,
                `remoteTimestamp`,
                `success`
             ) VALUES (
                :ip,
                :token, 
                :remoteTimestamp,
                :success
             )'
        );

    $statement->execute(
        [
            'ip'                => $_SERVER['REMOTE_ADDR'],
            'token'             => $token,
            'remoteTimestamp'   => $response->challenge_ts,
            'success'           => $response->success
        ]
    );

    return $response->success;
}

?>
