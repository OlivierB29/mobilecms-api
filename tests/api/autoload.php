<?php

include_once 'Autoloader.php';

spl_autoload_register(array('AutoLoader', 'loadClass'));


// Register the directory to your include files
AutoLoader::registerDirectory('src/api/v1');

?>
