<?php

use \Gumlet\ImageResize;
use Google\Cloud\Storage\StorageClient;

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
                if (isset($values['after']) && !isset($values['private'])) {
                    $statement = $GLOBALS['database']
                        ->prepare(
                            'SELECT     `d_messages_public`.*, (
                                SELECT  COUNT(*)
                                FROM    `d_reports`
                                WHERE   `d_reports`.`message`    = `d_messages_public`.`id`
                                AND     `d_reports`.`reportedBy` = :userId
                                AND     `d_reports`.`private`    = FALSE
                             ) > 0 AS reported, (
                                SELECT  `d_images`.`url`
                                FROM    `d_images`
                                WHERE   `d_images`.`message`     = `d_messages_public`.`id`
                                AND     `d_images`.`private`     = FALSE
                             ) AS image
                             FROM       `d_messages_public`
                             WHERE      `id` < :after
                             ORDER BY   `id` DESC
                             LIMIT      ' . INDEX['PUBLIC_MESSAGES_LIMIT']
                        );

                    $statement->execute([
                        'after'     => $values['after'],
                        'userId'    => getUserId()
                    ]);

                    reply($statement->fetchAll());
                } else if (isset($values['private']) && $values['private']) {
                    $statement = $GLOBALS['database']
                        ->prepare(
                            'SELECT     `d_messages_private`.*, (
                                SELECT  COUNT(*)
                                FROM    `d_reports`
                                WHERE   `d_reports`.`message`    = `d_messages_private`.`id`
                                AND     `d_reports`.`reportedBy` = :userId
                                AND     `d_reports`.`private`    = FALSE
                             ) > 0 AS reported, (
                                SELECT  `d_images`.`url`
                                FROM    `d_images`
                                WHERE   `d_images`.`message` = `d_messages_private`.`id`
                                AND     `d_images`.`private` = TRUE
                             ) AS image
                             FROM       `d_messages_private`
                             WHERE      TRUE' . (isset($values['after']) ? '
                             AND        `d_messages_private`.`id` < :after' : '') . '
                             AND        `d_messages_private`.`recipient` = :userId
                             ORDER BY   `id` DESC
                             LIMIT      ' . MESSAGES['PUBLIC_MESSAGES_LIMIT']
                        );

                    $params = [ 'userId' => getUserId() ];

                    if (isset($values['after'])) {
                        $params['after'] = $values['after'];
                    }

                    $statement->execute($params);

                    reply($statement->fetchAll());
                } else if (isset($values['recipient']) && !empty($values['recipient'])) {
                    $statement = $GLOBALS['database']
                        ->prepare(
                            'SELECT     `d_messages_private`.*, (
                                SELECT  COUNT(*)
                                FROM    `d_reports`
                                WHERE   `d_reports`.`message`    = `d_messages_private`.`id`
                                AND     `d_reports`.`reportedBy` = :userId
                                AND     `d_reports`.`private`    = TRUE
                             ) > 0 AS reported, (
                                SELECT  `d_images`.`url`
                                FROM    `d_images`
                                WHERE   `d_images`.`message`     = `d_messages_private`.`id`
                                AND     `d_images`.`private`     = TRUE
                             ) AS image
                             FROM       `d_messages_private`
                             JOIN       `d_users`
                                ON      `d_users`.`id`           = `d_messages_private`.`recipient`
                                AND     `d_users`.`username`     = :recipient
                             ORDER BY   `d_messages_private`.`id` DESC
                             LIMIT      ' . INDEX['PUBLIC_MESSAGES_LIMIT']
                        );

                    $statement->execute([
                        'userId'    => getUserId(),
                        'recipient' => $values['recipient']
                    ]);

                    reply($statement->fetchAll());
                } else if (!isset($values['recipient']) || $values['recipient'] == null) {
                    $statement = $GLOBALS['database']
                        ->prepare(
                            'SELECT     `d_messages_public`.*, (
                                SELECT  COUNT(*)
                                FROM    `d_reports`
                                WHERE   `d_reports`.`message`    = `d_messages_public`.`id`
                                AND     `d_reports`.`reportedBy` = :userId
                                AND     `d_reports`.`private`     = FALSE
                             ) > 0 AS reported, (
                                SELECT  `d_images`.`url`
                                FROM    `d_images`
                                WHERE   `d_images`.`message`     = `d_messages_public`.`id`
                                AND     `d_images`.`private`     = FALSE
                             ) AS image
                             FROM       `d_messages_public`
                             ORDER BY   `id` DESC
                             LIMIT      ' . INDEX['PUBLIC_MESSAGES_LIMIT']
                        );

                    $statement->execute([ 'userId' => getUserId() ]);

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
                        }

                        if ($values['image'] == null) {
                            reply(
                                [
                                    'id'    => (int) $GLOBALS['database']->lastInsertId(),
                                    'image' => null
                                ]
                            );
                        } else {
                            if (strpos($values['image'], 'image') !== false) {
                                try {
                                    $messageId = (int) $GLOBALS['database']->lastInsertId();

                                    $values['image'] = explode(',', $values['image'])[1];

                                    $image = ImageResize::createFromString(
                                        base64_decode($values['image'])
                                    );

                                    $image->resizeToBestFit(
                                        IMAGE_PROCESSING['CROP_WIDTH'],
                                        IMAGE_PROCESSING['CROP_HEIGHT']
                                    );

                                    $image = $image->getImageAsString(IMAGE_PROCESSING['FORMAT'], IMAGE_PROCESSING['QUALITY']);

                                    $filename = sha1($image) . IMAGE_PROCESSING['EXTENSION'];

                                    $storage = new StorageClient([
                                        'projectId' => GOOGLE_CLOUD_PROJECT_ID,
                                        'keyFile'   => GOOGLE_CLOUD_KEYFILE
                                    ]);

                                    $storage
                                        ->bucket(GOOGLE_CLOUD_BUCKET_NAME)
                                        ->upload($image, [
                                                'name'      => $filename,
                                                'acl'       => []
                                            ]
                                        );

                                    $statement =
                                        $GLOBALS['database']->prepare(
                                            'INSERT INTO `d_images` (
                                                `url`,
                                                `message`,
                                                `private`
                                             ) VALUES (
                                                :url,
                                                :message,
                                                :private
                                             )'
                                        );

                                    $url = getParsedString(GOOGLE_CLOUD_PUBLIC_URL, [
                                        'bucketName'    => GOOGLE_CLOUD_BUCKET_NAME,
                                        'objectName'    => $filename
                                    ]);

                                    $statement->execute(
                                        [
                                            'url'       => $url,
                                            'message'   => $messageId,
                                            'private'   => isset($values['recipient']) ? 1 : 0
                                        ]
                                    );

                                    reply(
                                        [
                                            'id'    => (int) $GLOBALS['database']->lastInsertId(),
                                            'image' => $url
                                        ]
                                    );
                                } catch (Exception $exception) {
                                    reply(
                                        [
                                            'id'    => (int) $GLOBALS['database']->lastInsertId(),
                                            'image' => null,
                                            'error' => $exception->getMessage()
                                        ]
                                    );
                                }
                            } else {
                                reply(null, ERROR);
                            }
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
                                 AND    `d_messages_private`.`recipient` = :recipient;

                                 DELETE
                                 FROM   `d_images`
                                 WHERE  `d_images`.`message`             = :id
                                 AND    `d_images`.`private`             = TRUE;'
                            );

                        $statement->execute([
                            'id'        => $values['id'],
                            'recipient' => getUserId()
                        ]);

                        reply(null, $statement->rowCount() > 0 ? OK : ERROR);
                    } else {
                        reply(null, BAD_REQUEST);
                    }
                }

                break;
            case 'reportMessage':
                $userId = getUserId();

                if (
                    isset($values['id'])
                    &&
                    isset($values['reason'])
                    &&
                    isset($values['private'])
                    &&
                    getMessage($values['id']) != null
                    &&
                    isReportReasonValid($values['reason'])
                ) {
                    if ($userId == null) {
                        reply(null, NOT_ALLOWED);
                    } else {
                        $statement =
                            $GLOBALS['database']->prepare(
                                'SELECT COUNT(*)
                                 FROM   `d_reports`
                                 WHERE  `d_reports`.`message`    = :message
                                 AND    `d_reports`.`reportedBy` = :reportedBy
                                 AND    `d_reports`.`private`    = :private'
                            );

                        $statement->execute([
                            'message'       => $values['id'],
                            'reportedBy'    => $userId,
                            'private'       => $values['private']
                        ]);

                        if ($statement->fetch()[0] > 0) {
                            reply(null, ALREADY_EXISTS);
                        } else {
                            $statement =
                                $GLOBALS['database']->prepare(
                                    'INSERT INTO `d_reports` (
                                        `message`,
                                        `reason`,
                                        `reportedBy`,
                                        `private`
                                     ) VALUES (
                                        :message,
                                        :reason,
                                        :reportedBy,
                                        :private
                                     )'
                                );

                            $statement->execute([
                                'message'       => $values['id'],
                                'reason'        => $values['reason'],
                                'reportedBy'    => $userId,
                                'private'       => $values['private']
                            ]);

                            reply(null);
                        }
                    }
                } else {
                    reply(null, BAD_REQUEST);
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