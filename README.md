# acf-elasticsearch

Improve wordpress search performance/accuracy and enable secure search against ElasticSearch server with searchguard plugin.

## Installation

1. Upload plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Click the 'ElasticSearch' menu item and follow the instructions on each section to configure the plugin. (be sure to save on each section)
4. Select "Enable" on "Server Settings" when you are ready for it to go live.

## Tests
1. Install phpunit
```
wget https://phar.phpunit.de/phpunit-6.2.phar
chmod +x phpunit-6.2.phar
sudo mv phpunit-6.2.phar /usr/local/bin/phpunit
```

2. Create mysql test user
```
mysql -u root
CREATE USER 'es_plugin_test'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON *.* TO 'es_plugin_test'@'localhost';
```

3. Setup the test environment
```
bash bin/install-wp-tests.sh wordpress_test es_plugin_test 'password' 127.0.0.1 latest
```

4. Run the tests
```
phpunit
```
