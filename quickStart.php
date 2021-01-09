<?php

require_once 'includes/main.php';

if (isset($_GET['token']) && isset($_GET['from'])) {
    $statement =
        $database->prepare(
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

                $match = getUserByMailAddress($decoded['mailAddress']);

                $statement =
                    $database->prepare(
                        'UPDATE `d_users`
                         SET    `d_users`.`quickStartToken` = NULL
                         WHERE  `d_users`.`id`              = :id'
                    );

                $statement->execute([ 'id' => $match['id'] ]);

                setUserId($match['id']);
                setUserName($match['username']);
                setUserTheme($match['theme']);

                getUserQR();

                if (isset($_GET['changeTo'])) {
                    if (filter_var($_GET['changeTo'], FILTER_VALIDATE_EMAIL)) {
                        $statement =
                            $database->prepare(
                                'UPDATE `d_users`
                                 SET    `d_users`.`mailAddress` = :mailAddress
                                 WHERE  `d_users`.`id`          = :id'
                            );

                        $statement->execute([
                            'mailAddress'   => $_GET['changeTo'],
                            'id'            => $match['id']
                        ]);

                        setUserMailAddress($_GET['changeTo']);

                        setAllowance($match['allowance']);

                        redirect('profile?e=' . QUICKSTART['MAIL_CHANGE_OK']);
                    } else {
                        redirect(SYSTEM_HOSTNAME . '?e=' . QUICKSTART['INVALID_MAIL_ADDRESS']);
                    }
                } else if (isset($_GET['removeAccount'])) {
                    if (session_status() == PHP_SESSION_ACTIVE) {
                        session_destroy();
                    }

                    $statement =
                        $database->prepare(
                            'DELETE
                             FROM   `d_users`
                             WHERE  `d_users`.`id` = :id'
                        );
                    
                    $statement->execute([ 'id' => $match['id'] ]);

                    $statement =
                        $database->prepare(
                            'DELETE
                             FROM   `d_challenges`
                             WHERE  `d_challenges`.`ip` = :ip'
                        );

                    $statement->execute([ 'ip' => $_SERVER['REMOTE_ADDR'] ]);

                    $statement =
                        $database->prepare(
                            'DELETE
                             FROM   `d_messages_private`
                             WHERE  `d_messages_private`.`recipient` = :recipient'
                        );

                    $statement->execute([ 'recipient' => $match['id'] ]);

                    redirect('goodbye.php');
                } else {
                    setUserMailAddress($match['mailAddress']);
                }

                setAllowance($match['allowance']);

                redirect('private/?fromLogin');
            } else {
                redirect(SYSTEM_HOSTNAME . '?e=' . QUICKSTART['INVALID_MAIL_ADDRESS']);
            }
        } else {
            redirect(SYSTEM_HOSTNAME . '?e=' . EXPIRED_TOKEN);
        }
    } else {
        redirect(SYSTEM_HOSTNAME . '?e=' . EXPIRED_TOKEN);
    }
}