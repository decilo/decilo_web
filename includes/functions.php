<?php
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use \Ahc\Jwt\JWT;

require_once 'vendor/autoload.php';

function setUserId($id) {
    $_SESSION[USER_ID_STORE] = $id;
}

function getUserId() {
    return isset($_SESSION[USER_ID_STORE]) ? $_SESSION[USER_ID_STORE] : null;
}

function setUserName($username) {
    $_SESSION[USER_NAME_STORE] = $username;
}

function getUserName() {
    return isset($_SESSION[USER_NAME_STORE]) ? $_SESSION[USER_NAME_STORE] : null;
}

function setUserMailAddress($mailAddress) {
    $_SESSION[USER_MAIL_ADDRESS_STORE] = $mailAddress;
}

function getUserMailAddress() {
    return isset($_SESSION[USER_MAIL_ADDRESS_STORE]) ? $_SESSION[USER_MAIL_ADDRESS_STORE] : null;
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
    $options->markupDark = '#' . THEME[0];
    $options->markupLight = '#' . THEME[3];
    
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

function getJWT() {
    return (new JWT(MASTER_MAGIC, CRYPTO['JWT_ALGO'], CRYPTO['JWT_LIFETIME'], CRYPTO['JWT_LEEWAY']));
}

function getUserByUsername($username) {
    $statement = 
        $GLOBALS['database']->prepare(
            'SELECT *
             FROM   `d_users`
             WHERE  `d_users`.`username` = :username'
        );

    $statement->execute([ 'username' => $username ]);

    return $statement->fetch();
}

function getUserByMailAddress($mailAddress) {
    $statement = 
        $GLOBALS['database']->prepare(
            'SELECT *
             FROM   `d_users`
             WHERE  `d_users`.`mailAddress` = :mailAddress'
        );

    $statement->execute([ 'mailAddress' => $mailAddress ]);

    return $statement->fetch();
}

function getPrivateMessages($userId, $includeId = true, $includeRecipient = true) {
    $statement =
        $GLOBALS['database']->prepare(
            'SELECT
                ' . ($includeId         ? 'id, '        : '') . '
                ' . ($includeRecipient  ? 'recipient, ' : '') . '
                content,
                declaredName,
                created
             FROM   `d_messages_private`
             WHERE  `d_messages_private`.`recipient` = :recipient'
        );

    $statement->execute([ 'recipient' => $userId ]);

    return $statement->fetchAll();
}

function getColumnNames($table, $ignore = []) {
    $names = [];

    $statement = $GLOBALS['database']->query('DESCRIBE `' . $table . '`');

    foreach ($statement->fetchAll() as $columnMetadata) {
        if (
            $columnMetadata['Extra'] != 'auto_increment'
            &&
            !in_array($columnMetadata['Field'], $ignore)
        ) {
            $names[] = $columnMetadata['Field'];
        }
    }

    return $names;
}

function getExcelSheet($header, $body) {
    $sheet = [ $header ];

    foreach ($body as $row) {
        $processedMessage = [];
        foreach ($row as $key => $column) {
            if (!is_numeric($key)) {
                $processedMessage[] = $column;
            }
        }
        
        $sheet[] = $processedMessage;
    }

    return $sheet;
}

function getChallenges($ip, $includeId = true) {
    $statement =
        $GLOBALS['database']->prepare(
            'SELECT 
                ' . ($includeId ? 'id, ' : '') . '
                ip,
                token,
                serverTimestamp,
                remoteTimestamp,
                success
             FROM   `d_challenges`
             WHERE  `d_challenges`.`ip` = :ip'
        );

    $statement->execute([ 'ip' => $ip ]);

    return $statement->fetchAll();
}

function getRenderedMail($heading, $content, $buttonAction = null, $buttonLabel = null) {
    return '
    <div style="background-color: #' . THEME[3] . '; padding-bottom: 1.5em;">
        <h1 style="width: 100%; padding: 0.2em; text-align: center; font-weight: lighter; background-color: #' . THEME[0] . '; color: #' . THEME[4] . ';"> ' . SYSTEM_TITLE . ' </h1>

        <div style="padding-left: 1em; padding-right: 1em; margin: 0 auto;">
            <div style="height: 1em;"></div>

            <p style="font-size: 1rem; text-align: center; margin-block-start: 1em; margin-block-end: 1em; margin-inline-start: 0px; margin-inline-end: 0px; color: #' . THEME[4] . '; line-height: 1.5;">
                ' . $heading . '
            </p>

            <div style="height: 1em;"></div>
            <div style="height: 1px; overflow: hidden; background-color: #e0e0e0;"></div>
            <div style="height: 1em;"></div>

            <p style="font-weight: 150; margin-block-start: 1em; margin-block-end: 1em; margin-inline-start: 0px; margin-inline-end: 0px; text-align: center; color: #' . THEME[4] . ';">
                ' . $content . '
            </p>

            <div style="height: 1em;"></div>

            ' . ($buttonAction == null || $buttonLabel == null ? '' : '
            <a href="' . SYSTEM_HOSTNAME . '/' . $buttonAction . '" style="background-color: #' . THEME[0] . '; display: block; text-decoration: none; color: #fff; text-align: center; letter-spacing: .5px; transition: background-color .2s ease-out; font-size: 14px; outline: 0; border: none; border-radius: 2px; height: 36px; line-height: 36px; padding: 0 16px; width: 10rem; margin: 0 auto; text-transform: uppercase;">
                ' . $buttonLabel . '
            </a>') . '
        </div>
    </div>';
}

?>