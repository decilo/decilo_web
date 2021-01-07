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
            case 'getRandomAd':
                $statement = $database->prepare(
                    'SELECT
                        d_ads.id,
                        d_ads.content,
                        d_ads.company AS companyId,
                        d_companies.name AS companyName
                     FROM       d_ads
                     JOIN       d_companies     ON d_companies.id           = d_ads.company
                     JOIN       d_subscriptions ON d_subscriptions.company  = d_companies.id
                     WHERE      d_ads.approved
                     AND        d_subscriptions.active
                     ORDER BY   RAND()
                     LIMIT 1'
                );

                $statement->execute();

                reply($statement->fetch());

                break;
            case 'reportImpression':
                if (isset($values['id']) && !empty($values['id']) && is_numeric($values['id'])) {
                    $statement = $database->prepare(
                        'UPDATE `d_ads`
                         SET    `impressions` = `impressions` + 1
                         WHERE  `id` = :id'
                    );

                    $statement->execute([ 'id' => $values['id'] ]);

                    reply(null);
                } else {
                    reply(null, BAD_REQUEST);
                }

                break;
            case 'createAd':
                if (
                    isset($values['company'])  && isset($values['content'])
                    &&
                    !empty($values['company']) && !empty($values['content'])
                    &&
                    is_numeric($values['company'])
                ) {
                    if (isOwnerOf($values['company'])) {
                        $statement = $database->prepare(
                            'INSERT INTO `d_ads` (
                                `content`,
                                `company`
                             ) VALUES (
                                :content,
                                :company
                             )'
                        );

                        $statement->execute([
                            'content'   => $values['content'],
                            'company'   => $values['company']
                        ]);

                        reply(
                            [ 'id' => $database->lastInsertId() ],
                            $statement->rowCount() > 0 ? OK : ERROR
                        );
                    } else {
                        reply(null, NO_SUCH_ELEMENT);
                    }
                } else {
                    reply(null, BAD_REQUEST);
                }

                break;
            case 'tryToRemoveAd':
                if (isset($values['id']) && is_numeric($values['id'])) {
                    $ad = getAd($values['id']);

                    if ($ad == null) {
                        reply(null, NO_SUCH_ELEMENT);
                    } else {
                        if (isOwnerOf($ad['company'])) {
                            $statement = $database->prepare(
                                'DELETE
                                 FROM   `d_ads`
                                 WHERE  `d_ads`.`id` = :id'
                            );

                            $statement->execute([ 'id' => $values['id'] ]);

                            reply(null, $statement->rowCount() > 0 ? OK : ERROR);
                        } else {
                            reply(null, NOT_ALLOWED);
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
            default:
                reply(null, WHAT_THE_FUCK);
        }
    }
}

?>