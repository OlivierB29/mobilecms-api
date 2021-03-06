Back to main page [README.md](https://github.com/OlivierB29/mobilecms-api/tree/master/README.md)

## Code style
vendor/bin/phpcs --standard=CakePHP src/

# Unit tests
- Requirements : Composer, Xdebug
- `composer install`
- `vendor/bin/phpunit --configuration phpunit.xml`
- `vendor/bin/phpunit --configuration phpunit.xml --filter testPostBBCode`

# Debug unit tests
## Xdebug on Ubuntu 16.04 
- [Xdebug on Ubuntu 16.04](http://www.dieuwe.com/blog/xdebug-ubuntu-1604-php7)

## Xdebug on Ubuntu 20.04 
- `sudo apt-get install php-xdebug`
- edit /etc/php/7.4/cli/php.ini
```
[XDebug]
xdebug.remote_enable = 1
xdebug.remote_autostart = 1
```
## VS Code debugging
[felixfbecker.php-debug](https://marketplace.visualstudio.com/items?itemName=felixfbecker.php-debug)

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


## Known issues
ImagickException: not authorized  @ error/constitute.c/ReadImage/412

[https://stackoverflow.com/questions/37599727/php-imagickexception-not-authorized]