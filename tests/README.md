Back to main page [README.md](https://github.com/OlivierB29/mobilecms-api/tree/master/README.md)



## Unit tests
Requirements : Composer, Xdebug
[Xdebug on Ubuntu 16.04](http://www.dieuwe.com/blog/xdebug-ubuntu-1604-php7)

`composer install`
`vendor/bin/phpunit --configuration phpunit.xml`

Windows variant : `vendor\bin\phpunit.bat --configuration ` ...

## Code style
vendor/bin/phpcs --standard=CakePHP src/
