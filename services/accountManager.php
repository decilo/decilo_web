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
                            getRenderedMail(
                                $match == null ? 'Te damos la bienvenida' : 'Hola de nuevo',
                                'Para que puedas usar tu cuenta, es necesario que incies sesión tocando el botón de abajo. <br>
                                 <br>
                                 Después, vas a poder cambiar tu contraseña y otros datos desde <b>Mi cuenta</b>.',
                                 'quickStart.php?token=' . $token . '&from=' . $values['mailAddress'],
                                 'Iniciar sesión'
                            )
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
                                setUserTheme($match['theme']);
                                setAllowance($match['allowance']);
                                setLikedMessages([]);

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
                        if (strpos($values['username'], ' ') !== false) {
                            reply([ 'containsSpaces' => true ], ERROR);
                        }

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
                                    getRenderedMail(
                                        'Necesitamos que verifiques tu mail',
                                        'Para hacerlo, tocá el botón de abajo. <br>
                                         <br>
                                         Si no pediste este cambio o no recordás tener una cuenta en <b>' . SYSTEM_TITLE . '</b>, simplemente ignorá este mensaje.',
                                        'quickStart.php?token=' . $token . '&from=' . $user['mailAddress'] . '&changeTo=' . $values['mailAddress'],
                                        'Verificar'
                                    )
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
                                    `d_users`.`username` = :username,
                                    `d_users`.`theme`    = :theme     ' . (empty($values['password']) ? '' : ',
                                    `d_users`.`password` = :password  ') .
                                'WHERE  `d_users`.`id`   = :id'
                            );

                        $statement->bindValue('username',   $values['username']);
                        $statement->bindValue('theme',      $values['theme']);

                        if (!empty($values['password'])) {
                            $statement->bindValue('password', password_hash($values['password'], PASSWORD_ARGON2ID));
                        }

                        $statement->bindValue('id', $user['id']);

                        $statement->execute();

                        setUserName($values['username']);
                        setUserMailAddress($values['mailAddress']);
                        setUserTheme($values['theme']);

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
                            getRenderedMail(
                                'Confirmá que querés eliminar tu cuenta',
                                'Para eliminar tu cuenta, es necesario que verifiquemos si vos pediste hacerlo. Podés hacerlo usando el botón a continuación.',
                                'quickStart.php?token=' . $token . '&from=' . $user['mailAddress'] . '&removeAccount',
                                'Eliminar cuenta'
                            )
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
            case 'tryToSaveTheme':
                $userId = getUserId();

                if ($userId == null) {
                    reply(null, NOT_ALLOWED);
                } else {
                    if (isset($values['theme']) || $values['theme'] == null) {
                        $statement =
                            $GLOBALS['database']->prepare(
                                'UPDATE `d_users`
                                SET    `d_users`.`theme` = :theme
                                WHERE  `d_users`.`id`    = :id'
                            );

                        $statement->execute([
                            'id'    => $userId,
                            'theme' => $values['theme']
                        ]);

                        if ($statement->rowCount() > 0) {
                            setUserTheme($values['theme']);

                            reply(null, OK);
                        } else {
                            reply(null, ERROR);
                        }
                    } else {
                        reply(null, BAD_REQUEST);
                    }
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
                            getColumnNames('d_messages_private', [ 'recipient' ], [ 'image' ]),
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
                        getRenderedMail(
                            'Te dejamos una copia de tus datos',
                            'Tenés un archivo adjunto disponible con una copia de lo que sabemos de vos.'
                        )
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
            case 'generateSubscriptionPlan':
                $allowance = getAllowance();

                if ($allowance == USER_LEVEL_OWNER) {
                    $request = curl_init('https://api.mercadopago.com/preapproval_plan');

                    curl_setopt_array($request, [
                        CURLOPT_HTTPHEADER     => [
                            'Content-Type: application/json',
                            'Authorization: Bearer ' . MERCADOPAGO_KEYS['PRIVATE']
                        ],
                        CURLOPT_POSTFIELDS     => json_encode([
                            'back_url'          => 'https://mercadopago.com.ar',
                            'reason'            => 'Servicios publicitarios',
                            'auto_recurring'    => [
                                'frequency'             => 1,
                                'frequency_type'        => 'months',
                                'transaction_amount'    => MERCADOPAGO_SUBSCRIPTION_COST,
                                'currency_id'           => 'ARS',
                                'repetitions'           => 12,
                                'free_trial'            => [
                                    'frequency'      => 1,
                                    'frequency_type' => 'months'
                                ]
                            ]
                        ]),
                        CURLOPT_RETURNTRANSFER => true
                    ]);

                    $response = curl_exec($request);

                    $result   = json_decode($response, true);

                    reply(
                        $result,
                        $result['status'] == 'active' ? OK : ERROR
                    );
                } else {
                    reply([ 'allowance' => $allowance ], NOT_ALLOWED);
                }

                break;
            default:
                reply(null, WHAT_THE_FUCK);
        }
    }
}

?>