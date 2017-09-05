<?php

define('ERROR_LOG', 'true');

// enable CORS
define('ALLOW_CROSS_DOMAIN', 'true');

// mail password reset
define('ENABLE_MAIL', 'false');
define('MAIL_FROM', 'sendmail@example.org');
define('DEBUG_RESETPASSWORD', 'true');


// block HTTP if activated
define('ACTIVATE_HTTPS', 'false');

//eg : /var/www/html
define('HOME', $_SERVER['DOCUMENT_ROOT']);

//If possible, use a directory only accessible with filesystem queries.
//Unless, use a .htaccess file
//eg : /var/www/private
define('PRIVATEDIR', realpath($_SERVER['DOCUMENT_ROOT'].'/../private'));
