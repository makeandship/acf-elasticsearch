<?php

namespace makeandship\elasticsearch\settings;

use makeandship\elasticsearch\Constants;

class SettingsHelper
{
    public static function get_post_type_checkbox_data()
    {
        // populate post types
        $types = self::types();
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
            
            if (isset($option_post_types) && !empty($option_post_types)) {
                foreach ($option_post_types as $item) {
                    if ($item['type'] == $post_type) {
                        $exclude = implode("\n", $item['exclude']);
                        $private = implode("\n", $item['private']);
                    }
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
        $value = null;
        
        // populate search fields
        $option_search_fields = SettingsManager::get_instance()->get(Constants::OPTION_SEARCH_FIELDS);
        if ($option_search_fields) {
            $value = implode("\n", $option_search_fields);
        }
        
        return $value;
    }

    public static function get_exclusion_slugs_data()
    {
        $value = null;
        
        // populate search fields
        $option_slugs_to_exclude = SettingsManager::get_instance()->get(Constants::OPTION_SLUGS_TO_EXCLUDE);
        if ($option_slugs_to_exclude) {
            $value = implode("\n", $option_slugs_to_exclude);
        }
        
        return $value;
    }

    public static function get_weightings_data()
    {
        // populate weightings
        $weightings = array();
        $option_weightings = SettingsManager::get_instance()->get(Constants::OPTION_WEIGHTINGS);
        if (isset($option_weightings) && !empty($option_weightings)) {
            foreach($option_weightings as $field => $weight) {
                if ($field) {
                    $weightings[] = $field.'^'.$weight;
                }
            }
        }
        $value = implode("\n", $weightings);
        
        return $value;
    }

    static function types()
	{
		$types = get_post_types();

		$available = array();

		foreach ($types as $type) {
			$tobject = get_post_type_object($type);

			if (!$tobject->exclude_from_search) {
				$available[] = $type;
			}
		}

		return $available;
	}
}
