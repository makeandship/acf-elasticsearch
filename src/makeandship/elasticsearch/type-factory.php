<?php

namespace makeandship\elasticsearch;

use \Elastica\Client;

class TypeFactory {

	private static $clients;

	function __construct( $config ) {
		$this->config = $config;
	}

	function get_client( $writable=false ) {

		$writable_key = strval($writable);

		if (isset(self::$clients) && is_array(self::$clients) && array_key_exists($writable_key, self::$clients)) {
			return self::$clients[$writable_key];
		}
		else {
		    $settings = array(
			  Constants::SETTING_URL => $this->config[Constants::OPTION_SERVER],
			  Constants::SETTING_USERNAME => ES_PRIVATE_USERNAME,
			  Constants::SETTING_PASSWORD => ES_PRIVATE_PASSWORD
		    );
			if ($writable) {
				$settings[Constants::SETTING_TIMEOUT] = $this->use_attribute_or_default(
					$this->config,
					Constants::OPTION_WRITE_TIMEOUT,
					Constants::DEFAULT_WRITE_TIMEOUT
				);
			}
			else {
				$settings[Constants::SETTING_TIMEOUT] = $this->use_attribute_or_default(
					$this->config,
					Constants::OPTION_READ_TIMEOUT,
					Constants::DEFAULT_READ_TIMEOUT
				);
			}

			$client = new Client($settings);


			if (!isset(self::$clients) || !is_array(self::$clients)) {
				self::$clients = array();
			}
			self::$clients[$writable_key] = $client;		

			return self::$clients[$writable_key];	
		}
	}

	private function use_attribute_or_default( $config, $name, $default ) {
		$value = null;

		if (array_key_exists($name, $config)) {
			$value = $this->config[$name];
		}
		else {
			$value = $default;
		}

		return $value;
	}
	
	/**
	 * Return a type to support an index or delete call
	 */
	public function create( $type_name, $writable=false, $private=false ) {
		$type = null;
		$index_key = Constants::OPTION_PUBLIC_PRIMARY_INDEX;

		if ($private) {
			$index_key = Constants::OPTION_PRIVATE_PRIMARY_INDEX;
		}

		$client = $this->get_client( $writable );
		
		if (isset($client) && array_key_exists($index_key, $this->config)) {
			$index_name = $this->config[$index_key];
			$index = $client->getIndex($index_name);

			if (isset($index)) {
				$type = $index->getType($type_name);
			}
		}

		return $type;
	}
}