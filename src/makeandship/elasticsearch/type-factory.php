<?php

namespace makeandship\elasticsearch;

use makeandship\elasticsearch\settings\SettingsManager;

use \Elastica\Client;

class TypeFactory
{
    private static $clients;

    public function __construct()
    {
    }

    public function get_client($writable=false)
    {
        $writable_key = strval($writable);

        if (isset(self::$clients) && is_array(self::$clients) && array_key_exists($writable_key, self::$clients)) {
            return self::$clients[$writable_key];
        } else {
            $client_settings = SettingsManager::get_instance()->get_client_settings();
            
            if ($writable) {
                $client_settings[Constants::SETTING_TIMEOUT] =
                    SettingsManager::get_instance()->get(Constants::OPTION_WRITE_TIMEOUT) ?
                    SettingsManager::get_instance()->get(Constants::OPTION_WRITE_TIMEOUT) :
                    Constants::DEFAULT_WRITE_TIMEOUT;
            } else {
                $client_settings[Constants::SETTING_TIMEOUT] =
                    SettingsManager::get_instance()->get(Constants::OPTION_READ_TIMEOUT) ?
                    SettingsManager::get_instance()->get(Constants::OPTION_READ_TIMEOUT) :
                    Constants::DEFAULT_READ_TIMEOUT;
            }

            $client = new Client($client_settings);

            if (!isset(self::$clients) || !is_array(self::$clients)) {
                self::$clients = array();
            }
            self::$clients[$writable_key] = $client;

            return self::$clients[$writable_key];
        }
    }
    
    /**
     * Return a type to support an index or delete call
     */
    public function create($type_name, $writable=false)
    {
        $type = null;

        $client = $this->get_client($writable);
        
        $primary_index = SettingsManager::get_instance()->get(Constants::OPTION_PRIMARY_INDEX);

        if (isset($client) && $primary_index) {
            $index = $client->getIndex($primary_index);

            if (isset($index)) {
                $type = $index->getType($type_name);
            }
        }

        return $type;
    }
}
