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
            case 'getRecent':
                if (isset($values['after'])) {
                    $statement = $GLOBALS['database']
                        ->prepare(
                            'SELECT     *
                             FROM       `d_messages_public`
                             WHERE      `id` < :after
                             ORDER BY   `created` DESC
                             LIMIT      ' . INDEX['PUBLIC_MESSAGES_LIMIT']
                        );

                    $statement->execute([ 'after' => $values['after'] ]);

                    reply($statement->fetchAll());
                } else if (isset($values['private']) && $values['private']) {
                    $statement = $GLOBALS['database']
                        ->prepare(
                            'SELECT     *
                             FROM       `d_messages_private`
                             ORDER BY   `created` DESC
                             LIMIT      ' . MESSAGES['PUBLIC_MESSAGES_LIMIT']
                        );

                    $statement->execute();

                    reply($statement->fetchAll());
                } else if (isset($values['recipient']) && !empty($values['recipient'])) {
                    $statement = $GLOBALS['database']
                        ->prepare(
                            'SELECT     *
                             FROM       `d_messages_private`
                             JOIN       `d_users`
                                ON      `d_users`.`id`          = `d_messages_private`.`recipient`
                                AND     `d_users`.`username`    = :recipient
                             ORDER BY   `d_messages_private`.`created` DESC
                             LIMIT      ' . INDEX['PUBLIC_MESSAGES_LIMIT']
                        );

                    $statement->execute([ 'recipient' => $values['recipient'] ]);

                    reply($statement->fetchAll());
                } else if (!isset($values['recipient']) || $values['recipient'] == null) {
                    $statement = $GLOBALS['database']
                        ->prepare(
                            'SELECT     *
                             FROM       `d_messages_public`
                             ORDER BY   `created` DESC
                             LIMIT      ' . INDEX['PUBLIC_MESSAGES_LIMIT']
                        );

                    $statement->execute();

                    reply($statement->fetchAll());
                } else {
                    reply($values, BAD_REQUEST);
                }

                break;
            case 'postMessage':
                if (
                    isset($values['content'])   && !empty($values['content'])
                    &&
                    isset($values['token'])     && !empty($values['token'])
                ) {
                    if (verifyCaptcha($values['token'])) {
                        if (isset($values['recipient'])) {
                            $statement = $GLOBALS['database']
                                ->prepare(
                                    'INSERT INTO `d_messages_private` (
                                        `content`,
                                        `declaredName`,
                                        `recipient`
                                    ) VALUES (
                                        :content,
                                        :declaredName,
                                        (
                                            SELECT  `d_users`.`id`
                                            FROM    `d_users`
                                            WHERE   `d_users`.`username` = :recipient
                                        )
                                    )'
                                );

                            $statement->execute(
                                [
                                    'content'       => trim(strip_tags($values['content'])),
                                    'declaredName'  => $values['declaredName'],
                                    'recipient'     => $values['recipient']
                                ]
                            );

                            reply((int) $GLOBALS['database']->lastInsertId());
                        } else {
                            $statement = $GLOBALS['database']
                                ->prepare(
                                    'INSERT INTO `d_messages_public` (
                                        `content`,
                                        `declaredName`
                                    ) VALUES (
                                        :content,
                                        :declaredName
                                    )'
                                );

                            $statement->execute(
                                [
                                    'content'       => trim(strip_tags($values['content'])),
                                    'declaredName'  => $values['declaredName']
                                ]
                            );

                            reply((int) $GLOBALS['database']->lastInsertId());
                        }
                    } else {
                        reply(null, SUSPICIOUS_OPERATION);
                    }
                } else {
                    reply($values, BAD_REQUEST);
                }

                break;
            case 'tryToRemove':
                $userId = getUserId();

                if ($userId == null) {
                    reply(null, NOT_ALLOWED);
                } else {
                    if (isset($values['id'])) {
                        $statement = 
                            $GLOBALS['database']->prepare(
                                'DELETE
                                 FROM   `d_messages_private`
                                 WHERE  `d_messages_private`.`id`        = :id
                                 AND    `d_messages_private`.`recipient` = :recipient'
                            );

                        $statement->execute([
                            'id'        => $values['id'],
                            'recipient' => getUserId()
                        ]);

                        reply(null, $statement->rowCount() == 1 ? OK : ERROR);
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
            case 'getRecent':
                $statement = $GLOBALS['database']
                    ->prepare(
                        'SELECT     *
                         FROM       `d_messages_public`
                         ORDER BY   `created` DESC
                         LIMIT      ' . INDEX['PUBLIC_MESSAGES_LIMIT']  
                );

                if ($statement !== false) {
                    $statement->execute();

                    reply($statement->fetchAll());
                } else {
                    reply(null, NOT_READY);
                }

                break;
            case 'areYouThere?':
                if (getUserId() == null) {
                    reply(['message' => 'Yes, but no session is currently active.'], NOT_ALLOWED);
                } else {
                    reply(['message' => 'Here I am!'], OK);
                }
            default:
                reply(null, WHAT_THE_FUCK);
        }
    }
}

?>