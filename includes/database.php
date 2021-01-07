<?php

class Database extends PDO {
    public function __construct() {
        try {
            parent::__construct(
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
            } else {
                header('location: ' . SHARED_VALUES['SYSTEM_HOSTNAME'] . 'exceptions/maintenance');
            }

            error_log($exception->getMessage());

            exit();
        }
    }
}

?>