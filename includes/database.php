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
    header('location: ' . SHARED_VALUES['SYSTEM_HOSTNAME'] . 'exceptions/maintenance');
}

?>