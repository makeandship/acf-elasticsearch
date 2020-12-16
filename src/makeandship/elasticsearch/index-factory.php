<?php
namespace makeandship\elasticsearch;

use makeandship\elasticsearch\settings\SettingsManager;
use \Elastica\Client;

class IndexFactory
{
    private static $clients;
    private static $indexes;

    public function __construct()
    {
        if (!isset(self::$indexes) || !is_array(self::$indexes)) {
            self::$indexes = array();
        }
    }

    public function get_client($writable = false)
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

    final public function create($private, $writable = false)
    {

        if ($private) {
            $name =
            SettingsManager::get_instance()->get(Constants::OPTION_PRIVATE_INDEX);
        } else {
            $name =
            SettingsManager::get_instance()->get(Constants::OPTION_INDEX);
        }

        if (self::$indexes) {
            $index = Util::safely_get_attribute(self::$indexes, $name);
            if ($index) {
                return $index;
            }
        }

        $client = $this->get_client($writable);

        if (isset($client)) {
            if ($name) {
                $index                = $client->getIndex($name);
                self::$indexes[$name] = $index;

                return $index;
            }
        }

        return null;
    }

}
