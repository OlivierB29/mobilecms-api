Back to main page [README.md](https://github.com/OlivierB29/mobilecms-api/tree/master/README.md)




## Unit tests
- Requirements : Composer, Xdebug
- [Xdebug on Ubuntu 16.04](http://www.dieuwe.com/blog/xdebug-ubuntu-1604-php7)
- `composer install`
- `vendor/bin/phpunit --configuration phpunit.xml`
- `vendor/bin/phpunit --filter testPostBBCode tests/api/CmsApiTest.php`

## Windows
- Install [PHP](https://www.php.net/downloads.php)
- Install [XDEBUG](https://xdebug.org/wizard.php) 
- Configure php.ini as described by the xdebug wizard, but check the execution 
Sample error :
PHP Warning:  Failed loading Zend extension 'ext\php_xdebug-2.7.2-7.3-vc15-nts-x86_64.dll' (tried: ext\ext\php_xdebug-2.7.2-7.3-vc15-nts-x86_64.dll

=> Configure php.ini with zend_extension = php_xdebug-xxxxx.dll
instead of zend_extension = ext/php_xdebug-xxxxx.dll 

- Install [powershell-phpmanager](https://github.com/mlocati/powershell-phpmanager)
- php.ini
extension=php_fileinfo.dll
extension=gd2
extension=mbstring
extension=exif

- `Install-PhpExtension imagick`
- `vendor\bin\phpunit.bat --configuration phpunit.xml`

## Code style
vendor/bin/phpcs --standard=CakePHP src/

## Known issues
ImagickException: not authorized  @ error/constitute.c/ReadImage/412

[https://stackoverflow.com/questions/37599727/php-imagickexception-not-authorized]