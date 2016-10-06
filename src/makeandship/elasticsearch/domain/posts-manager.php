<?php

namespace makeandship\elasticsearch\domain;

class PostsManager {
	function __construct() {

	}

	function initialise_status() {
		if (is_multisite()) {
			return $this->initialise_status_multisite();
		}
		else {
			return $this->initialise_status_multisite();
		}
	}

	private function initialise_status_singlesite() {

	}

	private function initialise_status_multisite() {
		$status = array();

		$sites_manager = new SitesManager();
		$sites = $sites_manager->get_sites();

		foreach($sites as $site) {
			$blog_id = $site->blog_id;

			$total = $this->get_posts_count( $blog_id );
			
			$status[$blog_id] = array(
				'page' => 1,
				'count' => 0,
				'total' => $total
			);
		}

		return $status;
	}

	public function get_posts_count( $blog_id ) {
		$count = 0;

		if (isset($blog_id)) {
			// target site
			switch_to_blog($blog_id);

			$args = $this->get_count_post_args();
			$count = get_posts($args);

			// back to the original
			restore_current_blog();
		}

		return $count;
	}

	public function get_posts( $blog_id ) {

	}

	private function get_count_post_args( ) {
		$args = array(
			'post_type' => $post_types,
			'post_status' => 'publish',
			'fields' => 'count'
		);		
	}	

	private function get_paginated_post_args( $page, $per ) {
		
	}
}