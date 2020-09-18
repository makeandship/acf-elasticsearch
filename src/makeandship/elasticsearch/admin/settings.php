<div class="wrap">
    <?php
use makeandship\elasticsearch\admin\HtmlUtils;
use makeandship\elasticsearch\Constants;
use makeandship\elasticsearch\settings\SettingsHelper;
use makeandship\elasticsearch\settings\SettingsManager;

if (!empty($_POST)) {

    // save incoming options
    $server                  = esc_url($_POST['acf_elasticsearch_server']);
    $primary_index           = trim($_POST['acf_elasticsearch_primary_index']);
    $secondary_index         = trim($_POST['acf_elasticsearch_secondary_index']);
    $private_primary_index   = trim($_POST['acf_elasticsearch_private_primary_index']);
    $private_secondary_index = trim($_POST['acf_elasticsearch_private_secondary_index']);
    $read_timeout            = intval(trim($_POST['acf_elasticsearch_read_timeout']));
    $write_timeout           = intval(trim($_POST['acf_elasticsearch_write_timeout']));
    $username                = trim($_POST['acf_elasticsearch_username']);
    $password                = trim($_POST['acf_elasticsearch_password']);
    $post_types              = HtmlUtils::create_post_types();
    $capability              = trim($_POST['acf_elasticsearch_capability']);
    $search_fields           = HtmlUtils::create_search_fields();
    $weightings              = HtmlUtils::create_weightings();
    $fuzziness               = intval(trim($_POST['acf_elasticsearch_fuzziness']));
    $slugs_to_exclude        = HtmlUtils::create_slugs_to_exclude();
    $exclusion_field         = trim($_POST['acf_elasticsearch_exclusion_field']);
    $ids_to_exclude          = HtmlUtils::create_ids_from_slugs($slugs_to_exclude);

    SettingsManager::get_instance()->set(Constants::OPTION_SERVER, $server);
    SettingsManager::get_instance()->set(Constants::OPTION_PRIMARY_INDEX, $primary_index);
    SettingsManager::get_instance()->set(Constants::OPTION_SECONDARY_INDEX, $secondary_index);
    SettingsManager::get_instance()->set(Constants::OPTION_PRIVATE_PRIMARY_INDEX, $private_primary_index);
    SettingsManager::get_instance()->set(Constants::OPTION_PRIVATE_SECONDARY_INDEX, $private_secondary_index);
    SettingsManager::get_instance()->set(Constants::OPTION_READ_TIMEOUT, $read_timeout);
    SettingsManager::get_instance()->set(Constants::OPTION_WRITE_TIMEOUT, $write_timeout);
    SettingsManager::get_instance()->set(Constants::OPTION_USERNAME, $username);
    SettingsManager::get_instance()->set(Constants::OPTION_PASSWORD, $password);
    SettingsManager::get_instance()->set(Constants::OPTION_POST_TYPES, $post_types);
    SettingsManager::get_instance()->set(Constants::OPTION_CAPABILITY, $capability);
    SettingsManager::get_instance()->set(Constants::OPTION_SEARCH_FIELDS, $search_fields);
    SettingsManager::get_instance()->set(Constants::OPTION_WEIGHTINGS, $weightings);
    SettingsManager::get_instance()->set(Constants::OPTION_FUZZINESS, $fuzziness);
    SettingsManager::get_instance()->set(Constants::OPTION_SLUGS_TO_EXCLUDE, $slugs_to_exclude);
    SettingsManager::get_instance()->set(Constants::OPTION_EXCLUSION_FIELD, $exclusion_field);
    SettingsManager::get_instance()->set(Constants::OPTION_IDS_TO_EXCLUDE, $ids_to_exclude);
}
?>
    <h1>ACF Elasticsearch</h1>
    <div id="poststuff">
        <form method="post" action="">
            <div id="config-container" class="postbox">
                <h2 class="handle"><span>1. Configure your Elastic search server</span></h2>
                <div class="inside acf-fields -left">
                    <div class="acf-elasticsearch-container">
                        <div class="acf-elasticsearch-row">
                            <div class="twocol">
                                <label for="">Version</label>
                            </div>
                            <div class="tencol last">
                                <?php echo Constants::VERSION; ?>
                            </div>
                        </div>
                        <?php
echo HtmlUtils::render_readonly_setting(
    'Server',
    array(
        'const' => array(
            'ES_URL',
        ),
        'env'   => array(
            'ES_URL',
        ),
    )
);
echo HtmlUtils::render_readonly_setting(
    'Username',
    array(
        'const' => array(
            'ES_USERNAME',
        ),
        'env'   => array(
            'ES_USERNAME',
        ),
    )
);
echo HtmlUtils::render_readonly_setting(
    'Password',
    array(
        'const' => array(
            'ES_PASSWORD',
        ),
        'env'   => array(
            'ES_PASSWORD',
        ),
    )
);
echo HtmlUtils::render_readonly_setting(
    'Primary Index',
    array(
        'const' => array(
            'ES_INDEX',
        ),
        'env'   => array(
            'ES_INDEX',
        ),
    )
);
echo HtmlUtils::render_readonly_setting(
    'Secondary Index',
    array(
        'const' => array(
            'ES_SECONDARY_INDEX',
        ),
        'env'   => array(
            'ES_SECONDARY_INDEX',
        ),
    )
);
echo HtmlUtils::render_readonly_setting(
    'Private Primary Index',
    array(
        'const' => array(
            'ES_PRIVATE_SECONDARY_INDEX',
        ),
        'env'   => array(
            'ES_PRIVATE_SECONDARY_INDEX',
        ),
    )
);
echo HtmlUtils::render_readonly_setting(
    'Private Secondary Index',
    array(
        'const' => array(
            'ES_PRIVATE_SECONDARY_INDEX',
        ),
        'env'   => array(
            'ES_PRIVATE_SECONDARY_INDEX',
        ),
    )
);
echo HtmlUtils::render_field(
    'Read Timeout',
    'acf_elasticsearch_read_timeout',
    array(
        'class'       => 'short',
        'placeholder' => '',
        'value'       => 30,
    )
);
echo HtmlUtils::render_field(
    'Write Timeout',
    'acf_elasticsearch_write_timeout',
    array(
        'class'       => 'short',
        'placeholder' => '',
        'value'       => 30,
    )
);
echo HtmlUtils::render_post_type_choices(
    'Post Types'
);
echo HtmlUtils::render_field(
    'Capability',
    'acf_elasticsearch_capability',
    array(
        'class'       => '',
        'placeholder' => 'Capability which allows private searching',
    )
);
echo HtmlUtils::render_field(
    'Search Fields',
    'acf_elasticsearch_search_fields',
    array(
        'type'        => 'textarea',
        'class'       => 'medium',
        'value'       => SettingsHelper::get_search_fields_data(),
        'placeholder' => '',
    )
);
echo HtmlUtils::render_field(
    'Weightings',
    'acf_elasticsearch_weightings',
    array(
        'type'        => 'textarea',
        'class'       => 'medium',
        'value'       => SettingsHelper::get_weightings_data(),
        'placeholder' => '',
    )
);
echo HtmlUtils::render_field(
    'Slugs to exclude',
    'acf_elasticsearch_slugs_to_exclude',
    array(
        'type'        => 'textarea',
        'class'       => 'medium',
        'value'       => SettingsHelper::get_exclusion_slugs_data(),
        'placeholder' => '',
    )
);
echo HtmlUtils::render_field(
    'Exclusion field name',
    'acf_elasticsearch_exclusion_field',
    array(
        'class'       => '',
        'placeholder' => 'ACF field which when true will exclude posts from the index',
    )
);
echo HtmlUtils::render_field(
    'Fuzziness',
    'acf_elasticsearch_fuzziness',
    array(
        'class'       => 'short',
        'placeholder' => '',
        'value'       => 1,
    )
);
echo HtmlUtils::render_buttons([
    array(
        'value' => 'Save',
        'name'  => 'acf_elasticsearch_save_button',
        'class' => 'button-primary',
        'id'    => 'save',
    ),
]);
?>
                        <span id="config-spinner" class="acf-spinner"></span>
                        <span id="config-messages"></span>
                    </div>
                </div>
            </div>
            <div id="mapping-container" class="postbox">
                <h2 class="handle"><span>2. Setup server mappings</span></h2>
                <div class="inside acf-fields -left">
                    <div class="acf-elasticsearch-container">
                        <?php
echo HtmlUtils::render_buttons([
    array(
        'value' => 'Create mappings',
        'name'  => 'acf_elasticsearch_create_mappings_button',
        'class' => 'button',
        'id'    => 'create-mappings',
    ),
]);
?>
                        <span id="mapping-spinner" class="acf-spinner"></span>
                        <span id="mapping-messages"></span>
                    </div>
                </div>
            </div>
            <div id="indexing-container" class="postbox">
                <h2 class="handle"><span>3. Index the data</span></h2>
                <div class="inside acf-fields -left">
                    <div class="acf-elasticsearch-container">
                        <?php
echo HtmlUtils::render_buttons([
    array(
        'value' => 'Index posts',
        'name'  => 'acf_elasticsearch_index_posts_button',
        'class' => 'button',
        'id'    => 'index-posts',
    ),
    array(
        'value' => 'Resume indexing posts',
        'name'  => 'acf_elasticsearch_resume_indexing_posts_button',
        'class' => 'button',
        'id'    => 'resume-indexing-posts',
    ),
    array(
        'value' => 'Index taxonomies',
        'name'  => 'acf_elasticsearch_index_taxonomies_button',
        'class' => 'button',
        'id'    => 'index-taxonomies',
    ),
    array(
        'value' => 'Clear index',
        'name'  => 'acf_elasticsearch_clear_index_button',
        'class' => 'button',
        'id'    => 'clear-index',
    ),
]);
?>
                        <span id="indexing-spinner" class="acf-spinner"></span>
                        <span id="indexing-messages"></span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>