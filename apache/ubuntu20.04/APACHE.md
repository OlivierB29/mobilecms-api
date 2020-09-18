Apache mini guide - Ubuntu 20.04

- Edit 000-default.conf :
```
<Directory /var/www/html>
	AllowOverride all
</Directory>
```

`ln -s ../mods-available/rewrite.load rewrite.load`

refresh :
`sudo rm -rf /var/www/html/api && sudo cp -R ~/git/mobilecms-api/src/api /var/www/html/ && sudo cp -R /var/www/html/api/v1 /var/www/html/api/v2  && sudo chown -R www-data:www-data /var/www/html/api/`