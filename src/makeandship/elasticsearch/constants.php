<?php

namespace makeandship\elasticsearch;

class Constants
{

    // index
    const DEFAULT_SHARDS = 1;
    const DEFAULT_REPLICAS = 1;

    // elastica
    const SETTING_URL = 'url';
    const SETTING_TIMEOUT = 'timeout';
    const SETTING_USERNAME = 'username';
    const SETTING_PASSWORD = 'password';

    const DEFAULT_WRITE_TIMEOUT = 30;
    const DEFAULT_READ_TIMEOUT = 30;

    // plugin
    const VERSION = '0.0.1';
    const DB_VERSION = 1;

    const OPTION_SERVER = 'acf_elasticsearch_server';
    const OPTION_PRIMARY_INDEX = 'acf_elasticsearch_primary_index';
    const OPTION_SECONDARY_INDEX = 'acf_elasticsearch_secondary_index';
    const OPTION_PRIVATE_PRIMARY_INDEX = 'acf_elasticsearch_private_primary_index';
    const OPTION_PRIVATE_SECONDARY_INDEX = 'acf_elasticsearch_private_secondary_index';
    const OPTION_READ_TIMEOUT = 'acf_elasticsearch_read_timeout';
    const OPTION_WRITE_TIMEOUT = 'acf_elasticsearch_write_timeout';
    const OPTION_INDEX_STATUS = 'acf_elasticsearch_index_status';
    const OPTION_USERNAME = 'acf_elasticsearch_username';
    const OPTION_PASSWORD = 'acf_elasticsearch_password';
    const OPTION_POST_TYPES = 'acf_elasticsearch_post_types';
    const OPTION_CAPABILITY = 'acf_elasticsearch_capability';
    const OPTION_SEARCH_FIELDS = 'acf_elasticsearch_search_fields';
    const OPTION_WEIGHTINGS = 'acf_elasticsearch_weightings';
    const OPTION_FUZZINESS = 'acf_elasticsearch_fuzziness';

    // indexer
    const STATUS_PUBLISH = 'publish';
    const STATUS_PRIVATE = 'private';
    const INDEX_POST_STATUSES = [Constants::STATUS_PUBLISH, Constants::STATUS_PRIVATE];

    // TODO move to settings
    const DEFAULT_POSTS_PER_PAGE = 20;
    const DEFAULT_TERMS_PER_PAGE = 50;
    const DEFAULT_SITES_PER_PAGE = 50;

    // no instantiation
    protected function __construct()
    {
    }
}
