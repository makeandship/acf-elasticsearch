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
        $option_post_types = SettingsManager::get_instance()->get(Constants::OPTION_POST_TYPES);

        $option_types = [];

        foreach ($option_post_types as $item) {
            $option_types[] = $item['type'];
        }

        $post_type_checkboxes = [];

        foreach ($types as $post_type) {
            // if no options have been selected, select them all, otherwise none
            $checked = (
                (!$option_types) ||
                ($option_types && in_array($post_type, $option_types))
            ) ? true : false;

            $exclude = "";
            $private = "";

            foreach ($option_post_types as $item) {
                if ($item['type'] == $post_type) {
                    $exclude = implode("\n", $item['exclude']);
                    $private = implode("\n", $item['private']);
                }
            }
            
            $post_type_checkboxes[] = array(
                'value' => $post_type,
                'name' => 'acf_elasticsearch_post_types[]',
                'class' => 'checkbox',
                'checked' => $checked,
                'id' => $post_type,
                'exclude' => $exclude,
                'private' => $private
            );
        }

        return $post_type_checkboxes;
    }
}
