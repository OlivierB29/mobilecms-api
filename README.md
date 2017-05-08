# mobilecms-api
This project is a REST API for reading and writing content with JSON files.
It is initially intended to manage a small sport organization : News, calendar events, public pages


- Hosted on a cheap server, with no database available (more info in FAQ)
- Authentication with JSON web tokens
- Password encryption
- All the data is public, by default. (except users)


## Runtime Requirements
- PHP 7

## Manual install
- copy src/api to web server to the web directory eg: www/adminapp/api
- copy sample tests-data/public to /var/www/html/adminapp/public
- copy sample tests-data/private to a non readable directory, such as /var/www/private
- If needed, edit additional configuration and directories : edit api/v1/conf.php

## Gulp install
When editing the API and live testing.

```bash
$ npm install
```
```bash
$ gulp #Print available gulp tasks
```

Configure local directories gulpfile.js
var serverDeployDir = '/var/www/html';
var privateDeployDir = '/var/www/private';

Copy code and sample data
```bash
$ gulp samplepublic sampleprivate deploy
```

## Build
- copy src/api to web server to the web directory eg: www/adminapp/api

## Running unit tests
Requirements : [phpunit](https://phpunit.de)  (6.1+)

```bash
$ npm test
```
OR
```bash
$ phpunit --configuration phpunit-utils.xml
$ phpunit --configuration phpunit-api.xml
```

## Running end-to-end tests
- deploy to a local server
- edit code
```bash
$ gulp  deploy # deploy code to /var/www/html
```
- use a tool like [HttpRequester](https://addons.mozilla.org/en-US/firefox/addon/httprequester)

### Authentication

- URL : http://localhost/adminapp/api/v1/auth.php
Content Type : application/x-www-form-urlencoded
Content : requestbody={ "user": "test@example.com", "password":"..."}

- POST

- Sample Response
```json
{"username":"test@example.com","email":"test@example.com","role":"guest","token":"..."}
```

- Copy paste the token value

### Get content
- URL : http://localhost/adminapp/api/v1/api.php?path=/api/v1/content/calendar
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
