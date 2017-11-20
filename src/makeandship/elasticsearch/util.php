<?php

namespace makeandship\elasticsearch;

class Util
{
    /**
     * Return a settings array based on stored options for use
     * with elastica searches, indexing and mapping
     *
     * @param options stored options for the plugin
     * @return settings array containing url, username and password
     */
    public static function get_client_settings($options)
    {
        $settings = array(
            Constants::SETTING_URL => $options[Constants::OPTION_SERVER]
        );
        if (array_key_exists(Constants::OPTION_USERNAME, $options)) {
            $settings[Constants::SETTING_USERNAME] = $options[Constants::OPTION_USERNAME];
        }
        if (array_key_exists(Constants::OPTION_PASSWORD, $options)) {
            $settings[Constants::SETTING_PASSWORD] = $options[Constants::OPTION_PASSWORD];
        }

        return $settings;
    }

    /**
     * Retrieve the value from an array item, or object attribute
     * returning null if the attribute is missing or the value is null
     *
     * @param array the array or object
     * @param attribute the name of the attribute to extract
     * @return the value or the attribute or null if missing
     */
    public static function safely_get_attribute($array, $attribute)
    {
        if (is_array($array)) {
            if (isset($array) && isset($attribute) && $array && $attribute) {
                if (array_key_exists($attribute, $array)) {
                    return $array[$attribute];
                }
            }
        } elseif (is_object($array)) {
            if (isset($array) && isset($attribute) && $array && $attribute) {
                if (property_exists($array, $attribute)) {
                    return $array->{$attribute};
                }
            }
        }
        return null;
    }

    public static function get_facet_size() 
    {
        return Config::apply_filters('searcher_query_facet_size', 100);
    }
}
