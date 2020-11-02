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
                $statement = $GLOBALS['database']->prepare(
                    'SELECT
                        d_ads.id,
                        d_ads.content,
                        d_ads.company AS companyId,
                        d_companies.name AS companyName
                     FROM       d_ads
                     JOIN       d_companies ON d_companies.id = d_ads.company
                     ORDER BY   RAND()
                     LIMIT 1'
                );

                $statement->execute();

                reply($statement->fetch());

                break;
            case 'reportImpression':
                if (isset($values['id']) && !empty($values['id']) && is_numeric($values['id'])) {
                    $statement = $GLOBALS['database']->prepare(
                        'UPDATE `d_ads`
                         SET    `impressions` = `impressions` + 1
                         WHERE  `id` = :id'
                    );

                    $statement->execute([ 'id' => $values['id'] ]);

                    reply(null);
                } else {
                    reply(null, BAD_REQUEST);
                }
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