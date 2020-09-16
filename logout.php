<?php

require_once 'includes/main.php';

session_destroy();

redirect('login.php');

?>