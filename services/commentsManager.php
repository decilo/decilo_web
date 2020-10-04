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
            case 'getComments':
                if (
                     isset($values['message']) &&  isset($values['private'])
                    &&
                    !empty($values['message'])
                ) {
                    $statement =
                        $GLOBALS['database']->prepare(
                            'SELECT     *
                             FROM       `d_comments`
                             WHERE      `d_comments`.`message` = :message
                             AND        `d_comments`.`private` = :private
                             ORDER BY   `d_comments`.`id` DESC'
                        );

                    $statement->execute([
                        'message'   => $values['message'],
                        'private'   => $values['private']
                    ]);

                    reply($statement->fetchAll());
                } else {
                    reply(null, BAD_REQUEST);
                }

                break;
            case 'postComment':
                if (
                    isset($values['content'])
                    &&
                    isset($values['message'])
                    &&
                    isset($values['private'])
                    &&
                    !empty($values['content'])
                    &&
                    is_numeric($values['message'])
                ) {
                    $statement =
                        $GLOBALS['database']->prepare(
                            'INSERT INTO `d_comments` (
                                `content`,
                                `declaredName`,
                                `message`,
                                `private`
                             ) VALUES (
                                :content,
                                :declaredName,
                                :message,
                                :private
                             );'
                        );

                    $statement->execute([
                        'content'       => $values['content'],
                        'declaredName'  => $values['declaredName'],
                        'message'       => $values['message'],
                        'private'       => $values['private'] ? 1 : 0
                    ]);

                    reply(
                        [ 'id' => (int) $GLOBALS['database']->lastInsertId() ],
                        $statement->rowCount() > 0 ? OK : ERROR
                    );
                } else {
                    reply(null, BAD_REQUEST);
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
            default:
                reply(null, WHAT_THE_FUCK);
        }
    }
}

?>