<?xml version="1.0" encoding="UTF-8"?>
<urlset
      xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

<?php

header('Content-Type: application/xml');

require_once 'includes/settings.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

function escapeXML($string) {
    return str_replace('&', '&#38;', $string);
}

function getXML($loc, $priority) {
    return 
    '<url>
        <loc>'      . SYSTEM_HOSTNAME . '/' . escapeXML($loc)  . '</loc>
        <lastmod>'  . strftime('%Y-%m-%d')          . '</lastmod>
        <priority>' . $priority                     . '</priority>
     </url>';
}

$defaults = [ 'index.php', 'privacy.php' ];

foreach ($defaults as $default) {
    print getXML($default, '1.0');
}

$messages =
    $GLOBALS['database']->prepare(
        'SELECT
            CONCAT(\'view.php?message=\', `d_messages_private`.`id`, \'&private=true\') AS url
         FROM `d_messages_private`

         UNION

         SELECT
            CONCAT(\'view.php?message=\', `d_messages_public`.`id`) AS url
         FROM `d_messages_public`'
    );

$messages->execute();

foreach ($messages->fetchAll() as $message) {
    print getXML($message['url'], '0.9');
}

?>

</urlset>