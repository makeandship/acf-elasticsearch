<?php

namespace makeandship\elasticsearch;

use makeandship\elasticsearch\transformer\HtmlFieldTransformer;
use makeandship\elasticsearch\transformer\DateFieldTransformer;

class PostDocumentBuilder extends DocumentBuilder {

	/**
	 *
	 */
	public function build ( $post ) {
		$document = null;

		if (isset($post)) {
			$document = array();

			foreach(PostMappingBuilder::CORE_FIELDS as $name => $definition) {
				if ($name === 'link') {
					$link = get_permalink( $post->ID );
					$document['link'] = $link;
				}
				else {
					$value = $post->{$name};

					// transform if required
					if (is_array($definition) && array_key_exists('transformer', $definition)) {
						$transformer = new $definition['transformer'];

						if (isset($transformer)) {
							$value = $transformer->transform($value);
						}
					}

					$document[$name] = $value; 

					if (is_array($definition) && array_key_exists('suggest', $definition)) {
						if ($definition['suggest']) {
							$document[$name.'_suggest'] = $value;
						}
					}
				}
			}

			// acf fields
			if( class_exists('acf') ) {
				$post_type = $post->post_type;
				
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
								$field_document = $this->build_acf_field( $field, $post );
								if (isset($field_document)) {
									$document = array_merge( 
										$document, 
										$field_document
									);
								}
							}
						}
					}
				}
			}
		}

		return $document;
	}

	private function build_acf_field( $field, $post ) {
		$document = null;

		if (isset($field) && isset($post)) {
			if (array_key_exists('name', $field)) {
				$name = $field['name'];
				$type = $field['type'];
				$value = get_field( $name, $post->ID );
				
				if (isset($value) && !empty($value)) {
					$value = $this->transform_acf_value( $value, $type );
					$document = array();
					$document[$name] = $value;
				}
			}
		}

		return $document;
	}

	private function transform_acf_value( $value, $type ) {
		$transformer = null;

		switch($acf_type) {
			
			case 'checkbox':
				break;
			case 'color_picker': 
				break;
			case 'date_picker':
				$transformer = new DateFieldTransformer();
				break;
			case 'date_time_picker':
				$transformer = new DateFieldTransformer();
				break;
			case 'file':
				break;
			case 'google_map':
				break;
			case 'image':
				// nested
				break;
			case 'message':
				break;
			case 'number':
				break;
			case 'oembed':
				// nested
				break;
			case 'page_link':
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
				break;
			case 'true_false':
				break;
			case 'user':
				// custom
				break;
			case 'wysiwyg':
				$transformer = new HtmlFieldTransformer();
				break;

		if ($transformer) {
			$value = $transformer->transform( $value );
		}

		return $value;
	}

	/**
	 *
	 */
	public function get_id( $post ) {
		return $post->ID;
	}
}