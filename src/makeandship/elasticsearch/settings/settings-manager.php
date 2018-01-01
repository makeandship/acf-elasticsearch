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
            $this->settings[Constants::OPTION_PRIVATE_PRIMARY_INDEX] = $this->get_option(Constants::OPTION_PRIVATE_PRIMARY_INDEX);
            $this->settings[Constants::OPTION_PRIVATE_SECONDARY_INDEX] = $this->get_option(Constants::OPTION_PRIVATE_SECONDARY_INDEX);
            $this->settings[Constants::OPTION_READ_TIMEOUT] = $this->get_option(Constants::OPTION_READ_TIMEOUT);
            $this->settings[Constants::OPTION_WRITE_TIMEOUT] = $this->get_option(Constants::OPTION_WRITE_TIMEOUT);
            $this->settings[Constants::OPTION_INDEX_STATUS] = $this->get_option(Constants::OPTION_INDEX_STATUS);
            $this->settings[Constants::OPTION_USERNAME] = $this->get_option(Constants::OPTION_USERNAME);
            $this->settings[Constants::OPTION_PASSWORD] = $this->get_option(Constants::OPTION_PASSWORD);
            $this->settings[Constants::OPTION_POST_TYPES] = $this->get_option(Constants::OPTION_POST_TYPES);
            $this->settings[Constants::OPTION_CAPABILITY] = $this->get_option(Constants::OPTION_CAPABILITY);
            $this->settings[Constants::OPTION_SEARCH_FIELDS] = $this->get_option(Constants::OPTION_SEARCH_FIELDS);
            $this->settings[Constants::OPTION_WEIGHTINGS] = $this->get_option(Constants::OPTION_WEIGHTINGS);
            $this->settings[Constants::OPTION_FUZZINESS] = $this->get_option(Constants::OPTION_FUZZINESS);
            $this->settings[Constants::OPTION_SLUGS_TO_EXCLUDE] = $this->get_option(Constants::OPTION_SLUGS_TO_EXCLUDE);
            $this->settings[Constants::OPTION_EXCLUSION_FIELD] = $this->get_option(Constants::OPTION_EXCLUSION_FIELD);
            $this->settings[Constants::OPTION_IDS_TO_EXCLUDE] = $this->get_option(Constants::OPTION_IDS_TO_EXCLUDE);
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
                Constants::OPTION_PRIVATE_PRIMARY_INDEX,
                Constants::OPTION_PRIVATE_SECONDARY_INDEX,
                Constants::OPTION_READ_TIMEOUT,
                Constants::OPTION_WRITE_TIMEOUT,
                Constants::OPTION_INDEX_STATUS,
                Constants::OPTION_USERNAME,
                Constants::OPTION_PASSWORD,
                Constants::OPTION_POST_TYPES,
                Constants::OPTION_CAPABILITY,
                Constants::OPTION_SEARCH_FIELDS,
                Constants::OPTION_WEIGHTINGS,
                Constants::OPTION_FUZZINESS,
                Constants::OPTION_SLUGS_TO_EXCLUDE,
                Constants::OPTION_EXCLUSION_FIELD,
                Constants::OPTION_IDS_TO_EXCLUDE
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

    public function get_private_fields($type)
    {
        $private = array();
        $option_post_types = $this->get_option(Constants::OPTION_POST_TYPES);

        foreach ($option_post_types as $item) {
            if ($item['type'] == $type) {
                $private = $item['private'];
            }
        }

        return $private;
    }

    public function get_exclude_fields($type)
    {
        $exclude = array();
        $option_post_types = $this->get_option(Constants::OPTION_POST_TYPES);

        foreach ($option_post_types as $item) {
            if ($item['type'] == $type) {
                $exclude = $item['exclude'];
            }
        }

        return $exclude;
    }

    public function get_indexes()
    {
        $indexes = array();
        // public primary index
        $primary = $this->get(Constants::OPTION_PRIMARY_INDEX);
        if ($primary) {
            $indexes[] = array(
                'name' => $primary,
                'type' => 'primary',
                'public' => true
            );
        }

        // public secondary index
        $secondary = $this->get(Constants::OPTION_SECONDARY_INDEX);
        if ($secondary) {
            $indexes[] = array(
                'name' => $secondary,
                'type' => 'secondary',
                'public' => true
            );
        }

        // private primary index
        $private_primary = $this->get(Constants::OPTION_PRIVATE_PRIMARY_INDEX);
        if ($private_primary) {
            $indexes[] = array(
                'name' => $private_primary,
                'type' => 'primary',
                'public' => false
            );
        }

        // private secondary index
        $private_secondary = $this->get(Constants::OPTION_PRIVATE_SECONDARY_INDEX);
        if ($private_secondary) {
            $indexes[] = array(
                'name' => $private_secondary,
                'type' => 'secondary',
                'public' => false
            );
        }

        return $indexes;
    }

    public function get_post_types()
    {
        $post_types = $this->get(Constants::OPTION_POST_TYPES);

        $types = [];

        if (isset($post_types) && !empty($post_types)) {
            foreach ($post_types as $item) {
                $types[] = $item['type'];
            }
        }

        return $types;
    }
}
