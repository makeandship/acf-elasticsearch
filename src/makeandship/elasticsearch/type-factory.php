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
    public function create($type_name, $writable=false, $private=false, $primary=true)
    {
        $type = null;

        $client = $this->get_client($writable);
        
        if (isset($client)) {
            if ($private) {
                $index = $primary ?
                    SettingsManager::get_instance()->get(Constants::OPTION_PRIVATE_PRIMARY_INDEX) :
                    SettingsManager::get_instance()->get(Constants::OPTION_PRIVATE_SECONDARY_INDEX);
            } else {
                $index = $primary ?
                    SettingsManager::get_instance()->get(Constants::OPTION_PRIMARY_INDEX) :
                    SettingsManager::get_instance()->get(Constants::OPTION_SECONDARY_INDEX);
            }
            
            if ($index) {
                $index = $client->getIndex($index);

                if (isset($index)) {
                    $type = $index->getType($type_name);
                }
            }
        }

        return $type;
    }
}
