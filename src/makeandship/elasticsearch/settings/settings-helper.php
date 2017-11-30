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
        $option_types = SettingsManager::get_instance()->get_post_types();

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

    public static function get_search_fields_data()
    {
        // populate search fields
        $option_search_fields = SettingsManager::get_instance()->get(Constants::OPTION_SEARCH_FIELDS);
        $value = implode("\n", $option_search_fields);
        if (!$value) {
            $value = "post_title_suggest\nname_suggest";
        }
        return $value;
    }

    public static function get_weightings_data()
    {
        // populate weightings
        $weightings = array();
        $option_weightings = SettingsManager::get_instance()->get(Constants::OPTION_WEIGHTINGS);
        foreach($option_weightings as $field => $weight) {
            if ($field) {
                $weightings[] = $field.'^'.$weight;
            }
        }
        $value = implode("\n", $weightings);
        if (!$value) {
            $value = "post_title^3\npost_content^3";
        }
        return $value;
    }
}
