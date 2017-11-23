<?php

namespace makeandship\elasticsearch\settings;

use makeandship\elasticsearch\Constants;
use makeandship\elasticsearch\Util;

class SettingsManager
{
    protected static $instance = null;

    // can't be instantiated externally
    protected function __construct()
    {
    }
    protected function __clone()
    {
    } // no clone

    public static function get_instance()
    {
        if (SettingsManager::$instance === null) {
            SettingsManager::$instance = new SettingsManager();
            SettingsManager::$instance->initialize();
        }
        return SettingsManager::$instance;
    }

    protected function initialize()
    {
        $this->get_settings(true);
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

            $this->settings[Constants::OPTION_SERVER] = $this->get_option(Constants::OPTION_SERVER);
            $this->settings[Constants::OPTION_PRIMARY_INDEX] = $this->get_option(Constants::OPTION_PRIMARY_INDEX);
            $this->settings[Constants::OPTION_SECONDARY_INDEX] = $this->get_option(Constants::OPTION_SECONDARY_INDEX);
            $this->settings[Constants::OPTION_READ_TIMEOUT] = $this->get_option(Constants::OPTION_READ_TIMEOUT);
            $this->settings[Constants::OPTION_WRITE_TIMEOUT] = $this->get_option(Constants::OPTION_WRITE_TIMEOUT);
            $this->settings[Constants::OPTION_INDEX_STATUS] = $this->get_option(Constants::OPTION_INDEX_STATUS);
            $this->settings[Constants::OPTION_USERNAME] = $this->get_option(Constants::OPTION_USERNAME);
            $this->settings[Constants::OPTION_PASSWORD] = $this->get_option(Constants::OPTION_PASSWORD);
            $this->settings[Constants::OPTION_POST_TYPES] = $this->get_option(Constants::OPTION_POST_TYPES);
        }
        
        return $this->settings;
    }

    public function get($name)
    {
        $settings = $this->get_settings();

        return Util::safely_get_attribute($settings, $name);
    }

    public function set($name, $value)
    {
        if ($this->valid_setting($name)) {
            $this->set_option($name, $value);

            if ($this->settings) {
                $this->settings[$name] = $value;
            }
        }
    }

    private function valid_setting($name)
    {
        if ($name) {
            if (in_array($name, [
                Constants::OPTION_SERVER,
                Constants::OPTION_PRIMARY_INDEX,
                Constants::OPTION_SECONDARY_INDEX,
                Constants::OPTION_READ_TIMEOUT,
                Constants::OPTION_WRITE_TIMEOUT,
                Constants::OPTION_INDEX_STATUS,
                Constants::OPTION_USERNAME,
                Constants::OPTION_PASSWORD,
                Constants::OPTION_POST_TYPES
            ])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return settings to connect to the configured elasticsearch instance
     *
     * @return array with options set
     */
    public function get_client_settings()
    {
        $settings = array();

        $settings[Constants::SETTING_URL] = $this->get(Constants::OPTION_SERVER);

        $username = $this->get(Constants::OPTION_USERNAME);
        if ($username) {
            $settings[Constants::SETTING_USERNAME] = $username;
        }

        $password = $this->get(Constants::OPTION_PASSWORD);
        if ($password) {
            $settings[Constants::SETTING_PASSWORD] = $password;
        }

        return $settings;
    }

    /**
     * Retrieve an option for a given key.  If this is a
     * network installation, finds a network site option otherwise
     * finds a local site option
     *
     * @param $key the name of the option
     *
     * @return the option value
     */
    public function get_option($key)
    {
        if (is_multisite()) {
            return get_site_option($key);
        } else {
            return get_option($key);
        }
    }

    /**
     * Set an option for a given key.  If this is a
     * network installation, sets a network site option otherwise
     * sets a local site option
     *
     * @param $key the name of the option
     * @param $value to store
     */
    public function set_option($key, $value)
    {
        if (is_multisite()) {
            return update_site_option($key, $value);
        } else {
            return update_option($key, $value);
        }
    }
}
