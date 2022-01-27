<?php
define('AUTOLOAD_ROOT', __DIR__ . DIRECTORY_SEPARATOR . 'src/');
spl_autoload_register(function($class_name) {
    $components = explode('\\', $class_name);

    $class_name = array_pop($components);
    $class_path = implode('/', $components);

    // add a space before each capital, trim the first space, switch spaces for hyphens, lower case
    $file_name = strtolower(str_replace(' ','-',ltrim(preg_replace('/[A-Z]/', ' $0', $class_name)))).'.php';

    $file_path = AUTOLOAD_ROOT . $class_path . DIRECTORY_SEPARATOR . $file_name;

    if (file_exists( $file_path )) {
        require_once( $file_path );
    }
});