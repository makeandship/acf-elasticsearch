<?php

namespace makeandship\elasticsearch;

class SiteMappingBuilder extends MappingBuilder
{
    const CORE_FIELDS = array(
        'blog_id' => array(
            'type' => 'integer'
        )
    );

    /**
     *
     */
    public function build($name=null, $cascade=false)
    {
        $properties = array();

        // base post fields
        foreach (SiteMappingBuilder::CORE_FIELDS as $field => $options) {
            if (isset($field) && isset($options)) {
                $properties = array_merge(
                    $properties,
                    $this->build_field($field, $options, $cascade)
                );
            }
        }

        // options - acf fields
        if (class_exists('acf')) {
            $options = get_fields('options');
            foreach ($options as $option_name => $option_value) {
                $field_object = get_field_object($option_name, 'options');
                if (isset($field_object)) {
                    $properties = array_merge(
                        $properties,
                        $this->build_option($field_object)
                    );
                }
            }
        }

        return $properties;
    }

    private function build_field($field, $options, $cascade=false)
    {
        $properties = array();

        if (isset($field) && isset($options)) {
            // settings
            if (is_string($options)) {
                $type = $options;
                $index = 'analyzed';
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

    private function build_option($field_object)
    {
        $properties = array();

        if (isset($field_object)) {
            $name = $field_object['name'];

            // string, color_picker, select
            $type = 'string';
            switch ($field_object['type']) {
                case 'date_picker':
                    $type = 'date';
                    break;

            }

            $properties[$name] = array(
                'type' => $type,
                'index' => 'analyzed'
            );
        }

        return $properties;
    }
}
