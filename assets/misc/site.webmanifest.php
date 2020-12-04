<?php
    chdir('../../');

    header('Content-Type: application/manifest+json');

    require_once 'includes/settings.php';
    
    print json_encode(WEBSITE_MANIFEST, JSON_NUMERIC_CHECK);
?>