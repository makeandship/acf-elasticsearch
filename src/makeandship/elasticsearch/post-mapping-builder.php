<?php

namespace makeandship\elasticsearch;

class PostMappingBuilder extends MappingBuilder {

	const EXCLUDE_POST_TYPES = array(
		'revision',
		'attachment',
		'json_consumer',
		'nav_menu',
		'nav_menu_item',
		'post_format',
		'link_category',
		'acf-field-group',
		'acf-field'
	);

	const CORE_FIELDS = array(
		'post_content' => array( 
			'type' => 'string', 
			'suggest' => true,
			'transformer' => 'makeandship\elasticsearch\transformer\HtmlFieldTransformer'
		),
		'post_title' => 'string',
		'post_type' => array( 
			'type' => 'string', 
			'index' => 'not_analyzed' 
		),
		'post_date' => array(
			'type' => 'date',
			'transformer' => 'makeandship\elasticsearch\transformer\DateFieldTransformer'
		),
		'link' => array(
			'type' => 'string',
			'index' => 'not_analyzed'
		)
	);

	const CORE_DATE_FIELDS = array(
	);

	/**
	 *
	 */
	public function build ( $post_type ) {
		error_log($post_type.' is '.$this->valid($post_type));
		if (!$this->valid( $post_type )) {
			return null;
		}

		$properties = array();

		// base post fields
		foreach( PostMappingBuilder::CORE_FIELDS as $field => $options) {
			if (isset( $field ) && isset($options)) {
				$properties = array_merge( 
					$properties, 
					$this->build_field( $field, $options ) 
				);	
			}
		}

		// acf fields
		if( class_exists('acf') ) {
			// field groups for this post type
			$args = array(
				'post_type' => $post_type
			);
			$field_groups = acf_get_field_groups( $args );

			if (isset( $field_groups ) && !empty( $field_groups )) {
				foreach( $field_groups as $field_group ) {
					$field_group_id = $field_group['ID'];
					if ($field_group_id) {
						$fields = acf_get_fields( $field_group_id );

						foreach($fields as $field) {
							$properties = array_merge( 
								$properties, 
								$this->build_acf_field( $field ) 
							);
						}
					}
				}
			}
		}

		return $properties;
	}

	public function valid( $post_type ) {
		if (in_array( $post_type, self::EXCLUDE_POST_TYPES)) {
			return false;
		}
		return true;
	}

	// TODO right place?
	public function get_valid_post_types() {
		$post_types = get_post_types(array(
			'public' => true
		));
		
		$valid_post_types = array();
		foreach($post_types as $post_type) {
			if ($this->valid($post_type)) {
				$valid_post_types[] = $post_type;
			}
		}

		return $valid_post_types;
	}

	private function build_field( $field, $options ) {
		$properties = array();

		if (isset( $field ) && isset( $options )) {
			// settings
			if (is_string($options)) {
				$type = $options;
				$index = 'analyzed';
				$suggest = null;
			}
			else {
				if (array_key_exists('type', $options)) {
					$type = $options['type'];
				}
				if (array_key_exists('index', $options)) {
					$index = $options['index'];
				}
				else{
					$index = 'analyzed';
				}
				if (array_key_exists('suggest', $options)) {
					$suggest = $options['suggest'];
				}
			}

			$properties[$field] = array(
				'type' => $type,
				'index' => $index
			);

			if (isset($suggest)) {
				$properties[$field.'_suggest'] = array(
					'type' => 'string',
					'analyzer' => 'ngram_analyzer',
	    			'search_analyzer' => 'whitespace_analyzer',
				);
			}
		}

		return $properties;
	}

	private function build_acf_field( $field ) {
		$properties = array();

		if (isset( $field )) {
			if (array_key_exists('type', $field) && array_key_exists('name', $field)) {
				$acf_type = $field['type'];
				$name = $field['name'];

				// default to index each field
				$type = 'text';
				$index = 'analyzed';

				// default to text 
				// color_picker, email, page_link, radio, select, text, textarea, url, wysiwyg

				switch($acf_type) {
					case 'checkbox':
						$type = 'boolean';
						break;
					case 'date_picker':
						$type = 'date';
						break;

					case 'date_time_picker':
						$type = 'date';
						break;

					case 'file':
						break;

					case 'google_map':
						$type = 'geo_point';
						break;

					case 'image':
						// nested
						break;

					case 'message':
						break;

					case 'number':
						$type = 'long';
						break;

					case 'oembed':
						// nested
						break;

					case 'password':
						// dont index
						break;

					case 'post_object':
						// id?
						break;

					case 'relationship':
						break;

					case 'taxonomy':
						break;

					case 'time_picker':
						$type = 'long';
						break;

					case 'true_false':
						$type = 'boolean';
						break;

					case 'user':
						// custom
						break;
  
				}

				if (isset($type)) {
					$properties[$field] = array(
						'type' => $type,
						'index' => $index
					);
				}

			}
			
			
		}

		return $properties;
	}
}