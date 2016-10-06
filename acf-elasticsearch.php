<?php 
/*
Plugin Name: ACF Elasticsearch
Plugin URI:  https://www/makeandship.com/blog/acf-elasticsearch
Description: Elasticsearch integration for ACF-based wordpress sites
Version:     0.1
Author:      Make and Ship Limited
Author URI:  https://www.makeandship.com/
License:     MIT
License URI: https://opensource.org/licenses/MIT
*/
defined( 'ABSPATH' ) or die( 'No direct access is permitted to acf-elasticsearch' );

use makeandship\elasticsearch\AcfElasticsearchPlugin;

// autoloader that 
define('AUTOLOAD_ROOT', __DIR__ . DIRECTORY_SEPARATOR . 'src/');
spl_autoload_register(function($class_name) {
    $components = explode('\\', $class_name);

    $class_name = array_pop($components);
    $class_path = implode($components, '/');

    // add a space before each capital, trim the first space, switch spaces for hyphens, lower case
    $file_name = strtolower(str_replace(' ','-',ltrim(preg_replace('/[A-Z]/', ' $0', $class_name)))).'.php';

    $file_path = AUTOLOAD_ROOT . $class_path . DIRECTORY_SEPARATOR . $file_name;

    if (file_exists( $file_path )) {
        require_once( $file_path );
    }
});

# load dependencies
require_once __DIR__.'/vendor/autoload.php';

# bootstrap
new AcfElasticsearchPlugin();