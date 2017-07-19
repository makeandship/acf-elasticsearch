<?php

namespace makeandship\elasticsearch\domain;

class TaxonomiesManager {
	function __construct() {

	}

	/**
	 * Get all terms from the db
	 *
	 * @return array of WP_Site
	 */
	public function get_taxonomies() {
		global $wpdb;
		$sql = $wpdb->prepare("
			select 
				* 
			from
				$wpdb->terms wt,
				$wpdb->term_taxonomy wtt
			where
				wt.term_id = wtt.term_id 
				AND 1 = %s ", "1");
		//$terms = $wpdb->get_results($sql, OBJECT);
		$terms = get_terms([]);


		return $terms;
	}
}