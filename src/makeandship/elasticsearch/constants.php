<?php

namespace makeandship\elasticsearch;

class Constants
{
    // index
    const DEFAULT_SHARDS   = 1;
    const DEFAULT_REPLICAS = 1;

    // elastica
    const SETTING_URL      = 'url';
    const SETTING_TIMEOUT  = 'timeout';
    const SETTING_USERNAME = 'username';
    const SETTING_PASSWORD = 'password';

    const DEFAULT_WRITE_TIMEOUT = 30;
    const DEFAULT_READ_TIMEOUT  = 30;

    // plugin
    const VERSION    = '7.4.15';
    const DB_VERSION = 1;

    const OPTION_SERVER                = 'acf_elasticsearch_server';
    const OPTION_INDEX                 = 'acf_elasticsearch_index';
    const OPTION_PRIVATE_INDEX         = 'acf_elasticsearch_private_index';
    const OPTION_READ_TIMEOUT          = 'acf_elasticsearch_read_timeout';
    const OPTION_WRITE_TIMEOUT         = 'acf_elasticsearch_write_timeout';
    const OPTION_INDEX_STATUS          = 'acf_elasticsearch_index_status';
    const OPTION_USERNAME              = 'acf_elasticsearch_username';
    const OPTION_PASSWORD              = 'acf_elasticsearch_password';
    const OPTION_POST_TYPES            = 'acf_elasticsearch_post_types';
    const OPTION_CAPABILITY            = 'acf_elasticsearch_capability';
    const OPTION_SEARCH_FIELDS         = 'acf_elasticsearch_search_fields';
    const OPTION_WEIGHTINGS            = 'acf_elasticsearch_weightings';
    const OPTION_FUZZINESS             = 'acf_elasticsearch_fuzziness';
    const OPTION_SLUGS_TO_EXCLUDE      = 'acf_elasticsearch_slugs_to_exclude';
    const OPTION_EXCLUSION_FIELD       = 'acf_elasticsearch_exclusion_field';
    const OPTION_IDS_TO_EXCLUDE        = 'acf_elasticsearch_ids_to_exclude';
    const OPTION_ELASTICSEARCH_VERSION = 'acf_elasticsearch_cluster_version';
    const OPTION_MAPPING_TIMEOUT       = 'acf_elasticsearch_mapping_timeout';

    // indexer
    const STATUS_PUBLISH      = 'publish';
    const STATUS_PRIVATE      = 'private';
    const INDEX_POST_STATUSES = [Constants::STATUS_PUBLISH, Constants::STATUS_PRIVATE];

    const DEFAULT_POSTS_PER_PAGE = 100;
    const DEFAULT_TERMS_PER_PAGE = 50;
    const DEFAULT_SITES_PER_PAGE = 50;
    const DEFAULT_MAPPING_TYPE   = 'document';

    // always ignore these post types
    const EXCLUDE_POST_TYPES = array(
        'revision',
        'json_consumer',
        'nav_menu',
        'nav_menu_item',
        'post_format',
        'link_category',
        'acf-field-group',
        'acf-field',
    );

    // no instantiation
    function __construct()
    {
    }
}
