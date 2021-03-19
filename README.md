# mobilecms-api

deprecated

New version [mobilecms-api-slim](https://github.com/OlivierB29/mobilecms-api-slim)


## REST API written in PHP, for managing content stored into JSON files.


It is initially intended to manage a sport organization, with such content : News, calendar events, public pages, documents, ...

- Hosted on a cheap server, with no database available (see explanation in FAQ)
- Authentication with JSON web tokens
- Password encryption
- All the data is public, by default. (except users)
- Create thumbnails for images and PDF files

## Internal database
[database.md](database.md)

## Directory structure
Assume `www/html/` is the web root context.

- `www/html/public` : public database
- `www/html/api` : PHP files for API
- `www/private` : private directory (users)


## Installation notes


### API
- Copy src/api to web server to the web directory eg: /var/www/html/api
- Copy src/.htaccess file to the web server root (or edit your own custom file)
- If needed, edit additional configuration and directories : api/v1/conf/conf.json

### Copy sample data
- Copy sample sample-database/public to /var/www/html/public
- Give access rights to the apache service `sudo chown -R www-data:www-data /var/www/html/public`
- Create a default admin user
- Create '/var/www/private'

## Development install

When editing the API and live testing to a local web server.
### Required : PHP 7.0+
Basically a hosted PHP should be OK.
- php-gd : image features
- php-mbstring php-xml : optional, for PHPUnit
- php-imagick : preview PDF feature

### Ubuntu 16.04 - 18.04 - 20.04
With a default Ubuntu, you may need : `sudo apt install php-xdebug libapache2-mod-php php-gd php-mbstring php-xml php-imagick`

### Dev dependencies (optional)
- [Composer](https://getcomposer.org/download/)
- [Gulp](https://gulpjs.com/)

### Dev server

Copy code and sample data to web directories '/var/www/html' and '/var/www/private'

## Build
- copy src/api to web server to the web directory eg: www/adminapp/api


## Notes and FAQ
### FAQ
- Why not using a true CMS on a web hosting package ?
Value for money. A true CMS embeds too much unwanted features, such as public comments. Calendar events are not shipped with a CMS, and require a plugin.

- And a hosted CMS ?
I prefer a domain name, instead of mysite.company.com

- Why JSON files over a SQL database ?
Some entry level offers don't have any database, shipped with 10MB of file storage, such as a domain name package.
In future plans, with the growing data, the database may become useful. For now, we have 10-20 news per year, and roughly the same for calendar events.

### Common issues
- Q: When running phpunit : `Class 'DOMDocument' not found`
- A: Install php-xml (https://stackoverflow.com/questions/14395239/class-domdocument-not-found#14395414)

- Q: Can't login and browser debugger prints 404
- A: Install [mod_rewrite](https://stackoverflow.com/questions/17745310/how-to-enable-mod-rewrite-in-lamp-on-ubuntu#17745379)
On Ubuntu 16.04, the file path is /etc/apache2/sites-available/000-default.conf

- Q: When running tests : Error: No code coverage driver is available
- A: Install php-xdebug

- Q : "@ error/constitute.c/ReadImage/412" or "Web : ImagickException: attempt to perform an operation not allowed by the security policy `PDF’" when running PHPUnit on Ubuntu 18.04/20.04
- A: [Imagick - ImagickException not authorized @ error/constitute.c/ReadImage/412 error](https://stackoverflow.com/questions/52817741/imagick-imagickexception-not-authorized-error-constitute-c-readimage-412-err)