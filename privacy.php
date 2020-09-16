<?php

$title = 'Privacidad';

require_once 'includes/main.php';
require_once 'views/header.php';

?>

<div class="container dark-5">
    <p class="flow-text center"> Procesamiento de datos </p>

    <div class="divider"></div>

    <p class="lato thin">
        Los datos que ingresás tanto en el portal principal como en el área de mensajes privados se almacenan en texto plano.<br>
        <br>
        Por otro lado, la información sensible que nos das, como tu contraseña, aspectos de tu vida identificables que almacenás en tu perfil como la información sobre tu aspecto físico, tus intereses y tu actividad en el sitio que compartís con nosotros, se guardan con la última versión del mecanismo de encriptación más fuerte soportado por el sistema. <br>
        <br>
        Parte de esta información, al mismo tiempo, es procesada por sistemas externos como Google reCaptcha v3, Google Analytics y otros proveedores externos transparentes.
    </p>

    <p class="flow-text center"> Aspectos técnicos </p>

    <div class="divider"></div>

    <p class="lato thin">
        Parte de la información de la que hablamos anteriormente la usamos para poder completar varias tareas técnicas requeridas por motivos de seguridad y estabilidad del sistema. <br>
        <br>
        Por ejemplo, usamos la tecnología de Google reCaptcha v3 para proteger el sitio: tu IP y un identificador único temporal son emparejados entre tu dispositivo y el servidor para tratar de identificarte como un usuario legítimo. Si no lo logramos, es posible que un desafío rápido aparezca. <br>
        <br>
        Además, aprovechamos este paso intermediario (la validación entre tu dispositivo y el servidor) para almacenar datos como tu IP, el identificador único provisto por reCaptcha, la fecha y hora interna del servidor local, la fecha y hora reportada por el servidor remoto (el de reCaptcha) y si lograste o no resolver el desafío. <br>
        <br>
        Es importante destacar que tu IP suele cambiar frecuentemente y, además, el identificador único que te asignó el sistema de validación se vence en dos minutos, por lo que esta información se guarda por motivos históricos y legales, de modo que normalmente no representa una relación identificable con tu actividad. <br>
        <br>
        Finalmente, es posible que utilicemos Google Analytics para obtener, almacenar y analizar información anónima sobre tu actividad en el sitio como tu manera de navegar, tus intereses, qué es lo que más te gusta ver y hacer, entre otros. Esta información se divide en países o "regiones de tráfico" y no posse datos específicos de tu actividad identificable como un usuario único.
    </p>

    <p class="flow-text center"> Sobre vos </p>

    <div class="divider"></div>

    <p class="lato thin">
        Nos comprometemos a ser transparentes y manipular tus datos de manera segura. Vas a poder descargar una versión en tiempo real de lo que sabemos internamente sobre vos cuando quieras y todas las veces que quieras. <br>
        <br>
        Tené en cuenta que el volúmen de esta información puede fluctuar entre descarga y descarga, puesto que tu IP podría cambiar y dejar de estar vinculada a un conjunto de datos específico. <br>
        <br>
        Para descargar tus datos ahora, seleccioná "descargar una copia de mis datos" en "Mi cuenta".
    </p>

    <p class="flow-text center"> Tus opciones </p>

    <div class="divider"></div>

    <p class="lato thin">
        Si querés, podés eliminar tu cuenta y todos los datos relacionados de manera física y permanente con ella. <br>
        <br>
        Para hacerlo, tocá "Eliminar cuenta" en "Mi cuenta". Te vamos a pedir que confirmes la operación a través de un correo electrónico. <br>
        <br>
        Tené en cuenta que pueden existir copias de seguridad físicas, ajenas al servidor principal que pueden contener tus datos. Sin embargo, estas se reciclan usualmente cada un mes.
    </p>
</div>

<?php require_once 'views/footer.php'; ?>