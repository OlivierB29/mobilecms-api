<?php

include_once 'src/api/v1/autoloader/Autoloader.php';

spl_autoload_register(['AutoLoader', 'loadClass']);

// Register the directory to your include files
AutoLoader::registerDirectory('src/api/v1/classes');
