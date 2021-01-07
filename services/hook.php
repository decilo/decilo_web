<?php

header('Content-Type: application/json');

chdir('..');

require_once 'includes/main.php';
require_once 'vendor/autoload.php';

$request = json_decode(
    file_get_contents('php://input'),
    true
);

if (empty($request)) {
    http_response_code(400);

    redirect(SYSTEM_HOSTNAME . 'exceptions/bad_request');
} else {
    $statement = $database->prepare(
        'INSERT INTO `d_received_hooks` (
            `body`
         ) VALUES (
            :body
         )'
    );

    $statement->execute([ 'body' => json_encode($request) ]);

    if ($statement->rowCount() < 1) {
        http_response_code(503);

        reply([ 'message' => 'A database error has occured, please try again later.' ]);
    } else {
        switch ($request['type']) {
            case 'subscription':
                $id = $request['data']['id'];

                $request = curl_init('https://api.mercadopago.com/preapproval/search?id=' . $id);

                curl_setopt_array($request, [
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . MERCADOPAGO_KEYS['PRIVATE']
                    ],
                    CURLOPT_RETURNTRANSFER => true
                ]);

                $subscriptions = json_decode(curl_exec($request), true);

                if (
                    $subscriptions == null
                    ||
                    empty($subscriptions)
                    ||
                    curl_getinfo($request, CURLINFO_HTTP_CODE) != 200
                    ||
                    count($subscriptions) < 1
                ) {
                    http_response_code(404);

                    reply([ 'message' => 'The specified subscription does not exist.' ]);
                } else {
                    $subscription = &$subscriptions['results'][0];

                    if ($subscription['status'] == 'cancelled') {
                        $statement = $database->prepare(
                            'UPDATE `d_subscriptions`
                             SET    `d_subscriptions`.`active`  = FALSE
                             WHERE  `d_subscriptions`.`token`   = :token'
                        );

                        $statement->execute([ 'token' => $id ]);

                        if ($statement->rowCount() > -1) {
                            http_response_code(200);

                            reply([ 'message' => 'The specified subscription is now marked as cancelled.' ]);
                        } else {
                            http_response_code(503);

                            reply([ 'message' => 'A database error has occured, please try again later.' ]);
                        }
                    } else {
                        reply([ 'message' => 'Nothing to do.' ]);
                    }
                }

                break;
            case 'test':
                http_response_code(200);

                reply([ 'message' => 'Received and processed, the server is working properly.' ]);
        }
    }
}

?>