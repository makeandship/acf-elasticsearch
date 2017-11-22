<?php

namespace makeandship\elasticsearch;

use \Elastica\Client;

class TypeFactory
{
    private static $clients;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    public function get_client($writable=false)
    {
        $writable_key = strval($writable);

        if (isset(self::$clients) && is_array(self::$clients) && array_key_exists($writable_key, self::$clients)) {
            return self::$clients[$writable_key];
        } else {
            $client_settings = $client_settings = Util::get_client_settings($this->settings);
            
            if ($writable) {
                $client_settings[Constants::SETTING_TIMEOUT] = $this->use_attribute_or_default(
                    $this->settings,
                    Constants::OPTION_WRITE_TIMEOUT,
                    Constants::DEFAULT_WRITE_TIMEOUT
                );
            } else {
                $client_settings[Constants::SETTING_TIMEOUT] = $this->use_attribute_or_default(
                    $this->settings,
                    Constants::OPTION_READ_TIMEOUT,
                    Constants::DEFAULT_READ_TIMEOUT
                );
            }

            $client = new Client($client_settings);

            if (!isset(self::$clients) || !is_array(self::$clients)) {
                self::$clients = array();
            }
            self::$clients[$writable_key] = $client;

            return self::$clients[$writable_key];
        }
    }

    private function use_attribute_or_default($settings, $name, $default)
    {
        $value = null;

        if (array_key_exists($name, $settings)) {
            $value = $this->settings[$name];
        } else {
            $value = $default;
        }

        return $value;
    }
    
    /**
     * Return a type to support an index or delete call
     */
    public function create($type_name, $writable=false)
    {
        $type = null;

        $client = $this->get_client($writable);
        
        if (isset($client) && array_key_exists(Constants::OPTION_PRIMARY_INDEX, $this->settings)) {
            $index_name = $this->settings[Constants::OPTION_PRIMARY_INDEX];
            $index = $client->getIndex($index_name);

            if (isset($index)) {
                $type = $index->getType($type_name);
            }
        }

        return $type;
    }
}
