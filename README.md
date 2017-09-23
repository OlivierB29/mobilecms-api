# mobilecms-api
### REST API written in PHP, for managing content stored into JSON files.

[![Build Status](https://travis-ci.org/OlivierB29/mobilecms-api.svg?branch=master)](https://travis-ci.org/OlivierB29/mobilecms-api)

It is initially intended to manage a sport organization, with such content : News, calendar events, public pages, documents, ...

- Hosted on a cheap server, with no database available (see explanation in FAQ)
- Authentication with JSON web tokens
- Password encryption
- All the data is public, by default. (except users)

## Internal database
Take a look : [sample-database](https://github.com/OlivierB29/mobilecms-api/tree/master/sample-database)

* public/calendar/record.json : a record of a calendar object

Special files in public/calendar/index
* metadata.json : properties of a record
* new.json : default values of a new record
* index.json : index list of records, and some fields.
* index_template.json : index properties

Media files
* media/calendar/id1/foobar.jpg

## Runtime Requirements
- PHP 7

## Dev dependencies (optional)
- [Composer](https://getcomposer.org/)
- [phpunit](https://phpunit.de)
- [Gulp](https://gulpjs.com/)

## Manual install
- copy src/api to web server to the web directory eg: /var/www/html/api
- copy src/.htaccess file to the web server root (or edit your own custom file)
- copy sample sample-database/public to /var/www/html/public
- give access rights to the apache service `sudo chown -R www-data:www-data /var/www/html/public`
- copy sample sample-database/private outsite the web server documents, such as /var/www/private
- if needed, edit additional configuration and directories : api/v1/conf.php (see config dir for examples)

## Gulp install
When editing the API and live testing to a local web server.

`npm install`
`gulp` Print available gulp tasks

Configure local directories gulpfile.js
var serverDeployDir = '/var/www/html';
var privateDeployDir = '/var/www/private';

Copy code and sample data
`gulp samplepublic sampleprivate deploy`


## Build
- copy src/api to web server to the web directory eg: www/adminapp/api

## Running unit tests
- [phpunit](https://phpunit.de) 6.1+

`phpunit --configuration phpunit-utils.xml`
`phpunit --configuration phpunit-api.xml`

## Running end-to-end tests
- deploy to a local server
- edit code
`gulp  deploy` or manually deploy code to /var/www/html

- use a tool like [HttpRequester](https://addons.mozilla.org/en-US/firefox/addon/httprequester)

### Authentication

- URL : http://localhost/authapi/v1 (see src/.htaccess for RESTful rewrite rules)
Content Type : application/x-www-form-urlencoded
Content : requestbody={ "user": "test@example.com", "password":"..."}

- POST

- Sample Response
```json
{"username":"test@example.com","email":"test@example.com","role":"guest","token":"..."}
```

- Copy paste the token value

### Get content
- URL : http://localhost/adminapp/restapi/v1/content/calendar
- Headers :
Add a Authorization header, with value : Bearer [token]

- GET

- Sample Response
```json
[{"filename":"5.json","id":"5"},{"filename":"1.json","id":"1"},{"filename":"4.json","id":"4"},{"filename":"2.json","id":"2"},{"filename":"3.json","id":"3"},{"filename":"6.json","id":"6"},{"filename":"10.json","id":"10"}]
```

## FAQ
- Why not using a true CMS on a web hosting package ?
Value for money.

- And a hosted CMS ?
I prefer a domain name, instead of mysite.company.com

- Why JSON files VS database ?
Some entry level offers don't have any database, and <10MB of file storage, such as a domain name package.
In future plans, with the growing data, the database may become useful.
For now, we have 10-20 news per year, and roughly the same for calendar events.
