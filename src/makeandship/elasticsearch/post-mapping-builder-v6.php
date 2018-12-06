<?php

namespace makeandship\elasticsearch;

class PostMappingBuilderV6 extends PostMappingBuilder
{
    const CORE_FIELDS = array(
        'type' => array(
            'type' => 'keyword',
            'index' => true
        ),
        'post_content' => array(
            'type' => 'text',
            'suggest' => true,
            'transformer' => 'makeandship\elasticsearch\transformer\HtmlFieldTransformer'
        ),
        'post_title' => array(
            'type' => 'text',
            'suggest' => true
        ),
        'post_type' => array(
            'type' => 'keyword',
            'index' => true
        ),
        'post_date' => array(
            'type' => 'date',
            'transformer' => 'makeandship\elasticsearch\transformer\DateFieldTransformer'
        ),
        'link' => array(
            'type' => 'keyword',
            'index' => true
        )
    );

    /**
     *
     */
    public function build($post_type)
    {
        if (!PostMappingBuilder::valid($post_type)) {
            return array();
        }

        $properties = array();

        // base post fields
        foreach (PostMappingBuilderV6::CORE_FIELDS as $field => $options) {
            if (isset($field) && isset($options)) {
                $properties = array_merge(
                    $properties,
                    $this->build_field($field, $options)
                );
            }
        }

        // acf fields
        if (class_exists('acf')) {
            // field groups for this post type
            $args = array(
                'post_type' => $post_type
            );
            $field_groups = acf_get_field_groups($args);

            if (isset($field_groups) && !empty($field_groups)) {
                foreach ($field_groups as $field_group) {
                    $field_group_id = $field_group['ID'];
                    if ($field_group_id) {
                        $fields = acf_get_fields($field_group_id);

                        foreach ($fields as $field) {
                            $field_properties = $this->build_acf_field($field);
                            $properties = array_merge(
                                $properties,
                                $field_properties
                            );
                        }
                    }
                }
            }
        }

        // post taxonomies
        $taxonomies = get_object_taxonomies($post_type, 'objects');
        foreach ($taxonomies as $name => $taxonomy) {
            if (!in_array($name, PostMappingBuilder::EXCLUDE_TAXONOMIES)) {
                $properties = array_merge(
                    $properties,
                    $this->build_taxonomy($name, $taxonomy)
                );
            }
        }

        return $properties;
    }

    private function build_field($field, $options)
    {
        $properties = array();

        if (isset($field) && isset($options)) {
            // settings
            if (is_string($options)) {
                $type = $options;
                $index = true;
                $suggest = null;
            } else {
                if (array_key_exists('type', $options)) {
                    $type = $options['type'];
                }
                if (array_key_exists('index', $options)) {
                    $index = $options['index'];
                } else {
                    $index = true;
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
                    'type' => 'text',
                    'analyzer' => 'ngram_analyzer',
                    'search_analyzer' => 'whitespace_analyzer',
                );
            }
        }

        return $properties;
    }

    private function build_acf_field($field)
    {
        $properties = array();

        if (isset($field)) {
            if (
                array_key_exists('type', $field) &&
                array_key_exists('name', $field) &&
                $field['type'] != 'tab') {
                $acf_type = $field['type'];
                $name = $field['name'];

                // default to index each field
                $props = array(
                    'type' => 'text',
                    'index' => true
                );

                // default to text
                // color_picker, email, page_link, radio, select, text, textarea, url, wysiwyg

                switch ($acf_type) {
                    case 'checkbox':
                        $props['type'] = 'boolean';
                        $props['index'] = true;
                        break;
                    case 'date_picker':
                        $props['type'] = 'date';
                        $props['index'] = true;
                        break;

                    case 'date_time_picker':
                        $props['type'] = 'date';
                        $props['index'] = true;
                        break;

                    case 'file':
                        break;

                    case 'google_map':
                        $props['type'] = 'geo_point';
                        $props['index'] = true;
                        break;

                    case 'group':
                        $props['type'] = 'nested';
                        $props['properties'] = array();
                        unset($props['index']);

                        foreach ($field['sub_fields'] as $sub_field) {
                            $sub_field_name = $sub_field['name'];
                            $sub_field_props = $this->build_acf_field($sub_field);

                            if (isset($sub_field_props) && !empty($sub_field_props)) {
                                $props['properties'] = array_merge(
                                    $props['properties'],
                                    $sub_field_props
                                );
                            }
                        }
                        break;

                    case 'image':
                        $props['type'] = 'nested';
                        $props['properties'] = array(
                          'filename'  => array(
                            'type' => 'text',
                            'index' => false
                          ),
                          'filesize'  => array(
                            'type' => 'long',
                            'index' => false
                          ),
                          'alt'  => array(
                            'type' => 'text'
                          ),
                          'url'  => array(
                            'type' => 'text'
                          ),
                          'description'  => array(
                            'type' => 'text'
                          ),
                          'caption'  => array(
                            'type' => 'text'
                          ),
                          'mime'  => array(
                            'type' => 'keyword',
                            'index' => false
                          ),
                          'type'  => array(
                            'type' => 'keyword',
                            'index' => false
                          ),
                          'subtype'  => array(
                            'type' => 'keyword',
                            'index' => false
                          ),
                          'width'  => array(
                            'type' => 'long',
                            'index' => false
                          ),
                          'height'  => array(
                            'type' => 'long',
                            'index' => false
                          ),
                        );
                        unset($props['index']);
                        break;

                    case 'message':
                        break;

                    case 'number':
                        $props['type'] = 'long';
                        $props['index'] = true;
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
                        // alter name to identify where further processing is required on lookup
                        $name = $name.'_relationship';
                        $props['type'] = 'long';
                        $props['index'] = true;
                        break;

                    case 'repeater':
                        $props['type'] = 'nested';
                        $props['properties'] = array();
                        unset($props['index']);

                        foreach ($field['sub_fields'] as $sub_field) {
                            $sub_field_name = $sub_field['name'];
                            $sub_field_props = $this->build_acf_field($sub_field);

                            if (isset($sub_field_props) && !empty($sub_field_props)) {
                                $props['properties'] = array_merge(
                                    $props['properties'],
                                    $sub_field_props
                                );
                            }
                        }
                        break;

                    case 'select':
                        $props['type'] = 'keyword';
                        $props['index'] = true;
                        break;

                    case 'taxonomy':
                        break;

                    case 'time_picker':
                        $props['type'] = 'date';
                        $props['index'] = true;
                        $props['format'] = 'HH:mm:ss';
                        break;

                    case 'true_false':
                        $props['type'] = 'boolean';
                        $props['index'] = true;
                        break;

                    case 'user':
                        // custom
                        break;

                }

                if (isset($props) && isset($name)) {
                    $properties[$name] = $props;
                }
            }
        }

        return $properties;
    }

    private function build_taxonomy($name, $taxonomy)
    {
        $properties = array();

        if (isset($name)) {
            $properties[$name] = array(
                "index" => true,
                "type" => "keyword"
            );

            $properties[$name.'_name'] = array(
                "type" => "text"
            );

            $properties[$name.'_suggest'] = array(
                "analyzer" => "ngram_analyzer",
                "search_analyzer" => "whitespace_analyzer",
                "type" => "text"
            );
        }

        return $properties;
    }
}
