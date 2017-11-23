<?php

namespace makeandship\elasticsearch\settings;

use makeandship\elasticsearch\Constants;
use makeandship\elasticsearch\Defaults;

class SettingsHelper
{
    public static function get_post_type_checkbox_data()
    {
        // populate post types
        $types = Defaults::types();
        $option_types = SettingsManager::get_instance()->get(Constants::OPTION_POST_TYPES);

        $post_type_checkboxes = [];

        foreach ($types as $post_type) {
            // if no options have been selected, select them all, otherwise none
            $checked = (
                (!$option_types) ||
                ($option_types && in_array($post_type, $option_types))
            ) ? true : false;
            
            $post_type_checkboxes[] = array(
                'value' => $post_type,
                'name' => 'acf_elasticsearch_post_types[]',
                'class' => 'checkbox',
                'checked' => $checked,
                'id' => $post_type
            );
        }

        return $post_type_checkboxes;
    }
}
