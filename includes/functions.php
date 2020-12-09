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

function setUserQR($url) {
    $_SESSION[USER_QR_STORE] = $url;
}

function getUserQR() {
    if (!isset($_SESSION[USER_QR_STORE]) || $_SESSION[USER_QR_STORE] == null) {
        $options = new QROptions();

        $options->outputType = QRCode::OUTPUT_MARKUP_SVG;
        $options->eccLevel = QRCode::ECC_H;
        $options->markupDark = '#' . THEME['LIGHT'][0];
        $options->markupLight = 'transparent';

        $qrcode = new QRCode($options);

        $image = $qrcode->render(getUserLink());

        $filename = sha1($image) . '.svg';

        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        file_put_contents($filePath, $image);

        $url = uploadImage($filePath, $filename);

        setUserQR($url);

        $statement =
            $GLOBALS['database']->prepare(
                'UPDATE `d_users`
                    SET `qr` = :qr
                 WHERE `id` = :id'
            );

        $statement->execute([
            'qr' => $url,
            'id' => getUserId()
        ]);
    }
    
    return $_SESSION[USER_QR_STORE];
}

function setUserTheme($mailAddress) {
    $_SESSION[USER_THEME_STORE] = $mailAddress;
}

function getUserTheme() {
    return isset($_SESSION[USER_THEME_STORE]) ? $_SESSION[USER_THEME_STORE] : null;
}

function setAllowance($level) {
    $_SESSION[ALLOWANCE_LEVEL_STORE] = $level;
}

function getAllowance() {
    return isset($_SESSION[ALLOWANCE_LEVEL_STORE]) ? $_SESSION[ALLOWANCE_LEVEL_STORE] : null;
}

function setLikedMessages($likedMessages) {
    $_SESSION[LIKED_MESSAGES_STORE] = $likedMessages;
}

function getLikedMessages() {
    if (isset($_SESSION[LIKED_MESSAGES_STORE])) {
        return $_SESSION[LIKED_MESSAGES_STORE];
    } else {
        setLikedMessages([]);

        return $_SESSION[LIKED_MESSAGES_STORE];
    }
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
    ], JSON_NUMERIC_CHECK);

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

    return $result == null ? null : $result['username'];
}

function getUserLink() {
    return SYSTEM_HOSTNAME . 'to/' . getCurrentUser()['username'];
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
            'remoteTimestamp'   => strftime('%G-%m-%d %H:%M:%S', strtotime($response->challenge_ts)),
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

function getUserById($id) {
    $statement = 
        $GLOBALS['database']->prepare(
            'SELECT *
             FROM   `d_users`
             WHERE  `d_users`.`id` = :id'
        );

    $statement->execute([ 'id' => $id ]);

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
                created, (
                    SELECT  `d_images`.`url`
                    FROM    `d_images`
                    WHERE   `d_images`.`message`     = `d_messages_private`.`id`
                    AND     `d_images`.`private`     = TRUE
                ) AS image
             FROM   `d_messages_private`
             WHERE  `d_messages_private`.`recipient` = :recipient'
        );

    $statement->execute([ 'recipient' => $userId ]);

    return $statement->fetchAll();
}

function getColumnNames($table, $ignore = [], $add = []) {
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

    $names = array_merge($names, $add);

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
    <div style="background-color: #' . THEME['LIGHT'][3] . '; padding-bottom: 1.5em;">
        <h1 style="width: 100%; padding: 0.2em; text-align: center; font-weight: lighter; background-color: #' . THEME['LIGHT'][0] . '; color: #' . THEME['LIGHT'][4] . ';"> ' . SYSTEM_TITLE . ' </h1>

        <div style="padding-left: 1em; padding-right: 1em; margin: 0 auto;">
            <div style="height: 1em;"></div>

            <p style="font-size: 1rem; text-align: center; margin-block-start: 1em; margin-block-end: 1em; margin-inline-start: 0px; margin-inline-end: 0px; color: #' . THEME['LIGHT'][4] . '; line-height: 1.5;">
                ' . $heading . '
            </p>

            <div style="height: 1em;"></div>
            <div style="height: 1px; overflow: hidden; background-color: #e0e0e0;"></div>
            <div style="height: 1em;"></div>

            <p style="font-weight: 150; margin-block-start: 1em; margin-block-end: 1em; margin-inline-start: 0px; margin-inline-end: 0px; text-align: center; color: #' . THEME['LIGHT'][4] . ';">
                ' . $content . '
            </p>

            <div style="height: 1em;"></div>

            ' . ($buttonAction == null || $buttonLabel == null ? '' : '
            <a href="' . SYSTEM_HOSTNAME . '/' . $buttonAction . '" style="background-color: #' . THEME['LIGHT'][0] . '; display: block; text-decoration: none; color: #fff; text-align: center; letter-spacing: .5px; transition: background-color .2s ease-out; font-size: 14px; outline: 0; border: none; border-radius: 2px; height: 36px; line-height: 36px; padding: 0 16px; width: 10rem; margin: 0 auto; text-transform: uppercase;">
                ' . $buttonLabel . '
            </a>') . '
        </div>
    </div>';
}

function getReportReasons() {
    $statement =
        $GLOBALS['database']->prepare(
            'SELECT     *
             FROM       `d_report_reasons`
             ORDER BY   `d_report_reasons`.`score`'
        );

    $statement->execute();

    return $statement->fetchAll();
}

function getMessage($id, $private = false) {
    $messagesTableSuffix = $private ? 'private' : 'public';

    $statement =
        $GLOBALS['database']->prepare(
            'SELECT *, (
                SELECT  `url`
                FROM    `d_images`
                WHERE   `d_images`.`message` =  `d_messages_' . $messagesTableSuffix . '`.`id`
             ) AS `image`
             FROM   `d_messages_' . $messagesTableSuffix . '`
             WHERE  `d_messages_' . $messagesTableSuffix . '`.`id` = :id'
        );

    $statement->execute([ 'id' => $id ]);

    return $statement->fetch();
}

function isReportReasonValid($id) {
    $statement =
        $GLOBALS['database']->prepare(
            'SELECT COUNT(*) AS count
             FROM   `d_report_reasons`
             WHERE  `d_report_reasons`.`id` = :id'
        );

    $statement->execute([ 'id' => $id ]);

    return $statement->fetch()['count'] > 0;
}

function getParsedString(string $string, Array $replacements) {
    foreach ($replacements as $key => $value) {
        $string = str_replace('{' . $key . '}', $value, $string);
    }

    return $string;
}

function getRecentMessages($recipient = null) {
    $messagesTableSuffix = $recipient == null ? 'public' : 'private';

    $statement = $GLOBALS['database']
        ->prepare(
            'SELECT     `d_messages_' . $messagesTableSuffix . '`.*, (
                SELECT  COUNT(*)
                FROM    `d_reports`
                WHERE   `d_reports`.`message`    = `d_messages_' . $messagesTableSuffix . '`.`id`
                AND     `d_reports`.`reportedBy` = :userId
                AND     `d_reports`.`private`    = ' . ($recipient == null ? 'FALSE' : 'TRUE') . '
             ) > 0 AS reported, (
                SELECT  `d_images`.`url`
                FROM    `d_images`
                WHERE   `d_images`.`message`     = `d_messages_' . $messagesTableSuffix . '`.`id`
                AND     `d_images`.`private`     = ' . ($recipient == null ? 'FALSE' : 'TRUE') . '
             ) AS image,
             CASE
                WHEN CHARACTER_LENGTH(`d_messages_' . $messagesTableSuffix . '`.`content`) > ' . MESSAGES['MAX_LENGTH'] . '
                THEN
                    CONCAT(
                        SUBSTRING(
                            `d_messages_' . $messagesTableSuffix . '`.`content`,
                                1,
                                ' . MESSAGES['MAX_LENGTH'] . '
                        ),
                        \'…\'
                    )
                ELSE
                    `d_messages_' . $messagesTableSuffix . '`.`content`
             END AS content,
             (
                SELECT  COUNT(*)
                FROM    `d_comments`
                WHERE   `d_comments`.`message` = `d_messages_' . $messagesTableSuffix . '`.`id`
                AND     `d_comments`.`private` = ' . ($recipient == null ? 'FALSE' : 'TRUE') . '
             ) AS comments,
             CASE
                WHEN (
                    SELECT  COUNT(*)
                    FROM    `d_images`
                    WHERE   `d_images`.`message`     = `d_messages_' . $messagesTableSuffix . '`.`id`
                    AND     `d_images`.`private`     = ' . ($recipient == null ? 'FALSE' : 'TRUE') . '
                ) > 0
                THEN (
                    CASE
                        WHEN (
                            SELECT  COUNT(*)
                            FROM    `d_images_analyzed`
                            WHERE   `d_images_analyzed`.`image` = (
                                SELECT  `d_images`.`id`
                                FROM    `d_images`
                                WHERE   `d_images`.`message`     = `d_messages_' . $messagesTableSuffix . '`.`id`
                                AND     `d_images`.`private`     = ' . ($recipient == null ? 'FALSE' : 'TRUE') . '
                            )
                        ) > 0
                    THEN true
                    ELSE false
                    END
                )
                ELSE true
             END AS verified
             FROM       `d_messages_' . $messagesTableSuffix . '`' . ($recipient == null ? ''      : '
             JOIN       `d_users`                ON `d_users`.`username`        = :recipient
             WHERE      `d_messages_' . $messagesTableSuffix . '`.`recipient`   = `d_users`.`id`') . '
             ORDER BY   `id` DESC
             LIMIT      ' . INDEX['QUICKLOAD_MESSAGES_LIMIT']
        );

    $params = [ 'userId' => getUserId() ];

    if ($recipient != null) {
        $params['recipient'] = $recipient;
    }

    $statement->execute($params);

    return $statement->fetchAll();
}

function uploadImage($path, $filename) {
    $file = fopen($path, 'r');
    
    $curl = curl_init(
        getParsedString(
            ORACLE_OBJECT_STORAGE_UPLOAD_URL, [
                'REGION'        => ORACLE_OBJECT_STORAGE_AUTH['ACCOUNT']['REGION'],
                'PREAUTH_TOKEN' => ORACLE_OBJECT_STORAGE_AUTH['BUCKET']['PREAUTH_TOKEN'],
                'NAMESPACE'     => ORACLE_OBJECT_STORAGE_AUTH['BUCKET']['NAMESPACE'],
                'CONTAINER'     => ORACLE_OBJECT_STORAGE_AUTH['BUCKET']['CONTAINER'],
                'FILENAME'      => $filename
            ]
        )
    );

    $mime = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($mime, $path);
    $mime = strpos($mime, 'svg') !== false ? 'image/svg+xml' : $mime;

    curl_setopt_array($curl,
        [
            CURLOPT_PUT             => true,
            CURLOPT_INFILE          => $file,
            CURLOPT_INFILESIZE      => filesize($path),
            CURLOPT_HEADER          => true,
            CURLOPT_HTTPHEADER      => [ 
                'Content-Type: ' . $mime,
                'opc-meta-cache-control: max-age=31557600, public',
                'Cache-Control: max-age=31557600, public'
            ],
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_USERPWD         => 
                ORACLE_OBJECT_STORAGE_AUTH['ACCOUNT']['MAIL_ADDRESS'] .
                ':' .
                ORACLE_OBJECT_STORAGE_AUTH['ACCOUNT']['AUTH_TOKEN']
        ]
    );

    curl_exec($curl);

    $info = curl_getinfo($curl);

    curl_close($curl);
    fclose($file);

    return
        $info['http_code'] == 200
            ?   getParsedString(ORACLE_OBJECT_STORAGE_DOWNLOAD_URL, [
                    'REGION'    => ORACLE_OBJECT_STORAGE_AUTH['ACCOUNT']['REGION'],
                    'NAMESPACE' => ORACLE_OBJECT_STORAGE_AUTH['BUCKET']['NAMESPACE'],
                    'CONTAINER' => ORACLE_OBJECT_STORAGE_AUTH['BUCKET']['CONTAINER'],
                    'FILENAME'  => $filename
                ])
            :   null;
}

function getParsedDatetime($datetime) {
    return strftime('%d/%m/%Y %H:%M:%S', strtotime($datetime));
}

function getReportedMessages() {
    $statement =
        $GLOBALS['database']->prepare(
            'SELECT
                d_messages_public.id AS message,
		        SUBSTR( d_messages_public.content, 1, 48 ) AS content,
                (
                    SELECT
                        REPLACE(GROUP_CONCAT( d_report_reasons.reason ), \',\', \', \')
                    FROM
                        d_report_reasons
                    JOIN d_reports ON d_report_reasons.id = d_reports.reason 
                    WHERE   d_reports.message = d_messages_public.id 
                    AND     d_reports.private = false
                ) AS reasons,
                (
                    SELECT  COUNT(*)
                    FROM    d_reports
                    WHERE   d_reports.message = d_messages_public.id
                ) AS reports,
                false AS private
             FROM d_messages_public 
             WHERE (
                SELECT COUNT(*)
                FROM d_reports
                WHERE d_reports.message = d_messages_public.id
             ) > 0

             UNION

             SELECT
                d_messages_private.id,
                d_messages_private.content,
                (
                    SELECT
                        REPLACE(GROUP_CONCAT( d_report_reasons.reason ), \',\', \', \')
                    FROM
                        d_report_reasons
                        JOIN d_reports ON d_report_reasons.id = d_reports.reason 
                    WHERE   d_reports.message = d_messages_private.id 
                    AND     d_reports.private = true 
                ) AS reasons,
                (
                    SELECT COUNT(*)
                    FROM d_reports
                    WHERE d_reports.message = d_messages_private.id
                ) AS reports,
                true AS private
             FROM d_messages_private 
             WHERE (
                SELECT COUNT(*)
                FROM d_reports
                WHERE d_reports.message = d_messages_private.id
             ) > 0
             
             ORDER BY reports DESC'
        );

    $statement->execute();

    return $statement->fetchAll();
}

function getRandomWallpaper() {
    $url = FALLBACK_WALLPAPER;

    if (isset($GLOBALS['database'])) {
        $statement =
            $GLOBALS['database']->prepare(
                'SELECT
                    d_wallpapers.id, d_wallpapers.url
                FROM
                    d_wallpapers,
                    (
                        SELECT      id
                        FROM        d_wallpapers
                        ORDER BY    RAND()
                        LIMIT       1
                    ) AS randomizer
                WHERE d_wallpapers.id = randomizer.id'
            );
        
        $statement->execute();

        $url = $statement->fetch()['url'];
    }

    return $url;
}

?>