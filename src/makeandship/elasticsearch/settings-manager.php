<?php

namespace makeandship\elasticsearch;

class SettingsManager
{
    public function __construct()
    {
        $this->settings = null;
    }
    /**
     * Get the current configuration.  Configuration values
     * are cached.  Use the $fresh parameter to get an updated
     * set
     *
     * @param $fresh - true to get updated values
     * @return array of settings
     */
    public function get_settings($fresh=false)
    {
        if (!isset($this->settings) || $fresh) {
            $this->settings = array();

            $this->get_option($this->settings, Constants::OPTION_SERVER);
            $this->get_option($this->settings, Constants::OPTION_PRIMARY_INDEX);
            $this->get_option($this->settings, Constants::OPTION_SECONDARY_INDEX);
            $this->get_option($this->settings, Constants::OPTION_READ_TIMEOUT);
            $this->get_option($this->settings, Constants::OPTION_WRITE_TIMEOUT);
            $this->get_option($this->settings, Constants::OPTION_INDEX_STATUS);
            $this->get_option($this->settings, Constants::OPTION_USERNAME);
            $this->get_option($this->settings, Constants::OPTION_PASSWORD);
        }
        
        return $this->settings;
    }

    /**
     * Add a single option to an settings array.  Detects multisite
     * and pulls from multisite settings when it is
     *
     * @param $settings array (passed by reference)
     * @param $name the option name
     */
    private function get_option(&$settings, $name)
    {
        if (!isset($settings)) {
            $settings = array();
        }

        if (is_multisite()) {
            $settings[$name] = get_site_option($name);
        } else {
            $settings[$name] = get_option($name);
        }
    }
}
