<?php

namespace makeandship\elasticsearch;

class Util
{
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
}
