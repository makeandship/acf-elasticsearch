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
				$value = get_field( $name, $post->ID );
				
				if (isset($value) && !empty($value)) {
					// transform value based on field type
					$document = array();
					$document[$name] = $value;
				}
			}
		}

		return $document;
	}

	/**
	 *
	 */
	public function get_id( $post ) {
		return $post->ID;
	}
}