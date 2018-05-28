# Configuration
## Main configuration

-  "publicdir": "/public"
Directory of public content. The real path will be something like /var/www/html/public

-  "privatedir": "/../private"
Directory of public content. The real path will be something like /var/www/private

-  "media": "media"
Directory of media files. The real path will be something like /var/www/html/media

- "mailsender": "sendmail@example.org"

## Security (be cautious)

-  "crossdomain": "true|false"
Enable or disable CORS.

-  "enablecleaninputs": "true"
Should stay to true, for safety. When enabled, sanitize inputs.

-  "https": "true|false"
Set if https is mandatory.

- "debugapiexceptions": "true|false"
If set to true, the API sends an exception message in HTTP response.
Otherwise, the API sends a generic response {"error":"internal error"}

-  "errorlog": "true"
Enable error log on backend

-  "enableheaders": "true|false"
Enable the PHP header() function. Disabled with PHPUnit.

-  "enablemail": "true|false"
Enable send mail for password reset

-  "debugnotifications": "true|false"
Enable notifications in HTTP response.
