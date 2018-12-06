<?php

namespace makeandship\elasticsearch;

class TermMappingBuilderV5 extends TermMappingBuilder
{
    const CORE_FIELDS = array(
        'type' => array(
            'type' => 'string',
            'index' => 'not_analyzed'
        ),
        'name' => array(
            'type' => 'string',
            'suggest' => true
        ),
        'slug' => array(
            'type' => 'string',
            'index' => 'not_analyzed'
        ),
    );

    /**
     *
     */
    public function build($taxonomy, $cascade=false)
    {
        $properties = array();

        if (!$this->valid($taxonomy)) {
            return array();
        }

        // TODO implement (this is post)

        // base post fields
        foreach (TermMappingBuilderV5::CORE_FIELDS as $field => $options) {
            if (isset($field) && isset($options)) {
                $properties = array_merge(
                    $properties,
                    $this->build_field($field, $options, $cascade)
                );
            }
        }

        return $properties;
    }

    public function build_field($field, $options, $cascade=false)
    {
        $properties = array();

        if (isset($field) && isset($options)) {
            $properties = array(
                'name_suggest' => array(
                    'analyzer' => 'ngram_analyzer',
                    'search_analyzer' => 'whitespace_analyzer',
                    'type' => 'string'
                ),
                'name' => array(
                    'type' => 'string'
                ),
                'slug' => array(
                    'type' => 'string',
                    'index' => 'not_analyzed'
                    )
            );
        }

        return $properties;
    }
}
