<?php

$title = 'Reportes';

require_once 'includes/main.php';

define('MIN_ACCESS_LEVEL', USER_LEVEL_OWNER);

require_once 'views/header.php';

$reports = getReportedMessages();

?>

<div class="container dark-5">
    <div class="section"></div>

    <p class="flow-text center"> Procesamiento de datos </p>

    <div class="divider"></div>

    <p class="lato thin center">
        Acá vas a poder administrar los reportes que realizan los usuarios. Si es necesario, eliminá el mensaje directamente desde la base de datos.
    </p>

    <table class="responsive-table">
        <thead>
            <th> ID </th>  <th> Contenido </th>  <th> Reportes </th>  <th> Razones </th>
        </thead>
        <tbody class="lato thin">
            <?php

            if (count($reports) > 0) {
                foreach ($reports as $report) {
                    print '
                    <tr>
                        <td>
                            <a href="view.php?message=' . $report['message'] . ($report['private'] ? '&private=true' : '') . '">' .
                                $report['message'] . '
                            </a>
                        </td>
                        <td> ' . $report['content'] . ' </td>
                        <td> ' . $report['reports'] . ' </td>
                        <td> ' . $report['reasons'] . ' </td>
                    </tr>';
                }
            } else {
                print '
                <tr>
                    <td colspan="5" class="center">
                        ¡Genial! Nada por acá.
                    </td>
                </tr>';
            }

            ?>
        </tbody>
    </table>
</div>

<?php require_once 'views/footer.php'; ?>