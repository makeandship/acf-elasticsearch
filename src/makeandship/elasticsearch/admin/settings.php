<div class="wrap">
<?php
use makeandship\elasticsearch\admin\HtmlUtils;
use makeandship\elasticsearch\Defaults;
use makeandship\elasticsearch\Constants;
use makeandship\elasticsearch\settings\SettingsManager;
use makeandship\elasticsearch\settings\SettingsHelper;

if (!empty($_POST)) {
        
    // save incoming options
    $server = esc_url($_POST['acf_elasticsearch_server']);
    $primary_index = trim($_POST['acf_elasticsearch_primary_index']);
    $secondary_index = trim($_POST['acf_elasticsearch_secondary_index']);
    $private_primary_index = trim($_POST['acf_elasticsearch_private_primary_index']);
    $private_secondary_index = trim($_POST['acf_elasticsearch_private_secondary_index']);
    $read_timeout = intval(trim($_POST['acf_elasticsearch_read_timeout']));
    $write_timeout = intval(trim($_POST['acf_elasticsearch_write_timeout']));
    $username = trim($_POST['acf_elasticsearch_username']);
    $password = trim($_POST['acf_elasticsearch_password']);
    $post_types = HtmlUtils::create_post_types();
    $capability = trim($_POST['acf_elasticsearch_capability']);
    $search_fields = HtmlUtils::create_search_fields();
    $weightings = HtmlUtils::create_weightings();
    $fuzziness = intval(trim($_POST['acf_elasticsearch_fuzziness']));

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
}
?>
<h1>ACF Elasticsearch</h1>
	<div id="poststuff">
		<form method="post" action="">
			<div id="config-container" class="postbox">
				<h2 class="handle"><span>1. Configure your Elastic search server</span></h2>
				<div class="inside acf-fields -left">
                    <div class="acf-elasticsearch-container">
                        <?php 
                        echo HtmlUtils::render_field(
                            'Server',
                            'acf_elasticsearch_server',
                            array(
                                'class' => '',
                                'placeholder' => 'e.g. http://www.yourserver.com:9200/'
                            )
                        );
                        echo HtmlUtils::render_field(
                            'Primary Index',
                            'acf_elasticsearch_primary_index',
                            array(
                                'class' => '',
                                'placeholder' => ''
                            )
                        );
                        echo HtmlUtils::render_field(
                            'Secondary Index',
                            'acf_elasticsearch_secondary_index',
                            array(
                                'class' => '',
                                'placeholder' => ''
                            )
                        );
                        echo HtmlUtils::render_field(
                            'Private Primary Index',
                            'acf_elasticsearch_private_primary_index',
                            array(
                                'class' => '',
                                'placeholder' => ''
                            )
                        );
                        echo HtmlUtils::render_field(
                            'Private Secondary Index',
                            'acf_elasticsearch_private_secondary_index',
                            array(
                                'class' => '',
                                'placeholder' => ''
                            )
                        );
                        echo HtmlUtils::render_field(
                            'Read Timeout',
                            'acf_elasticsearch_read_timeout',
                            array(
                                'class' => 'short',
                                'placeholder' => '',
                                'value' => 30
                            )
                        );
                        echo HtmlUtils::render_field(
                            'Write Timeout',
                            'acf_elasticsearch_write_timeout',
                            array(
                                'class' => 'short',
                                'placeholder' => '',
                                'value' => 30
                            )
                        );
                        echo HtmlUtils::render_field(
                            'Username',
                            'acf_elasticsearch_username',
                            array(
                                'class' => '',
                                'placeholder' => 'When using searchguard'
                            )
                        );
                        echo HtmlUtils::render_field(
                            'Password',
                            'acf_elasticsearch_password',
                            array(
                                'class' => '',
                                'placeholder' => 'When using searchguard'
                            )
                        );
                        echo HtmlUtils::render_post_type_choices(
                            'Post Types'
                        );
                        echo HtmlUtils::render_field(
                            'Capability',
                            'acf_elasticsearch_capability',
                            array(
                                'class' => '',
                                'placeholder' => 'Capability which allows private searching'
                            )
                        );
                        echo HtmlUtils::render_field(
                            'Search Fields',
                            'acf_elasticsearch_search_fields',
                            array(
                                'type' => 'textarea',
                                'class' => 'medium',
                                'value' => SettingsHelper::get_search_fields_data(),
                                'placeholder' => ''
                            )
                        );
                        echo HtmlUtils::render_field(
                            'Weightings',
                            'acf_elasticsearch_weightings',
                            array(
                                'type' => 'textarea',
                                'class' => 'medium',
                                'value' => SettingsHelper::get_weightings_data(),
                                'placeholder' => ''
                            )
                        );
                        echo HtmlUtils::render_field(
                            'Fuzziness',
                            'acf_elasticsearch_fuzziness',
                            array(
                                'class' => 'short',
                                'placeholder' => '',
                                'value' => 1
                            )
                        );
                        echo HtmlUtils::render_buttons([
                            array(
                                'value' => 'Save',
                                'name' => 'acf_elasticsearch_save_button',
                                'class' => 'button-primary',
                                'id' => 'save'
                            )
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
                                'value'=>'Create mappings',
                                'name' => 'acf_elasticsearch_create_mappings_button',
                                'class' => 'button',
                                'id' => 'create-mappings'
                            )
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
                                'name' => 'acf_elasticsearch_index_posts_button',
                                'class' => 'button',
                                'id' => 'index-posts'
                            ),
                            array(
                                'value' => 'Resume indexing posts',
                                'name' => 'acf_elasticsearch_resume_indexing_posts_button',
                                'class' => 'button',
                                'id' => 'resume-indexing-posts'
                            ),
                            array(
                                'value' => 'Index taxonomies',
                                'name' => 'acf_elasticsearch_index_taxonomies_button',
                                'class' => 'button',
                                'id' => 'index-taxonomies'
                            ),
                            array(
                                'value' => 'Clear index',
                                'name' => 'acf_elasticsearch_clear_index_button',
                                'class' => 'button',
                                'id' => 'clear-index'
                            )
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
