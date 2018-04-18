# mobilecms-api
## REST API written in PHP, for managing content stored into JSON files.

[![Build Status](https://travis-ci.org/OlivierB29/mobilecms-api.svg?branch=master)](https://travis-ci.org/OlivierB29/mobilecms-api)
![compatible](https://img.shields.io/badge/PHP%207-Compatible-brightgreen.svg)
[![StyleCI](https://styleci.io/repos/86973415/shield?style=flat)](https://styleci.io/repos/86973415)

It is initially intended to manage a sport organization, with such content : News, calendar events, public pages, documents, ...

- Hosted on a cheap server, with no database available (see explanation in FAQ)
- Authentication with JSON web tokens
- Password encryption
- All the data is public, by default. (except users)

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
### Required : PHP 7.0 / 7.1
Basically a hosted PHP should be OK. With a default Ubuntu 16.04, you may need : `sudo apt install libapache2-mod-php php-mcrypt php-gd php-mbstring php-xml`
- php-gd : image features
- php-mbstring php-xml : optional, for PHPUnit

### Dev dependencies (optional)
- [Composer](https://getcomposer.org/download/)
- [Gulp](https://gulpjs.com/)

### Dev tasks
`npm install`
`gulp` Print available gulp tasks

Configure local directories gulpfile.js
var serverDeployDir = '/var/www/html';
var privateDeployDir = '/var/www/private';

Copy code and sample data
`gulp samplepublic sampleprivate deploy`


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
On Ubuntu 16.04, the file is /etc/apache2/sites-available/000-default.conf
