<?php

namespace makeandship\elasticsearch\domain;

class OptionsManager {
	function __construct() {

	}

	/**
	 * Add a option at site or local level
	 *
	 * @param $key the name of the option
	 * @param $value the initial value 
	 */
	public function add( $key, $value ) {
		if (is_multisite()) {
			add_site_option( $key, $value );
		}
		else {
			add_option( $key, $value );
		}
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
	public function get( $key ) {
		if (is_multisite()) {
			return get_site_option( $key );
		}
		else {
			return get_option( $key );
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
	public function set( $key, $value ) {
		if (is_multisite()) {
			return update_site_option( $key, $value );
		}
		else {
			return update_option( $key, $value );
		}
	}
}