<?php

require_once 'includes/main.php';

if (isset($_GET['token']) && isset($_GET['from'])) {
    $statement =
        $GLOBALS['database']->prepare(
            'SELECT *
             FROM   `d_users`
             WHERE  `d_users`.`mailAddress`     = :mailAddress
             AND    `d_users`.`quickStartToken` = :quickStartToken'
        );

    $statement->execute(
        [
            'mailAddress'       => $_GET['from'],
            'quickStartToken'   => $_GET['token']
        ]
    );

    if ($statement->rowCount() > 0) {
        $decoded = getJWT()->decode($_GET['token']);

        if ($decoded != null && time() < $decoded['exp']) {
            if ($decoded['mailAddress'] == $_GET['from']) {
                if (session_status() == PHP_SESSION_ACTIVE) {
                    session_destroy();
                }

                session_start();

                $match = getUser($decoded['mailAddress']);

                $statement =
                    $GLOBALS['database']->prepare(
                        'UPDATE `d_users`
                         SET    `d_users`.`quickStartToken` = NULL
                         WHERE  `d_users`.`id`              = :id'
                    );

                $statement->execute([ 'id' => $match['id'] ]);

                setUserId($match['id']);
                setAllowance($match['allowance']);

                redirect('private.php');
            }
        } else {
            redirect('index.php?e=' . EXPIRED_TOKEN);
        }
    } else {
        redirect('index.php?e=' . EXPIRED_TOKEN);
    }
}