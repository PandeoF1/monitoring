# [Monitoring](https://monitoring-demo.pandeo.fr)
> Small work during internship

## File :<br />
> - [config](https://github.com/PandeoF1/monitoring/tree/main/conf) -> Configuration file <br />
> - [css](https://github.com/PandeoF1/monitoring/tree/main/css) -> CSS file <br />
> - [js](https://github.com/PandeoF1/monitoring/tree/main/js) -> JS file (Jquery & canvajs) <br />
> - [sql](https://github.com/PandeoF1/monitoring/tree/main/sql) -> SQL file (Database template) <br />
> - [index.php](https://github.com/PandeoF1/monitoring/blob/main/index.php) -> The website<br />
> - [client](https://github.com/PandeoF1/monitoring/tree/main/client) -> C# Client<br />

## Requirements :
 > - apache2 <br />
 > - mysql/mariadb (10.x) <br />
 > - php (7.x) <br />
 

## Installation Guide (Website) :
```
 - (Debian/Ubuntu)

Execute the following command :

    apt install -y mariadb-server mariadb-client apache2 php php-mysqli php-xml
    cd /var/www/ && git clone https://github.com/PandeoF1/monitoring.git monitoring && cd monitoring
    rm -rf client

With phpmyadmin or with the cli of Mysql, inject the file "sql/template.sql"

 - (Windows)

Clone all file in your www folder and configure the config/mysql.conf.php file.
With phpmyadmin inject the file "sql/template.sql".

```

## Installation Guide (Client) :
```
 - (Debian/Ubuntu)

The program is made in C# with .NET Framework. If you want to use it with linux use mono (wine) or wait the c/cpp version ^^'.

 - (Windows)

Clone the project on your computer, configure at the top of the program :
	- IP/Domain of database
	- Username & Password (User of client need to be create with "%" and not with "localhost" in the database. In mysql config file. Setup "bind-adress" to 0.0.0.0.)
	- Database name

Compile the project and launch it in every computer.

```
## (Recommended) Apache Configuration File :
```` 
------------------ Without SSL ------------------

<VirtualHost *:80>
  ServerName <domain> #Optionnal
  DocumentRoot "/var/www/monitoring/"
  AllowEncodedSlashes On
  php_value upload_max_filesize 100M
  php_value post_max_size 100M
  <Directory "/var/www/monitoring/">
    AllowOverride all
  </Directory>
</VirtualHost>

------------------ With SSL ------------------
<VirtualHost *:80>
  ServerName <domain>
  RewriteEngine On
  RewriteCond %{HTTPS} !=on
  RewriteRule ^/?(.*) https://%{SERVER_NAME}/$1 [R,L] 
</VirtualHost>
<VirtualHost *:443>
  ServerName <domain>
  DocumentRoot "/var/www/monitoring/"
  AllowEncodedSlashes On
  php_value upload_max_filesize 100M
  php_value post_max_size 100M
  <Directory "/var/www/monitoring/">
    Require all granted
    AllowOverride all
  </Directory>
  SSLEngine on
  SSLCertificateFile /etc/letsencrypt/live/<domain>/fullchain.pem
  SSLCertificateKeyFile /etc/letsencrypt/live/<domain>/privkey.pem
</VirtualHost> 

````
### Contributor :
 > - [A.Rouleau - Mr_Tox](https://github.com/Mr-ToX) -> CSS & HTML.
 > - [T.Nard - Pandeo_F1](https://github.com/PandeoF1/) -> C#, PHP, SQL & JS.

### Compatibility :
 > - Default : Windows (C#) <br />
 > - Linux (Debian/Ubuntu) with mono (Maybe x) ) <br />

### Todo :
- [ ] Clean the code.
- [ ] Make a c/c++ client version for linux and windows
- [x] Installation guide.

### Other :

That's a small website to monitor your computer (Only Windows for the moment). I have made that during an internship (2021/xx/xx) at [CHIMB](http://www.chimb.fr/) (French Hospital Center)