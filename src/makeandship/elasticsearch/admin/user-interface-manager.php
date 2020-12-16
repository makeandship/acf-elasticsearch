<?php

namespace makeandship\elasticsearch\admin;

use makeandship\elasticsearch\Constants;

class UserInterfaceManager
{
    const MENU_SPECIFICATION = array(
        'page_icon'     => 'icon-themes',
        'page_title'    => 'ACF Elasticsearch',
        'menu_title'    => 'ACF Elasticsearch',
        'menu_icon'     => 'dashicons-search',
        'page_slug'     => 'wp/admin/index.php',
        'page_cap'      => 'manage_options',
        'page_type'     => 'menu',
        'page_parent'   => '',
        'page_position' => 100,
    );

    function __construct($version, $db_version, $plugin)
    {
        $this->version    = $version;
        $this->db_version = $db_version;
        $this->plugin     = $plugin;
    }

    function initialise_options()
    {
        $multisite       = is_multisite();
        $network_enabled = is_plugin_active_for_network(plugin_basename(__FILE__));

        if ($multisite) {
            // && $network_enabled
            add_site_option(Constants::VERSION, $this->version);
            add_site_option(Constants::DB_VERSION, $this->db_version);

            add_site_option(Constants::OPTION_READ_TIMEOUT, 30);
            add_site_option(Constants::OPTION_WRITE_TIMEOUT, 30);

            add_site_option(Constants::OPTION_INDEX_STATUS, array());
            add_site_option(Constants::OPTION_POST_TYPES, array());
            add_site_option(Constants::OPTION_CAPABILITY, '');
            add_site_option(Constants::OPTION_SEARCH_FIELDS, array());
            add_site_option(Constants::OPTION_WEIGHTINGS, array());
            add_site_option(Constants::OPTION_SLUGS_TO_EXCLUDE, array());
            add_site_option(Constants::OPTION_EXCLUSION_FIELD, '');
        } else {
            add_option(Constants::VERSION, $this->version);
            add_option(Constants::DB_VERSION, $this->db_version);

            add_option(Constants::OPTION_SERVER, '');

            add_option(Constants::OPTION_READ_TIMEOUT, 30);
            add_option(Constants::OPTION_WRITE_TIMEOUT, 30);

            add_option(Constants::OPTION_INDEX_STATUS, array());
            add_option(Constants::OPTION_POST_TYPES, array());
            add_option(Constants::OPTION_CAPABILITY, '');
            add_option(Constants::OPTION_SEARCH_FIELDS, array());
            add_option(Constants::OPTION_WEIGHTINGS, array());
            add_option(Constants::OPTION_SLUGS_TO_EXCLUDE, array());
            add_option(Constants::OPTION_EXCLUSION_FIELD, '');
        }
    }

    function initialise_settings()
    {
        // add_settings_section( $id, $title, $callback, $page )
        add_settings_section(
            'acf_elasticsearch_settings',
            'Settings',
            array($this, 'render_section_settings'),
            'acf_elasticsearch_settings_page'
        );

        add_settings_field(
            'acf_elasticsearch_read_timeout',
            'Read Timeout',
            array($this, 'render_option_read_timeout'),
            'acf_elasticsearch_settings_page',
            'acf_elasticsearch_settings'

        );
        add_settings_field(
            'acf_elasticsearch_write_timeout',
            'Write Timeout',
            array($this, 'render_option_write_timeout'),
            'acf_elasticsearch_settings_page',
            'acf_elasticsearch_settings'
        );
        add_settings_field(
            'acf_elasticsearch_post_types',
            'Post Types',
            array($this, 'render_option_post_types'),
            'acf_elasticsearch_settings_page',
            'acf_elasticsearch_settings'
        );

        register_setting(
            'acf_elasticsearch_settings',
            'acf_elasticsearch_read_timeout',
            array($this, 'sanitize_read_timeout')
        );
        register_setting(
            'acf_elasticsearch_settings',
            'acf_elasticsearch_write_timeout',
            array($this, 'sanitize_write_timeout')
        );
        register_setting(
            'acf_elasticsearch_settings',
            'acf_elasticsearch_post_types',
            array($this, 'sanitize_post_types')
        );

        add_settings_section(
            'acf_elasticsearch_mapping',
            'Mappings',
            array($this, 'render_section_mappings'),
            'acf_elasticsearch_mappings_page'
        );

        add_settings_section(
            'acf_elasticsearch_index',
            'Index',
            array($this, 'render_section_index'),
            'acf_elasticsearch_index_page'
        );
    }

    function initialise_menu()
    {
        $this->menu = add_options_page(
            UserInterfaceManager::MENU_SPECIFICATION['page_title'],
            UserInterfaceManager::MENU_SPECIFICATION['menu_title'],
            UserInterfaceManager::MENU_SPECIFICATION['page_cap'],
            UserInterfaceManager::MENU_SPECIFICATION['page_slug'],
            array(&$this, 'render_settings_page')
        );
    }

    function render_settings_page()
    {
        include 'settings.php';
    }

    function sanitize_write_timeout($input)
    {
        return intval($input);
    }

    function sanitize_read_timeout($input)
    {
        return intval($input);
    }

    function sanitize_post_types($input)
    {
        return $input;
    }

    function render_field($type, $name, $attributes)
    {
        $html = null;
        switch ($type) {
            case 'text':
                $html = [
                    '<input type="text" name="' . $name . '"',
                ];

                foreach ($attributes as $attr_name => $attr_value) {
                    $html[] = ' ' . $attr_name . '="' . $attr_value . '"';
                }

                $html[] = '>';

                $html = implode($html);
                break;
        }

        return $html;
    }

    function enqueue_scripts()
    {
        $styles  = plugins_url('acf-elasticsearch/css/style.css');
        $scripts = plugins_url('acf-elasticsearch/js/main.js');

        wp_register_style('acf-elasticsearch', $styles, null, '0.0.3');
        wp_enqueue_style('acf-elasticsearch');
        wp_register_script('acf-elasticsearch', $scripts, array('jquery'), '0.2.2');
        wp_enqueue_script('acf-elasticsearch');

        wp_localize_script('acf-elasticsearch', 'acfElasticsearchManager', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('ajax-nonce'),
        ));
    }
}
