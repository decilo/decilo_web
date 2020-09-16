<?php

header('Content-Type: application/json');

chdir('..');

require_once 'includes/main.php';

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
                        (isset($values['force']) && boolval($values['force']))
                    ) {
                        $email->setFrom(SENDGRID_NOREPLY_ADDRESS, 'Administración de cuentas');
                        
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
                            <a href="' . SYSTEM_HOSTNAME . '/quickStart.php?token=' . 'DUMMYTOKEN' . '">
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
                    reply(null, NOT_ALLOWED);
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
                                session_destroy();
                                session_start();

                                setUserId($match['id']);
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

                reply(null, OK);

                break;
            default:
                reply(null, WHAT_THE_FUCK);
        }
    }
}

?>