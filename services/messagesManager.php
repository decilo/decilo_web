<?php

use \Gumlet\ImageResize;

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
                $private =
                    (isset($values['private']) && $values['private'] !== false)
                    ||
                    isset($values['recipient']);

                $messagesTable = 'd_messages_' . ($private ? 'private' : 'public');

                $position = -1;

                if (isset($values['startAt']) && is_numeric($values['startAt'])) {
                    $position = $values['startAt'];
                }

                $statement = $database->prepare('SET @position := :position');
                $statement->bindValue(':position', $position, PDO::PARAM_INT);
                $statement->execute();

                $sortBy = null;

                $afterDirection = '<';

                if (isset($values['sortBy'])) {
                    switch ($values['sortBy']) {
                        case SORTING_METHODS['BY_RELEVANCE']:
                            $sortBy = 'score';

                            $afterDirection = '>';

                            break;
                        case SORTING_METHODS['BY_DATE']:
                            $sortBy = 'id';

                            break;
                        case SORTING_METHODS['BY_LIKES']:
                            $sortBy = 'likes';

                            break;
                        case SORTING_METHODS['BY_COMMENTS']:
                            $sortBy = 'comments';

                            $afterDirection = '>';

                            break;
                    }
                }

                $query =  getParsedString(
                    'SELECT
                        *,
                        @position := @position + 1 AS position
                     FROM (
                        SELECT * {scoreCalculation}
                        FROM (
                            SELECT *
                            FROM (
                                SELECT
                                    `{messagesTable}`.`id`,
                                    `{messagesTable}`.`declaredName`,
                                    {likesCount}
                                    `{messagesTable}`.`created`, (
                                    SELECT  COUNT(*)
                                    FROM    `d_reports`
                                    WHERE   `d_reports`.`message`    = `{messagesTable}`.`id`
                                    AND     `d_reports`.`reportedBy` = :userId
                                    AND     `d_reports`.`private`    = :private
                                ) > 0 AS reported, (
                                    SELECT  `d_images`.`url`
                                    FROM    `d_images`
                                    WHERE   `d_images`.`message`     = `{messagesTable}`.`id`
                                    AND     `d_images`.`private`     = :private
                                ) AS image,
                                CASE WHEN (
                                    SELECT  COUNT(*)
                                    FROM    `d_images`
                                    WHERE   `d_images`.`message`     = `{messagesTable}`.`id`
                                    AND     `d_images`.`private`     = :private
                                ) > 0
                                THEN (
                                    CASE WHEN (
                                        SELECT  COUNT(*)
                                        FROM    `d_images_analyzed`
                                        WHERE   `d_images_analyzed`.`image` = (
                                            SELECT  `d_images`.`id`
                                            FROM    `d_images`
                                            WHERE   `d_images`.`message`     = `{messagesTable}`.`id`
                                            AND     `d_images`.`private`     = :private
                                        )
                                    ) > 0
                                    THEN true
                                    ELSE false
                                    END
                                )
                                ELSE true
                                END AS verified,
                                CASE WHEN CHARACTER_LENGTH(`{messagesTable}`.`content`) > :maxLength
                                THEN
                                    CONCAT(
                                        SUBSTRING(
                                                `{messagesTable}`.`content`,
                                                1,
                                                :maxLength
                                        ),
                                        \'…\'
                                    )
                                ELSE `{messagesTable}`.`content`
                                END AS content, (
                                    SELECT  COUNT(*)
                                    FROM    `d_comments`
                                    WHERE   `d_comments`.`message` = `{messagesTable}`.`id`
                                    AND     `d_comments`.`private` = :private
                                ) AS comments
                                FROM       `{messagesTable}`
                                {usersJoint}
                                WHERE 1 = 1
                                {recipient}
                            ) AS messageSet
                        ) AS scoringSet
                     ) AS scoringResult
                     {countFrom}
                     ORDER BY {sortBy} DESC
                     LIMIT :limit'
                , [
                    'usersJoint'        => $private ?
                    'JOIN       `d_users`
                     ON         `d_users`.`id`           = `{messagesTable}`.`recipient`
                     AND        `d_users`.`username`     = :recipient' : '',
                    'recipient'         => $private ?
                    'AND        `{messagesTable}`.`recipient` = :userId' : '',
                    'countFrom'         => isset($values['after']) ?
                    'WHERE      `id` {afterDirection} :after' : '',
                    'sortBy'            => $sortBy,
                    'scoreCalculation'  => $private ? '' : ', ((comments + likes) / 2) AS score',
                    'likesCount'        => $private ? '' : '`{messagesTable}`.`likes`, '
                ]);

                $query = getParsedString($query, [
                    'messagesTable'     => $messagesTable,
                    'afterDirection'    => $afterDirection
                ]);

                $statement = $database->prepare($query);

                if (isset($values['after'])) {
                    $statement->bindValue(':after', $values['after'], PDO::PARAM_INT);
                }

                if ($private) {
                    $statement->bindValue(':recipient', $values['recipient']);
                }

                $statement->bindValue(':userId'   , getUserId()                                      );
                $statement->bindValue(':maxLength', MESSAGES['MAX_LENGTH']          , PDO::PARAM_INT );
                $statement->bindValue(':limit'    , INDEX['PUBLIC_MESSAGES_LIMIT']  , PDO::PARAM_INT );
                $statement->bindValue(':private'  , $private                        , PDO::PARAM_BOOL);

                $statement->execute();

                reply($statement->fetchAll());

                break;
            case 'postMessage':
                if (
                    isset($values['content'])   && !empty($values['content'])
                    &&
                    isset($values['token'])     && !empty($values['token'])
                ) {
                    if (verifyCaptcha($values['token'])) {
                        if (isset($values['recipient'])) {
                            $statement = $database
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
                            $statement = $database
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
                                    'id'    => (int) $database->lastInsertId(),
                                    'image' => null
                                ]
                            );
                        } else {
                            if (strpos($values['image'], 'image') !== false) {
                                try {
                                    $messageId = (int) $database->lastInsertId();

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

                                    $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

                                    file_put_contents($filePath, $image);

                                    $url = uploadImage($filePath, $filename);

                                    unlink($filePath);

                                    $statement =
                                        $database->prepare(
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

                                    $statement->execute(
                                        [
                                            'url'       => $url,
                                            'message'   => $messageId,
                                            'private'   => isset($values['recipient']) ? 1 : 0
                                        ]
                                    );

                                    reply(
                                        [
                                            'id'    => (int) $database->lastInsertId(),
                                            'image' => $url
                                        ]
                                    );
                                } catch (Exception $exception) {
                                    reply(
                                        [
                                            'id'    => (int) $database->lastInsertId(),
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
                            $database->prepare(
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
                            $database->prepare(
                                'SELECT COUNT(*) AS count
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

                        if ($statement->fetch()['count'] > 0) {
                            reply(null, ALREADY_EXISTS);
                        } else {
                            $statement =
                                $database->prepare(
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
            case 'toggleLike':
                if (isset($values['id']) && is_numeric($values['id'])) {
                    $isLiked = false;

                    $likedMessages = getLikedMessages();

                    foreach ($likedMessages as $key => $likedMessage) {
                        if ($likedMessage == $values['id']) {
                            $isLiked = true;

                            unset($likedMessages[$key]);
                        }
                    }

                    $statement = $database->prepare(
                        'UPDATE `d_messages_public`
                         SET    `d_messages_public`.`likes` = `d_messages_public`.`likes` + (:likeCount)
                         WHERE  `d_messages_public`.`id`    = :id'
                    );

                    if ($isLiked) {
                        $statement->execute([ 'likeCount' => -1, 'id' => $values['id'] ]);
                    } else {
                        $statement->execute([ 'likeCount' =>  1, 'id' => $values['id'] ]);

                        $likedMessages[] = $values['id'];
                    }

                    setLikedMessages($likedMessages);

                    reply([ 'wasLiked' => $isLiked, 'liked' => $likedMessages ], $statement->rowCount() > 0 ? OK : ERROR);
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