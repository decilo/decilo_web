<?php

try {
    $GLOBALS['database'] = new PDO(
        'mysql:' . 
            'host='     . DATABASE['hostname'] . ';' . 
            'dbname='   . DATABASE['name']     . ';' . 
            'charset='  . DATABASE['encoding'], 
        DATABASE['username'], 
        DATABASE['password'],
        [ PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC ]
    );
} catch (PDOException $exception) {
    if (isset($uptimeTester)) {
        http_response_code(503);

        exit();
    } else {
        header('location: ' . SHARED_VALUES['SYSTEM_HOSTNAME'] . 'exceptions/maintenance');
    }
}

?>