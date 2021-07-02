<?php

namespace makeandship\elasticsearch\settings;

use makeandship\elasticsearch\Constants;
use makeandship\elasticsearch\Util;
use \Elastica\Client;

use makeandship\logging\Log;

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
    public function get_settings($fresh = false)
    {
        if (!isset($this->settings) || $fresh) {
            $this->settings = array();

            $this->settings[Constants::OPTION_SERVER] = $this->get_option_from_config(array(
                'const' => array(
                    'ES_URL',
                ),
                'env'   => array(
                    'ES_URL',
                ),
            ));
            $this->settings[Constants::OPTION_INDEX] = $this->get_option_from_config(array(
                'const' => array(
                    'ES_INDEX',
                ),
                'env'   => array(
                    'ES_INDEX',
                ),
            ));
            $this->settings[Constants::OPTION_PRIVATE_INDEX] = $this->get_option_from_config(array(
                'const' => array(
                    'ES_PRIVATE_INDEX',
                ),
                'env'   => array(
                    'ES_PRIVATE_INDEX',
                ),
            ));
            $this->settings[Constants::OPTION_READ_TIMEOUT]  = $this->get_option(Constants::OPTION_READ_TIMEOUT);
            $this->settings[Constants::OPTION_WRITE_TIMEOUT] = $this->get_option(Constants::OPTION_WRITE_TIMEOUT);
            $this->settings[Constants::OPTION_INDEX_STATUS]  = $this->get_option(Constants::OPTION_INDEX_STATUS);
            $this->settings[Constants::OPTION_USERNAME]      = $this->get_option_from_config(array(
                'const' => array(
                    'ES_USERNAME',
                ),
                'env'   => array(
                    'ES_USERNAME',
                ),
            ));
            $this->settings[Constants::OPTION_PASSWORD] = $this->get_option_from_config(array(
                'const' => array(
                    'ES_PASSWORD',
                ),
                'env'   => array(
                    'ES_PASSWORD',
                ),
            ));
            $this->settings[Constants::OPTION_POST_TYPES]            = $this->get_option(Constants::OPTION_POST_TYPES);
            $this->settings[Constants::OPTION_CAPABILITY]            = $this->get_option(Constants::OPTION_CAPABILITY);
            $this->settings[Constants::OPTION_SEARCH_FIELDS]         = $this->get_option(Constants::OPTION_SEARCH_FIELDS);
            $this->settings[Constants::OPTION_WEIGHTINGS]            = $this->get_option(Constants::OPTION_WEIGHTINGS);
            $this->settings[Constants::OPTION_FUZZINESS]             = $this->get_option(Constants::OPTION_FUZZINESS);
            $this->settings[Constants::OPTION_SLUGS_TO_EXCLUDE]      = $this->get_option(Constants::OPTION_SLUGS_TO_EXCLUDE);
            $this->settings[Constants::OPTION_EXCLUSION_FIELD]       = $this->get_option(Constants::OPTION_EXCLUSION_FIELD);
            $this->settings[Constants::OPTION_IDS_TO_EXCLUDE]        = $this->get_option(Constants::OPTION_IDS_TO_EXCLUDE);
            $this->settings[Constants::OPTION_MAPPING_TIMEOUT]       = $this->get_option(Constants::OPTION_MAPPING_TIMEOUT);
            $this->settings[Constants::OPTION_ELASTICSEARCH_VERSION] = $this->get_elasticseach_version();
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
                Constants::OPTION_INDEX,
                Constants::OPTION_PRIVATE_INDEX,
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
                Constants::OPTION_IDS_TO_EXCLUDE,
                Constants::OPTION_ELASTICSEARCH_VERSION,
                Constants::OPTION_MAPPING_TIMEOUT,
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

    /**
     * Get a value from a config where that config contains optional
     * constants, environment variables (or environment variable files)
     */
    public function get_option_from_config($config)
    {
        if ($config) {
            $const_settings = Util::safely_get_attribute($config, 'const');
            if ($const_settings && is_array($const_settings) && count($const_settings) > 0) {
                foreach ($const_settings as $const_setting) {
                    if (defined($const_setting)) {
                        $value = constant($const_setting);
                        if ($value) {
                            return $value;
                        }
                    }
                }
            }

            $env_settings = Util::safely_get_attribute($config, 'env');
            if ($env_settings && is_array($env_settings) && count($env_settings) > 0) {
                foreach ($env_settings as $env_setting) {
                    // get the value
                    $value = getenv($env_setting);
                    if ($value) {
                        return $value;
                    }

                    // get the value from a file in the value
                    $filename_env = $env_setting . '_FILE';
                    $filename     = getenv($filename_env);
                    if (file_exists($filename)) {
                        $value = file_get_contents($filename);
                        if ($value) {
                            return $value;
                        }
                    }
                }
            }
        }

        return null;
    }

    public function get_private_fields($type)
    {
        $private           = array();
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
        $exclude           = array();
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
        // public index
        $public_index = $this->get(Constants::OPTION_INDEX);
        if ($public_index) {
            $indexes[] = array(
                'name'   => $public_index,
                'public' => true,
            );
        }

        // private index
        $private_index = $this->get(Constants::OPTION_PRIVATE_INDEX);
        if ($private_index) {
            $indexes[] = array(
                'name'   => $private_index,
                'public' => false,
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

    private function get_elasticseach_version()
    {
        $client_settings = $this->get_client_settings();
        $client          = new Client($client_settings);
        try {
            $version = $client->getVersion();
            return $version;
        } catch (\Elastica\Exception\Connection\HttpException $e) {
            Log::debug("SettingsManager#get_elasticsearch_version: " . $e);
            return null;
        }
    }

    public function is_valid_post_type($post_type)
    {
        if (in_array($post_type, Constants::EXCLUDE_POST_TYPES)) {
            return false;
        }
        return true;
    }

    public function get_valid_post_types()
    {
        $post_types = get_post_types(array(
            'public' => true,
        ));

        $valid_post_types = array();

        foreach ($post_types as $post_type) {
            if ($this->is_valid_post_type($post_type)) {
                $valid_post_types[] = $post_type;
            }
        }

        return $valid_post_types;
    }
}
