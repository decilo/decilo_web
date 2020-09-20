<?php
use \Ahc\Jwt\JWT;

header('Content-Type: application/json');

chdir('..');

require_once 'includes/main.php';
require_once 'vendor/autoload.php';

$request = json_decode(
    file_get_contents('php://input'),
    true
);

if ($request == null) {
    reply(
        ['message' => 'Bad request, please make sure that you know what you\'re looking for. Unless you\'re looking for a repair, if that\'s the case, then just head over ' . SYSTEM_HOSTNAME . '!'],
        BAD_REQUEST
    );
} else {
    if (isset($request['values'])) {
        $values = &$request['values'];

        switch ($request['action']) {
            case 'trySendLoginMail':
                if (
                    isset($values['mailAddress']) && !empty($values['mailAddress'])
                    &&
                    filter_var($values['mailAddress'], FILTER_VALIDATE_EMAIL)
                ) {
                    $statement = 
                        $GLOBALS['database']->prepare(
                            'SELECT *
                             FROM   `d_users`
                             WHERE  `d_users`.`mailAddress` = :mailAddress'
                        );

                    $statement->execute([ 'mailAddress' => $values['mailAddress'] ]);

                    $match = $statement->fetch();

                    $email = new \SendGrid\Mail\Mail();

                    if (
                        $match == null
                        ||
                        $match['password'] == null
                        ||
                        (isset($values['force']) && boolval($values['force']))
                    ) {
                        $token = getJWT()->encode(
                            [
                                'mailAddress'   => $values['mailAddress'],
                                'timestamp'     => time()
                            ]
                        );

                        if ($match == null) {
                            $statement =
                                $GLOBALS['database']->prepare(
                                    'INSERT INTO `d_users` (
                                        `mailAddress`,
                                        `allowance`,
                                        `quickStartToken`
                                     ) VALUES (
                                        :mailAddress,
                                        :allowance,
                                        :quickStartToken
                                     );'
                                );

                            $statement->execute(
                                [
                                    'mailAddress'       => $values['mailAddress'],
                                    'allowance'         => ALLOWANCE_LEVEL['USER_LEVEL_CUSTOMER'],
                                    'quickStartToken'   => $token
                                ]
                            );
                        } else {
                            $statement =
                                $GLOBALS['database']->prepare(
                                    'UPDATE `d_users`
                                     SET    `d_users`.`quickStartToken`     = :quickStartToken
                                     WHERE  `d_users`.`id`                  = :id'
                                );

                            $statement->execute(
                                [
                                    'id'                => $match['id'],
                                    'quickStartToken'   => $token
                                ]
                            );
                        }

                        $email->setFrom(SENDGRID_NOREPLY_ADDRESS, SYSTEM_TITLE);
                        
                        $email->setSubject(
                            ($match == null ? 'Te damos la bienvenida' : 'Hola de nuevo') .
                            ' - ' .
                            SYSTEM_TITLE
                        );
                        
                        $email->addTo(
                            $values['mailAddress'],
                            $match == null ? $values['mailAddress'] : $match['username']
                        );

                        $email->addContent(
                            'text/html',
                            '<h2> Queda un sólo paso para que puedas usar tu cuenta. </h2>
                             <br>
                             <a href="' . SYSTEM_HOSTNAME . '/quickStart.php?token=' . $token . '&from=' . $values['mailAddress'] . '">
                                Tocá acá
                             </a>'
                        );

                        $sendgrid = new \SendGrid(SENDGRID_NOREPLY_KEY);

                        try {
                            $response = $sendgrid->send($email);

                            $statusCode = $response->statusCode();

                            reply($response, $statusCode == 200 || $statusCode == 202 ? OK : ERROR);
                        } catch (Exception $exception) {
                            reply($exception->getMessage(), ERROR);
                        }
                    } else {
                        reply(null, ALREADY_EXISTS);
                    }
                } else {
                    reply(null, BAD_REQUEST);
                }

                break;
            case 'tryLogin':
                if (isset($values['token'])) {
                    if (verifyCaptcha($values['token'])) {
                        $statement =
                            $GLOBALS['database']->prepare(
                                'SELECT *
                                 FROM   `d_users`
                                 WHERE  LOWER(`d_users`.`mailAddress`) = LOWER(:mailAddress)'
                            );

                        $statement->execute([ 'mailAddress' => $values['mailAddress' ]]);

                        $match = $statement->fetch();

                        if ($match == null) {
                            reply(null, NO_SUCH_ELEMENT);
                        } else {
                            if (password_verify($values['password'], $match['password'])) {
                                if (session_status() == PHP_SESSION_ACTIVE) {
                                    session_destroy();
                                }

                                session_start();

                                setUserId($match['id']);
                                setUserName($match['username']);
                                setUserMailAddress($match['mailAddress']);
                                setAllowance($match['allowance']);

                                reply([
                                    'user'      => (int) $match['id'],
                                    'session'   => session_id()
                                ], OK);
                            } else {    
                                reply(null, NOT_ALLOWED);
                            }
                        }
                    } else {
                        reply(null, SUSPICIOUS_OPERATION);
                    }
                } else {
                    reply(null, BAD_REQUEST);
                }

                break;
            case 'signUpTry':
                if (isset($values['username']) && isset($values['password']) && isset($values['mailAddress'])) {
                    $statement = 
                        $GLOBALS['database']->prepare(
                            'SELECT COUNT(*) 
                             FROM   `tf_users`
                             WHERE  UPER(username)      = UPPER(:username)
                             OR     UPPER(mailAddress)  = UPPER(:mailAddress)'
                        );

                    $statement->execute(
                        [
                            'username'      => $values['username'], 
                            'mailAddress'   => $values['mailAddress']
                        ]
                    );

                    $alreadyExists = $statement->fetch()[0] > 0;

                    if ($alreadyExists) {
                        reply(null, ALREADY_EXISTS);
                    } else {
                        $hasMagicKey = isset($values['magic']);

                        if ($hasMagicKey && $values['magic'] != MASTER_MAGIC) {
                            reply(
                                ['messages' => 'You\'re not allowed to complete this operation, the provided magic key does not match the known one.'], 
                                NOT_ALLOWED
                            );
                        }

                        $statement =
                            $GLOBALS['database']
                                ->prepare(
                                    'INSERT INTO `tf_users` (
                                        `username`, 
                                        `password`, 
                                        `mailAddress`,
                                        `allowance`
                                     ) VALUES (
                                        :username,
                                        :password,
                                        :mailAddress,
                                        :allowance
                                     )'
                                );

                        $statement->execute(
                            [
                                'username'      => $values['username'],
                                'password'      => password_hash($values['password'], PASSWORD_ARGON2ID),
                                'mailAddress'   => $values['mailAddress'],
                                'allowance'     => $hasMagicKey && isset($values['allowance']) ? $values['allowance'] : USER_LEVEL_CUSTOMER
                            ]
                        );

                        $didSucceed = $statement->rowCount() > 0;

                        reply(
                            [
                                'userId'    => $GLOBALS['database']->lastInsertId(),
                            ],
                            $didSucceed ? OK : ERROR
                        );
                    }
                } else {
                    reply(
                        [
                            'message' => 'Some values are missing, for this request to succeed, at least "username", "password" and "mailAddress" must be present.',
                        ],
                        BAD_REQUEST
                    );
                }

                break;
            case 'profileUpdateTry':
                if (
                    isset ($values['username']) && isset ($values['mailAddress']) && isset ($values['password'])
                    &&
                    !empty($values['username']) && !empty($values['mailAddress'])
                ) {
                    if (filter_var($values['mailAddress'], FILTER_VALIDATE_EMAIL)) {
                        $user = getCurrentUser();

                        $matchByUsername    = getUserByUsername($values['username']);
                        $matchByMailAddress = getUserByMailAddress($values['mailAddress']);

                        if ($matchByUsername != null && $matchByUsername['id'] != $user['id']) {
                            reply([ 'matchesUsername' => true ], ALREADY_EXISTS);
                        }

                        if ($values['mailAddress'] != $user['mailAddress']) {
                            if ($matchByMailAddress == null || $matchByMailAddress ['id'] == $user['id']) {
                                $result = [ 'needsMailVerification' => true ];

                                $email = new \SendGrid\Mail\Mail();

                                $token = getJWT()->encode(
                                    [
                                        'mailAddress'   => $user['mailAddress'],
                                        'timestamp'     => time()
                                    ]
                                );

                                $statement =
                                    $GLOBALS['database']->prepare(
                                        'UPDATE `d_users`
                                         SET    `d_users`.`quickStartToken`     = :quickStartToken
                                         WHERE  `d_users`.`id`                  = :id'
                                    );

                                $statement->execute(
                                    [
                                        'id'                => $user['id'],
                                        'quickStartToken'   => $token
                                    ]
                                );

                                $email->setFrom(SENDGRID_NOREPLY_ADDRESS, SYSTEM_TITLE);
                                
                                $email->setSubject('Necesitamos que verifiques tu mail - ' . SYSTEM_TITLE);
                                
                                $email->addTo(
                                    $values['mailAddress'],
                                    $user['username'] == null ? $values['mailAddress'] : $user['username']
                                );

                                $email->addContent(
                                    'text/html',
                                    '<h2> Necesitamos que verifiques este mail. </h2>
                                     <br>
                                     <a href="' . SYSTEM_HOSTNAME . '/quickStart.php?token=' . $token . '&from=' . $user['mailAddress'] . '&changeTo=' . $values['mailAddress'] . '">
                                        Tocá acá
                                     </a>'
                                );

                                $sendgrid = new \SendGrid(SENDGRID_NOREPLY_KEY);

                                try {
                                    $response = $sendgrid->send($email);

                                    $statusCode = $response->statusCode();

                                    reply($result, $statusCode == 200 || $statusCode == 202 ? OK : ERROR);
                                } catch (Exception $exception) {
                                    reply($exception->getMessage(), ERROR);
                                }
                            } else {
                                reply([ 'matchesUsername' => false ], ALREADY_EXISTS);
                            }
                        } else {
                            $result = [ 'needsMailVerification' => false ];
                        }

                        $statement =
                            $GLOBALS['database']->prepare(
                                'UPDATE `d_users`
                                 SET
                                    `d_users`.`username` = :username ' . (empty($values['password']) ? '' : ',
                                    `d_users`.`password` = :password ') .
                                'WHERE  `d_users`.`id`   = :id'
                            );

                        $statement->bindParam('username', $values['username']);

                        $hashedPassword = password_hash($values['password'], PASSWORD_ARGON2ID);

                        if (!empty($values['password'])) {
                            $statement->bindParam('password', $hashedPassword);
                        }

                        $statement->bindParam('id', $user['id']);

                        $statement->execute();

                        setUserName($values['username']);
                        setUserMailAddress($values['mailAddress']);

                        reply($result, OK);
                    } else {
                        reply(null, BAD_REQUEST);
                    }
                } else {
                    reply(null, BAD_REQUEST);
                }

                break;
            case 'requestAccountRemoval':
                if ($values['deleteNow']) {
                    if (
                        isset($_SESSION[ACCOUNT_DELETION_CD_STORE])
                        &&
                        time() - $_SESSION[ACCOUNT_DELETION_CD_STORE] >= PROFILE['ACCOUNT_DELETION_TIME']
                    ) {
                        $user = getCurrentUser();

                        $email = new \SendGrid\Mail\Mail();

                        $token = getJWT()->encode(
                            [
                                'mailAddress'   => $user['mailAddress'],
                                'timestamp'     => time()
                            ]
                        );

                        $statement =
                            $GLOBALS['database']->prepare(
                                'UPDATE `d_users`
                                 SET    `d_users`.`quickStartToken`     = :quickStartToken
                                 WHERE  `d_users`.`id`                  = :id'
                            );

                        $statement->execute(
                            [
                                'id'                => $user['id'],
                                'quickStartToken'   => $token
                            ]
                        );

                        $email->setFrom(SENDGRID_NOREPLY_ADDRESS, SYSTEM_TITLE);
                        
                        $email->setSubject('Eliminá tu cuenta - ' . SYSTEM_TITLE);
                        
                        $email->addTo(
                            $user['mailAddress'],
                            $user['username'] == null ? $values['mailAddress'] : $user['username']
                        );

                        $email->addContent(
                            'text/html',
                            '<h2> Confirmá que querés eliminar tu cuenta. </h2>
                             <br>
                             <a href="' . SYSTEM_HOSTNAME . '/quickStart.php?token=' . $token . '&from=' . $user['mailAddress'] . '&removeAccount">
                                Tocá acá
                             </a>'
                        );

                        $sendgrid = new \SendGrid(SENDGRID_NOREPLY_KEY);

                        try {
                            $response = $sendgrid->send($email);

                            $statusCode = $response->statusCode();

                            reply(
                                [ 'mailAddress' => getCurrentUser()['mailAddress'] ],
                                $statusCode == 200 || $statusCode == 202 ? OK : ERROR
                            );
                        } catch (Exception $exception) {
                            reply($exception->getMessage(), ERROR);
                        }
                    } else {
                        reply(null, NOT_ALLOWED);
                    }
                } else {
                    $_SESSION[ACCOUNT_DELETION_CD_STORE] = time();

                    reply([ 'waitFor' => PROFILE['ACCOUNT_DELETION_TIME'] ]);
                }

                break;
            default:
                reply(null, WHAT_THE_FUCK);
        }
    } else {
        switch ($request['action']) {
            case 'areYouThere?':
                if (getUserId() == null) {
                    reply(['message' => 'Yes, but no session is currently active.'], NOT_ALLOWED);
                } else {
                    reply(['message' => 'Here I am!'], OK);
                }

                break;
            case 'tryLogout':
                session_destroy();

                reply(null);

                break;
            case 'requestDataDownload':
                if (getUserId() == null) {
                    reply(null, NOT_ALLOWED);
                } else {
                    $user = getCurrentUser();

                    $email = new \SendGrid\Mail\Mail();

                    $email->setFrom(SENDGRID_NOREPLY_ADDRESS, SYSTEM_TITLE);

                    $email->setSubject('Te dejamos una copia de tus datos - ' . SYSTEM_TITLE);

                    $email->addTo(
                        $user['mailAddress'],
                        $user['username'] == null ? $user['mailAddress'] : $user['username']
                    );

                    $writer = new XLSXWriter();

                    $writer->writeSheet(
                        getExcelSheet(
                            getColumnNames('d_messages_private', [ 'recipient' ]),
                            getPrivateMessages($user['id'], false, false)
                        ),
                        'Mensajes privados'
                    );

                    $writer->writeSheet(
                        getExcelSheet(
                            getColumnNames('d_challenges'),
                            getChallenges($_SERVER['REMOTE_ADDR'], false)
                        ),
                        'Desafíos de seguridad'
                    );

                    $email->addAttachment(
                        base64_encode($writer->writeToString()),
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'tus_datos.xlsx',
                        'attachment'
                    );

                    $email->addContent(
                        'text/html',
                        '<h2> Esperamos que te sea de utilidad </h2>
                         <p> A continuación, te dejamos una copia de lo que sabemos de vos. </p>'
                    );

                    $sendgrid = new \SendGrid(SENDGRID_NOREPLY_KEY);

                    try {
                        $response = $sendgrid->send($email);

                        $statusCode = $response->statusCode();

                        reply(
                            [ 'mailAddress' => $user['mailAddress'] ],
                            $statusCode == 200 || $statusCode == 202 ? OK : ERROR
                        );
                    } catch (Exception $exception) {
                        reply($exception->getMessage(), ERROR);
                    }
                }

                break;
            default:
                reply(null, WHAT_THE_FUCK);
        }
    }
}

?>