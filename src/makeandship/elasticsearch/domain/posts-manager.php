<?php

namespace makeandship\elasticsearch\domain;

use makeandship\elasticsearch\Constants;
use makeandship\elasticsearch\settings\SettingsManager;

class PostsManager
{
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
    
    public function __construct()
    {
    }

    public function initialise_status()
    {
        if (is_multisite()) {
            return $this->initialise_status_multisite();
        } else {
            return $this->initialise_status_singlesite();
        }
    }

    private function initialise_status_singlesite()
    {
        $total = $this->get_posts_count(null);
        $status = array(
            'page' => 1,
            'count' => 0,
            'total' => $total
        );

        return $status;
    }

    private function initialise_status_multisite()
    {
        $status = array();

        $sites_manager = new SitesManager();
        $sites = $sites_manager->get_sites();

        foreach ($sites as $site) {
            $blog_id = $site->blog_id;

            $total = $this->get_posts_count($blog_id);
            
            $status[$blog_id] = array(
                'page' => 1,
                'count' => 0,
                'total' => $total,
                'blog_id' => $site->blog_id
            );
        }

        return $status;
    }

    public function get_posts_count($blog_id=null)
    {
        $count = 0;

        if (isset($blog_id)) {
            // target site
            switch_to_blog($blog_id);

            $args = $this->get_count_post_args();
            $count = intval((new \WP_Query($args))->found_posts);

            // back to the original
            restore_current_blog();
        } else {
            $args = $this->get_count_post_args();
            $count = intval((new \WP_Query($args))->found_posts);
        }

        return $count;
    }

    public function get_posts($blog_id, $page, $per)
    {
        if (isset($blog_id)) {
            switch_to_blog($blog_id);
        }

        $args = $this->get_paginated_post_args($page, $per);
        $posts = get_posts($args);

        if (isset($blog_id)) {
            restore_current_blog();
        }

        return $posts;
    }

    private function get_count_post_args()
    {
        $post_types = $this->get_valid_post_types();
        
        $args = array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'fields' => 'count'
        );

        return $args;
    }

    private function get_paginated_post_args($page, $per)
    {
        $post_types = $this->get_valid_post_types();
        
        $args = array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => $per,
            'paged' => $page
        );

        return $args;
    }

    /**
     * Get all valid post types
     *
     * @return array of post types
     */
    public function get_valid_post_types()
    {
        $post_types = get_post_types(array(
            'public' => true
        ));
        
        $valid_post_types = array();

        foreach ($post_types as $post_type) {
            if ($this->valid($post_type)) {
                $valid_post_types[] = $post_type;
            }
        }

        return $valid_post_types;
    }

    public function valid($post_type)
    {
        $types = SettingsManager::get_instance()->get(Constants::OPTION_POST_TYPES);

        if (!$types) {
            return false;
        } elseif (in_array($post_type, self::EXCLUDE_POST_TYPES)) {
            return false;
        } elseif (in_array($post_type, $types)) {
            return true;
        }
        return false;
    }
}
