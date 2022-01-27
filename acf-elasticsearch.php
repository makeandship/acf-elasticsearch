<?php
/*
Plugin Name: ACF Elasticsearch
Plugin URI:  https://www/makeandship.com/blog/acf-elasticsearch
Description: Elasticsearch integration for ACF-based wordpress sites
Version:     7.4.15
Author:      Make and Ship Limited
Author URI:  https://www.makeandship.com/
License:     MIT
License URI: https://opensource.org/licenses/MIT
 */
defined('ABSPATH') or die('No direct access is permitted to acf-elasticsearch');

use makeandship\elasticsearch\AcfElasticsearchPlugin;

# load dependencies
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/acf-elasticsearch-autoloader.php';

# bootstrap
new AcfElasticsearchPlugin();
