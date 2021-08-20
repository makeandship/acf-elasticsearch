<?php

namespace makeandship\elasticsearch;

class PostMappingBuilderV5 extends PostMappingBuilder
{
    const CORE_FIELDS = array(
        'type'         => array(
            'type'  => 'string',
            'index' => 'not_analyzed',
        ),
        'post_content' => array(
            'type'        => 'string',
            'suggest'     => true,
            'transformer' => 'makeandship\elasticsearch\transformer\HtmlFieldTransformer',
        ),
        'post_title'   => array(
            'type'     => 'string',
            'suggest'  => true,
            'sortable' => true,
        ),
        'parent_title'    => array(
            'type'     => 'text',
            'suggest'  => true,
            'sortable' => true,
        ),
        'parent_id'    => array(
            'type'  => 'long',
            'index' => true,
        ),
        'post_type'    => array(
            'type'  => 'string',
            'index' => 'not_analyzed',
        ),
        'post_date'    => array(
            'type'        => 'date',
            'transformer' => 'makeandship\elasticsearch\transformer\DateFieldTransformer',
        ),
        'post_modified'    => array(
            'type'        => 'date',
            'transformer' => 'makeandship\elasticsearch\transformer\DateFieldTransformer',
        ),
        'link'         => array(
            'type'  => 'string',
            'index' => 'not_analyzed',
        ),
    );

    /**
     *
     */
    function build($post_type, $cascade = false)
    {
        if (!PostMappingBuilder::valid($post_type)) {
            return array();
        }

        $properties = array();

        // base post fields
        foreach (PostMappingBuilderV5::CORE_FIELDS as $field => $options) {
            if (isset($field) && isset($options)) {
                $properties = array_merge(
                    $properties,
                    $this->build_field($field, $options, $cascade)
                );
            }
        }

        // acf fields
        if (class_exists('acf')) {
            // field groups for this post type
            $args = array(
                'post_type'     => $post_type,
                'post_template' => 'default',
                'page_template' => 'default',
            );
            $field_groups = acf_get_field_groups($args);

            if (isset($field_groups) && !empty($field_groups)) {
                foreach ($field_groups as $field_group) {
                    $field_group_id = $field_group['ID'];
                    if ($field_group_id) {
                        $fields = acf_get_fields($field_group_id);

                        foreach ($fields as $field) {
                            $field_properties = $this->build_acf_field($field, $cascade);
                            $properties       = array_merge(
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
            if (!in_array($name, MappingBuilder::EXCLUDE_TAXONOMIES)) {
                $properties = array_merge(
                    $properties,
                    $this->build_taxonomy($name, $taxonomy)
                );
            }
        }

        return $properties;
    }

    function build_field($field, $options, $cascade)
    {
        $properties = array();

        if (isset($field) && isset($options)) {
            // settings
            if (is_string($options)) {
                $type    = $options;
                $index   = 'analyzed';
                $suggest = null;
            } else {
                if (array_key_exists('type', $options)) {
                    $type = $options['type'];
                }
                if (array_key_exists('index', $options)) {
                    $index = $options['index'];
                } else {
                    $index = 'analyzed';
                }
                if (array_key_exists('suggest', $options)) {
                    $suggest = $options['suggest'];
                }
                if (array_key_exists('sortable', $options)) {
                    $sortable = $options['sortable'];
                }
            }

            $properties[$field] = array(
                'type'  => $type,
                'index' => $index,
            );

            if (isset($suggest)) {
                $properties[$field . '_suggest'] = array(
                    'type'            => 'string',
                    'analyzer'        => 'ngram_analyzer',
                    'search_analyzer' => 'whitespace_analyzer',
                );
            }

            if (isset($sortable)) {
                $properties[$field . '_sortable'] = array(
                    'type' => 'keyword',
                );
            }
        }

        return $properties;
    }

    function build_acf_field($field, $cascade = false)
    {
        $properties = array();

        if (isset($field)) {
            if (array_key_exists('type', $field) && array_key_exists('name', $field) && $field['type'] != 'tab') {
                $acf_type = $field['type'];
                $name     = $field['name'];

                // default to index each field
                $props = array(
                    'type'  => 'string',
                    'index' => 'analyzed',
                );

                // default to text
                // color_picker, email, page_link, radio, select, text, textarea, url, wysiwyg

                switch ($acf_type) {
                    case 'checkbox':
                        $props['type']  = 'string';
                        $props['index'] = 'not_analyzed';
                        break;
                    case 'date_picker':
                        $props['type']  = 'date';
                        $props['index'] = 'not_analyzed';
                        break;

                    case 'date_time_picker':
                        $props['type']  = 'date';
                        $props['index'] = 'not_analyzed';
                        break;

                    case 'file':
                        break;

                    case 'google_map':
                        $props['type']  = 'geo_point';
                        $props['index'] = 'not_analyzed';
                        break;

                    case 'group':
                        $props['type']       = 'nested';
                        $props['properties'] = array();
                        unset($props['index']);

                        foreach ($field['sub_fields'] as $sub_field) {
                            $sub_field_name  = $sub_field['name'];
                            $sub_field_props = $this->build_acf_field($sub_field, $cascade);

                            if (isset($sub_field_props) && !empty($sub_field_props)) {
                                $props['properties'] = array_merge(
                                    $props['properties'],
                                    $sub_field_props
                                );
                            }
                        }
                        break;

                    case 'image':
                        $props['type']       = 'nested';
                        $props['properties'] = array(
                            'filename'    => array(
                                'type'  => 'string',
                                'index' => 'not_analyzed',
                            ),
                            'filesize'    => array(
                                'type'  => 'long',
                                'index' => 'not_analyzed',
                            ),
                            'alt'         => array(
                                'type' => 'string',
                            ),
                            'url'         => array(
                                'type'  => 'string',
                                'index' => 'not_analyzed',
                            ),
                            'description' => array(
                                'type' => 'string',
                            ),
                            'caption'     => array(
                                'type' => 'string',
                            ),
                            'mime'        => array(
                                'type'  => 'string',
                                'index' => 'not_analyzed',
                            ),
                            'type'        => array(
                                'type'  => 'string',
                                'index' => 'not_analyzed',
                            ),
                            'subtype'     => array(
                                'type'  => 'string',
                                'index' => 'not_analyzed',
                            ),
                            'width'       => array(
                                'type'  => 'long',
                                'index' => 'not_analyzed',
                            ),
                            'height'      => array(
                                'type'  => 'long',
                                'index' => 'not_analyzed',
                            ),
                        );
                        break;

                    case 'message':
                        break;

                    case 'number':
                        $props['type']  = 'long';
                        $props['index'] = 'not_analyzed';
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
                        $name           = $name . '_relationship';
                        $props['type']  = 'long';
                        $props['index'] = 'not_analyzed';
                        break;

                    case 'repeater':
                        $props['type']       = 'nested';
                        $props['properties'] = array();
                        unset($props['index']);

                        foreach ($field['sub_fields'] as $sub_field) {
                            $sub_field_name  = $sub_field['name'];
                            $sub_field_props = $this->build_acf_field($sub_field, $cascade);

                            if (isset($sub_field_props) && !empty($sub_field_props)) {
                                $props['properties'] = array_merge(
                                    $props['properties'],
                                    $sub_field_props
                                );
                            }
                        }
                        break;

                    case 'select':
                        $props['type']  = 'string';
                        $props['index'] = 'not_analyzed';
                        break;

                    case 'taxonomy':
                        break;

                    case 'time_picker':
                        $props['type']   = 'date';
                        $props['index']  = 'not_analyzed';
                        $props['format'] = 'HH:mm:ss';
                        break;

                    case 'true_false':
                        $props['type']  = 'boolean';
                        $props['index'] = 'not_analyzed';
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

    function build_taxonomy($name, $taxonomy)
    {
        $properties = array();

        if (isset($name)) {
            $properties[$name] = array(
                "index" => "not_analyzed",
                "type"  => "string",
            );

            $properties[$name . '_name'] = array(
                "type" => "string",
            );

            $properties[$name . '_suggest'] = array(
                "analyzer"        => "ngram_analyzer",
                "search_analyzer" => "whitespace_analyzer",
                "type"            => "string",
            );
        }

        return $properties;
    }
}