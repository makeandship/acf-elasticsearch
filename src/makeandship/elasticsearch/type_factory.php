<?php

namespace makeandship\elasticsearch;

use \Elastica\Client;

class TypeFactory {

	const SETTING_URL = 'url';
	const SETTING_TIMEOUT = 'timeout';

	const DEFAULT_WRITE_TIMEOUT = 30;
	const DEFAULT_READ_TIMEOUT = 30;

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

			$settings[TypeFactory::SETTING_URL] = 
				$this->config[ACFElasticSearchPlugin::OPTION_SERVER];

			if ($writable) {
				$settings[TypeFactory::SETTING_TIMEOUT] = $this->use_attribute_or_default(
					$this->config,
					ACFElasticSearchPlugin::OPTION_WRITE_TIMEOUT,
					TypeFactory::DEFAULT_WRITE_TIMEOUT
				);
			}
			else {
				$settings[TypeFactory::SETTING_TIMEOUT] = $this->use_attribute_or_default(
					$this->config,
					ACFElasticSearchPlugin::OPTION_READ_TIMEOUT,
					TypeFactory::DEFAULT_READ_TIMEOUT
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
	public function create( $type_name, $writable=false ) {
		$type = null;

		$client = $this->get_client( $writable );
		
		if (isset($client) && array_key_exists(ACFElasticSearchPlugin::OPTION_PRIMARY_INDEX, $this->config)) {
			$index_name = $this->config[ACFElasticSearchPlugin::OPTION_PRIMARY_INDEX];
			$index = $client->getIndex($index_name);

			if (isset($index)) {
				$type = $index->getType($type_name);
			}
		}

		return $type;
	}
}