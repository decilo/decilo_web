<?php
header('Content-Type: application/json');

chdir('..');

require_once 'includes/main.php';
require_once 'vendor/autoload.php';

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
            case 'saveCompany':
                if (
                    isset($values['id'])    && $values['id'] != null
                    &&
                    isset($values['name'])  && isset($values['legalName'])  && isset($values['identifier'])
                    &&
                    !empty($values['name']) && !empty($values['legalName']) && !empty($values['identifier'])
                ) {
                    $user = getCurrentUser();

                    if ($user == null) {
                        reply(null, NOT_ALLOWED);
                    } else {
                        $statement =
                            $GLOBALS['database']->prepare(
                                'UPDATE `d_companies`
                                 SET
                                    `name`          = :name,
                                    `legalName`     = :legalName,
                                    `identifier`    = REPLACE( :identifier, \'-\', \'\' )
                                 WHERE  `id`    = :id
                                 AND    `owner` = :owner'
                            );

                        $statement->execute([
                            'name'          => $values['name'],
                            'legalName'     => $values['legalName'],
                            'identifier'    => $values['identifier'],
                            'id'            => $values['id'],
                            'owner'         => $user['id']
                        ]);

                        if ($statement->rowCount() > -1) {
                            setUserHasCompanies(true);
                        }

                        reply([ 'isIdentifierValid' => true ], $statement->rowCount() > -1 ? OK : NO_SUCH_ELEMENT);
                    }
                } else {
                    $statement =
                        $GLOBALS['database']->prepare(
                            'SELECT COUNT(*) AS count
                             FROM   `d_companies`
                             WHERE
                                REPLACE(`d_companies`.`identifier`, \'-\', \'\')
                                =
                                REPLACE(:identifier, \'-\', \'\')'
                        );

                    $statement->execute([ 'identifier' => $values['identifier'] ]);

                    if ($statement->fetch()['count'] > 0) {
                        reply(null, ALREADY_EXISTS);
                    } else {
                        if (
                            isset($values['name'])  && isset($values['legalName'])  && isset($values['identifier'])
                            &&
                            !empty($values['name']) && !empty($values['legalName']) && !empty($values['identifier'])
                        ) {
                            $user = getCurrentUser();

                            if ($user == null) {
                                reply(null, NOT_ALLOWED);
                            } else {
                                if (
                                    \ArgentinaDataValidator\Cuit::isValid(
                                        str_replace('-', '', $values['identifier'])
                                    )
                                ) {
                                    $statement =
                                        $GLOBALS['database']->prepare(
                                            'INSERT INTO `d_companies` (
                                                `name`,
                                                `legalName`,
                                                `owner`,
                                                `identifier`
                                            ) VALUES (
                                                :name,
                                                :legalName,
                                                :owner,
                                                REPLACE(:identifier, \'-\', \'\')
                                            )'
                                        );

                                    $statement->execute([
                                        'name'          => $values['name'],
                                        'legalName'     => $values['legalName'],
                                        'owner'         => $user['id'],
                                        'identifier'    => $values['identifier']
                                    ]);

                                    if ($statement->rowCount() > 0) {
                                        setUserHasCompanies(true);

                                        reply([
                                            'companies'         => getCompaniesForUser($user['id']),
                                            'isIdentifierValid' => true
                                        ]);
                                    } else {
                                        reply(null, ERROR);
                                    }
                                } else {
                                    reply([ 'isIdentifierValid' => false ]);
                                }
                            }
                        } else {
                            reply(null, BAD_REQUEST);
                        }
                    }
                }

                break;
            case 'saveSubscription':
                if (getUserId() == null) {
                    reply(null, NOT_ALLOWED);
                } else {
                    $companies = getCompaniesForUser();

                    if (count($companies) > 0) {
                        $company = $companies[0];

                        if (
                            isset($values['cardToken'])  && isset($values['mailAddress'])
                            &&
                            !empty($values['cardToken']) && !empty($values['mailAddress'])
                            &&
                            filter_var($values['mailAddress'], FILTER_VALIDATE_EMAIL) !== false
                        ) {

                            $request = curl_init('https://api.mercadopago.com/preapproval');

                            curl_setopt_array($request, [
                                CURLOPT_HTTPHEADER     => [
                                    'Content-Type: application/json',
                                    'Authorization: Bearer ' . MERCADOPAGO_KEYS['PRIVATE']
                                ],
                                CURLOPT_POSTFIELDS      => json_encode([
                                    'preapproval_plan_id'   => MERCADOPAGO_SUBSCRIPTION_PLAN_ID,
                                    'card_token_id'         => $values['cardToken'],
                                    'payer_email'           => $values['mailAddress']
                                ]),
                                CURLOPT_RETURNTRANSFER => true
                            ]);

                            $subscription = json_decode(curl_exec($request), true);

                            if ($subscription['status'] == 'authorized') {
                                $statement = $GLOBALS['database']->prepare(
                                    'INSERT INTO `d_subscriptions` (
                                        `company`,
                                        `token`
                                     ) VALUES (
                                        :company,
                                        :token
                                     )'
                                );

                                $statement->execute([
                                    'company'   => $company['id'],
                                    'token'     => $subscription['id']
                                ]);

                                if ($statement->rowCount() > 0) {
                                    $subscription['internalId'] = $GLOBALS['database']->lastInsertId();

                                    reply([ 'subscription' => $subscription ]);
                                } else {
                                    reply([ 'subscription' => $subscription ], ERROR);
                                }
                            } else {
                                reply([ 'subscription' => $subscription ], ERROR);
                            }
                        } else {
                            reply(null, BAD_REQUEST);
                        }
                    } else {
                        reply(null, BAD_REQUEST);
                    }
                }

                break;
            case 'cancelSubscription':
                if (getUserId() == null) {
                    reply(null, NOT_ALLOWED);
                } else {
                    if (isset($values['id']) && is_numeric($values['id'])) {
                        $subscription = getSubscription($values['id']);

                        if ($subscription == null) {
                            reply(null, NO_SUCH_ELEMENT);
                        } else {
                            if (isOwnerOf($subscription['company'])) {
                                $request = curl_init('https://api.mercadopago.com/preapproval/' . $subscription['token']);

                                curl_setopt_array($request, [
                                    CURLOPT_HTTPHEADER      => [
                                        'Content-Type: application/json',
                                        'Authorization: Bearer ' . MERCADOPAGO_KEYS['PRIVATE']
                                    ],
                                    CURLOPT_CUSTOMREQUEST   => 'PUT',
                                    CURLOPT_POSTFIELDS      => json_encode([ 'status' => 'cancelled' ]),
                                    CURLOPT_RETURNTRANSFER  => true
                                ]);

                                $result = json_decode(curl_exec($request), true);

                                if ($result['status'] == 'cancelled' || $result['status'] == 400) {
                                    $statement = $GLOBALS['database']->prepare(
                                        'UPDATE `d_subscriptions`
                                         SET    `d_subscriptions`.`active`  = FALSE
                                         WHERE  `d_subscriptions`.`id`      = :subscription'
                                    );

                                    $statement->execute([ 'subscription' => $subscription['id'] ]);

                                    if ($statement->rowCount() > 0) {
                                        reply($result);
                                    } else {
                                        reply($result, ERROR);
                                    }
                                } else {
                                    reply($result, ERROR);
                                }
                            } else {
                                reply(null, NOT_ALLOWED);
                            }
                        }
                    } else {
                        reply(null, BAD_REQUEST);
                    }
                }

                break;
            case 'tryToDeleteCompany':
                $userId = getUserId();

                if ($userId == null) {
                    reply(null, NOT_ALLOWED);
                } else {
                    if (isset($values['company']) && is_numeric($values['company'])) {
                        $company = getCompany($values['company']);

                        if ($company == null || $company['owner'] != $userId) {
                            reply(null, NO_SUCH_ELEMENT);
                        } else {
                            if ($company['isBillingEnabled'] == 1) {
                                reply(null, NOT_READY);
                            } else {
                                // BEGIN: ADs block
                                $statement = $GLOBALS['database']->prepare(
                                    'DELETE
                                    FROM   `d_ads`
                                    WHERE  `d_ads`.`company` = :company'
                                );

                                $statement->execute([ 'company' => $company['id'] ]);
                                // END: ADs block

                                // BEGIN: Subscriptions block
                                $statement = $GLOBALS['database']->prepare(
                                    'DELETE
                                     FROM   `d_subscriptions`
                                     WHERE  `d_subscriptions`.`company` = :company'
                                );

                                $statement->execute([ 'company' => $company['id'] ]);
                                // END: Subscriptions block

                                // BEGIN: Company block
                                $statement = $GLOBALS['database']->prepare(
                                    'DELETE
                                     FROM   `d_companies`
                                     WHERE  `d_companies`.`id` = :company'
                                );

                                $statement->execute([ 'company' => $company['id'] ]);
                                // END: Company block

                                setUserHasCompanies(
                                    count(getCompaniesForUser()) > 0
                                );

                                reply(null);
                            }
                        }
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
            case 'generateSubscriptionPlan':
                $allowance = getAllowance();

                if ($allowance == USER_LEVEL_OWNER) {
                    $request = curl_init('https://api.mercadopago.com/preapproval_plan');

                    curl_setopt_array($request, [
                        CURLOPT_HTTPHEADER     => [
                            'Content-Type: application/json',
                            'Authorization: Bearer ' . MERCADOPAGO_KEYS['PRIVATE']
                        ],
                        CURLOPT_POSTFIELDS     => json_encode([
                            'back_url'          => SYSTEM_HOSTNAME . '/company/billing',
                            'reason'            => 'Servicios publicitarios',
                            'auto_recurring'    => [
                                'frequency'             => 1,
                                'frequency_type'        => 'months',
                                'transaction_amount'    => MERCADOPAGO_SUBSCRIPTION_COST,
                                'currency_id'           => 'ARS',
                                'repetitions'           => 12,
                                'free_trial'            => [
                                    'frequency'      => 1,
                                    'frequency_type' => 'months'
                                ]
                            ]
                        ]),
                        CURLOPT_RETURNTRANSFER => true
                    ]);

                    $response = curl_exec($request);

                    $result   = json_decode($response, true);

                    reply(
                        $result,
                        $result['status'] == 'active' ? OK : ERROR
                    );
                } else {
                    reply([ 'allowance' => $allowance ], NOT_ALLOWED);
                }

                break;
            default:
                reply(null, WHAT_THE_FUCK);
        }
    }
}

?>