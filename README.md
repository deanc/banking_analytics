Banking Analytics
====================

A web application (to be run locally) which will analyse your banking data.

Currently only supporting the following banks:

* Nordea (Finland)

Installation
-
Set up a virtual host on your favourite web server to point to the web/app.php file:

*Apache*

	<VirtualHost *:80>
		ServerName banking.analytics
		ServerAdmin webmaster@banking.analytics

		DocumentRoot "/var/www/banking_analytics/web"
		<Directory "/var/www/banking_analytics/web">
			Options Indexes FollowSymLinks MultiViews
			AllowOverride None
			Order allow,deny
			allow from all
			<IfModule mod_rewrite.c>
				RewriteEngine On
				RewriteCond %{REQUEST_FILENAME} !-f
				RewriteRule ^(.*)$ /app.php [QSA,L]
			</IfModule>
		</Directory>
	</VirtualHost>

Add the following to your HOSTS file (/etc/hosts or C:/Windows/system32/drivers/etc) and add the following line:

`127.0.0.1	test`

Navigate to setup.php using whatever vhost you set up. e.g.

`http://banking.analytics/setup.php

Follow the on screen instructions





