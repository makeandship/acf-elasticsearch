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