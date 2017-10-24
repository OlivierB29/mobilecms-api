Back to main page [README.md](https://github.com/OlivierB29/mobilecms-api/tree/master/README.md)

## Api Documentation
Use a tool like swagger or stoplight

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
