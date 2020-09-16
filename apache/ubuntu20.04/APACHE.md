Apache mini guide - Ubuntu 20.04

- Edit 000-default.conf :
```
<Directory /var/www/html>
	AllowOverride all
</Directory>
```

`ln -s ../mods-available/rewrite.load rewrite.load`

refresh :
`sudo cp -R ~/git/mobilecms-api/src/api . && sudo chown -R www-data:www-data api && sudo rm -rf api/v2  && sudo mv api/v1 api/v2`