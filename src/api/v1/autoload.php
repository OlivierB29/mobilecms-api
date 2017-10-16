<?php

include_once 'autoloader/Autoloader.php';

spl_autoload_register(['AutoLoader', 'loadClass']);

// Register the directory to your include files
AutoLoader::registerDirectory('classes');
