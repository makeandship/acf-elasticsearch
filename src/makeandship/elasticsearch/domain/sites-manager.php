<?php

namespace makeandship\elasticsearch\domain;

class SitesManager {
	function __construct() {

	}

	/**
	 * Get all network sites excluding the network site
	 *
	 * @return array of WP_Site
	 */
	public function get_sites() {
        $args = $this->get_site_args();

        $sites = get_sites($args);
		
		foreach ($sites as &$site) {
			$blog_id = $site->blog_id;
			if ( is_main_site($blog_id) ) {
				unset($site);
			}
		}

		return $sites;
	}

	private function get_site_args() {
		return array(
			'limit' => 0
		);
	}
}