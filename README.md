Decilo
=====

Este es el código fuente de **Decilo**, sentíte libre de analizarlo, tomar ideas y proponer mejoras.

## Dependencias
 - Apache 2.4.41 o superior
 - PHP 7.4.3 o superior (podría funcionar con versiones más antiguas, requiere de las extensiones **gd**, **curl**, **mysql**, **pdo**, **xml**, **zip**, **mbstring**, **bcmath** e **intl**)
 - MariaDB 15.1 o superior (podría funcionar con versiones más antiguas)
 - Existen otras dependencias internas con diversas licencias compatibles con el uso comercial y privado. Para más información, visitá nuestro [Acerca de](https://decilo.ar/about.php).

## Instalación
La configuración del servidor presenta diferentes complicaciones y obstáculos, principalmente si el concepto de [Variables de entorno](https://httpd.apache.org/docs/current/env.html), la utilización del sistema de dependencias de [Composer](https://getcomposer.org/doc/00-intro.md) y el [concepto de "bundling"](https://medium.com/madhash/understanding-the-concept-of-bundling-for-beginners-f2db1adad724) no te resultan familiares. Si es así, podés referirte a los enlaces adjuntos a cada uno de los temas anteriores.

De momento, la versión de producción se ejecuta bajo **Ubuntu 20.04.2 LTS** y, si bien otras distribuciones pueden ser completamente utilizables, lo cierto es que fue probado durante meses en la misma y es el caso de uso sugerido.

Por último, para realizar la instalación, es recomendable proceder de la siguiente manera:

 - Crea una base de datos llamada `decilo` (si querés usar otro nombre, recordá que vas a tener que configurar la **variable de entorno**, específicamente `DATABASE_NAME`), después, ejecutá el script SQL provisto (podés encontrarlo en `/resources`).
 - Instalá las dependencias base: `sudo apt install php-gd php-curl php-mysql php-pdo php-xml php-zip php-mbstring php-bcmath php-intl`.
 - Instalá las dependencias del backend (movéte a la raíz del proyecto y ejecutá el siguiente comando): `composer install`.
 - Cuando termines, reniciá el servidor usando uno de los siguientes comandos: `sudo service apache2 restart` o `sudo systemctl restart apache2`.
 - Por último, la primera vez que intentes acceder, se te va a informa de qué variables de entorno necesitás. Las podés configurar colocando cada una de ellas con su valor correspondiente por medio de la instrucción `SetEnv` en tu **archivo de host virtual**. Recordá que, al realizar cambios en este archivo, es importante que reinicies el servidor para aplicarlos.

## Recursos
¡Probálo! Importá la base de datos desde la carpeta **resources** y empezá a usarlo.

## Licencia
El código fuente de este proyecto es propiedad de [@FacuM](https://github.com/FacuM), excluyendo el código fuente del software obtenido durante la instalación de dependencias, el cual puede contener diferentes tipos de licencia compatibles con el uso comercial y privado.

Si querés usar un fragmento de código tal y como está presente en este repositorio y no estás seguro de si está cubierto o no por esta licencia, no dudes en enviar un correo electrónico a [support@decilo.ar](mailto:support@decilo.ar) y a [facumo.fm@gmail.com](mailto:facumo.fm@gmail.com).