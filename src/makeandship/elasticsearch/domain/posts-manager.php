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

    public function initialise_status($include_private=false)
    {
        if (is_multisite()) {
            return $this->initialise_status_multisite($include_private);
        } else {
            return $this->initialise_status_singlesite($include_private);
        }
    }

    private function initialise_status_singlesite($include_private=false)
    {
        $total = $this->get_posts_count(null, $include_private);
        $status = array(
            'page' => 1,
            'count' => 0,
            'total' => $total
        );

        return $status;
    }

    private function initialise_status_multisite($include_private=false)
    {
        $status = array();

        $sites_manager = new SitesManager();
        $sites = $sites_manager->get_sites();

        foreach ($sites as $site) {
            $blog_id = $site->blog_id;

            $total = $this->get_posts_count($blog_id, $include_private);
            
            $status[$blog_id] = array(
                'page' => 1,
                'count' => 0,
                'total' => $total,
                'blog_id' => $site->blog_id
            );
        }

        return $status;
    }

    public function get_posts_count($blog_id=null, $include_private=false)
    {
        $count = 0;

        if (isset($blog_id)) {
            // target site
            switch_to_blog($blog_id);

            $args = $this->get_count_post_args($include_private);
            $count = intval((new \WP_Query($args))->found_posts);

            // back to the original
            restore_current_blog();
        } else {
            $args = $this->get_count_post_args($include_private);
            $count = intval((new \WP_Query($args))->found_posts);
        }

        return $count;
    }

    public function get_posts($blog_id, $page, $per, $include_private=false)
    {
        if (isset($blog_id)) {
            switch_to_blog($blog_id);
        }

        $args = $this->get_paginated_post_args($page, $per, $include_private);
        $posts = get_posts($args);

        if (isset($blog_id)) {
            restore_current_blog();
        }

        return $posts;
    }

    private function get_count_post_args($include_private=false)
    {
        $post_types = $this->get_valid_post_types();
        $post_status = $include_private ? array( 'publish', 'private' ) : 'publish';
        
        $args = array(
            'post_type' => $post_types,
            'post_status' => $post_status,
            'fields' => 'count'
        );

        return $args;
    }

    private function get_paginated_post_args($page, $per, $include_private=false)
    {
        $post_types = $this->get_valid_post_types();
        $post_status = $include_private ? array( 'publish', 'private' ) : 'publish';
        
        $args = array(
            'post_type' => $post_types,
            'post_status' => $post_status,
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
        $option_post_types = SettingsManager::get_instance()->get(Constants::OPTION_POST_TYPES);

        $types = [];

        foreach ($option_post_types as $item) {
            $types[] = $item['type'];
        }

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
