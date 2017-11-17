<div class="wrap">
<?php
    use makeandship\elasticsearch\admin\HtmlUtils;

if (!empty($_POST)) {
        
        // save incoming options
    $server = esc_url($_POST['acf_elasticsearch_server']);
    $primary_index = trim($_POST['acf_elasticsearch_primary_index']);
    $secondary_index = trim($_POST['acf_elasticsearch_secondary_index']);
    $read_timeout = intval(trim($_POST['acf_elasticsearch_read_timeout']));
    $write_timeout = intval(trim($_POST['acf_elasticsearch_write_timeout']));
    $username = trim($_POST['acf_elasticsearch_username']);
    $password = trim($_POST['acf_elasticsearch_password']);

    if (is_multisite()) { //  && is_plugin_active_for_network(plugin_basename(__FILE__))
        error_log('save multisite');
        // store at network level
        update_site_option('acf_elasticsearch_server', $server);
        update_site_option('acf_elasticsearch_primary_index', $primary_index);
        update_site_option('acf_elasticsearch_secondary_index', $secondary_index);
        update_site_option('acf_elasticsearch_read_timeout', $read_timeout);
        update_site_option('acf_elasticsearch_write_timeout', $write_timeout);
        update_site_option('acf_elasticsearch_username', $username);
        update_site_option('acf_elasticsearch_password', $password);
    } else {
        // store at site level
        update_option('acf_elasticsearch_server', $server);
        update_option('acf_elasticsearch_primary_index', $primary_index);
        update_option('acf_elasticsearch_secondary_index', $secondary_index);
        update_option('acf_elasticsearch_read_timeout', $read_timeout);
        update_option('acf_elasticsearch_write_timeout', $write_timeout);
        update_option('acf_elasticsearch_username', $username);
        update_option('acf_elasticsearch_password', $password);
    }
}
?>
<h1>ACF Elasticsearch</h1>
	<div id="poststuff">
		<form method="post" action="">
			<div class="postbox">
				<h2 class="hndle"><span>1. Configure your Elastic search server</span></h2>
				<div class="inside acf-fields -left">
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
                    echo HtmlUtils::render_buttons([
                        array(
                            'value' => 'Save',
                            'name' => 'acf_elasticsearch_save_button',
                            'class' => 'button-primary',
                            'id' => 'save'
                        )
                    ]);
                    ?>
				</div>
			</div>
			<div class="postbox">
				<h2><span>2. Setup server mappings</span></h2>
				<div class="inside acf-fields -left">
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
				</div>
			</div>
			<div class="postbox">
				<h2><span>3. Index the data</span></h2>
				<div class="inside acf-fields -left">
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
				</div>
			</div>
	 	</form>
	</div>
</div>
