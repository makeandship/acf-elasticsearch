<?php

namespace makeandship\elasticsearch\domain;

class TaxonomiesManager
{
    public function __construct()
    {
    }

    /**
     * Get all terms from the db
     *
     * @return array of WP_Site
     */
    public function get_taxonomies()
    {
        $terms = get_terms(array(
            'hide_empty' => false
        ));

        return $terms;
    }
}
