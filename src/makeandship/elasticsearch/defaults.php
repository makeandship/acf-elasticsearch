<?php
namespace makeandship\elasticsearch;

/**
 * Returns a set of default values that are sufficient for indexing wordpress if the user does not set any values.
 *
 * @license http://opensource.org/licenses/MIT
 * @author Paris Holley <mail@parisholley.com>
 * @version 4.0.1
 **/
class Defaults
{
	/**
	 * Useful field names that wordpress provides out the box
	 *
	 * @return string[] field names
	 **/
	static function fields()
	{
		return array('post_content', 'post_title', 'post_type');
	}

	/**
	 * Returns any post types currently defined in wordpress
	 *
	 * @return string[] post type names
	 **/
	static function types()
	{
		$types = get_post_types();

		$available = array();

		foreach ($types as $type) {
			$tobject = get_post_type_object($type);

			if (!$tobject->exclude_from_search && $type != 'attachment') {
				$available[] = $type;
			}
		}

		return $available;
	}

	/**
	 * Returns any taxonomies registered for the provided post types
	 *
	 * @return string[] taxonomy slugs
	 **/
	static function taxonomies($types)
	{
		$taxes = array();

		foreach ($types as $type) {
			$taxes = array_merge($taxes, get_object_taxonomies($type));
		}

		return array_unique($taxes);
	}

	/**
	 * Returns all customfields registered for any post type.
	 * Copied method meta_form() from admin/includes/templates.php as inline method ... damn those dirty wordpress suckers!!!
	 * @return string[] meta keys sorted
	 **/
	static function meta_fields()
	{
		if( class_exists('acf') ) {
			$keys = array();

			$field_groups = acf_get_field_groups();

			foreach($field_groups as $field_group) {
				$location = $field_group['location'];
				
				$post_type_group = self::is_post_type_field_group($field_group);

				if ( $post_type_group ) {
					$field_group_id = $field_group['ID'];
					
					$fields = acf_get_fields_by_id( $field_group_id );

					if (isset($fields)) {
						foreach($fields as $field) {
							$keys = array_merge( $keys, self::meta_fields_type( null, $field ) );
						}
					}
				}
			}
			
			return $keys;
		}
		else {
			global $wpdb;
			$keys = $wpdb->get_col("SELECT meta_key
	                            FROM $wpdb->postmeta
	                            GROUP BY meta_key
	                            HAVING meta_key NOT LIKE '\_%'
	                            ORDER BY meta_key");
			if ($keys) {
				natcasesort($keys);
			} else {
				$keys = array();
			}
			return $keys;
		}
	}

	private static function meta_fields_type($prefix, $field) {
		$keys  = array();

		$type = $field['type'];
		$name = $field['name'];
		
		if (isset($name) && !empty($name)) {
			if ($field['type'] === 'repeater') {
				if (array_key_exists( 'sub_fields', $field )) {
					$prefix = isset($prefix) ? $prefix.".".$name : $name;

					foreach($field['sub_fields'] as $sub_field) {
						$keys = array_merge( $keys, self::meta_fields_type( $prefix, $sub_field ));
					}
				}
			}
			else {
				$keys[] = isset($prefix) ? $prefix.".".$name : $name;
			}
		}

		return $keys;
	}

	private static function is_post_type_field_group( $field_group ) {
		$post_type_group = false;

		if (isset($field_group) && array_key_exists( 'location', $field_group )) {
			$location = $field_group['location'];

			foreach( $location as $rules) {
				foreach ($rules as $rule) {
					if ($rule['param'] === 'post_type') {
						$post_type_group = true;
						break;
					}
				}
			}
		}

		return $post_type_group;
	}
}

?>
