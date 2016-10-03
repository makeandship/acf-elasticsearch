<?php
?>
<div class="wrap">
<?php screen_icon();?>
<form id="acf_elasticsearch_options" action="options.php" method="post">
<?php
	do_settings_sections( 'acf_elasticsearch_settings_page' );
	submit_button('Save', 'primary', 'acf_elasticsearch_options_submit');

	do_settings_sections( 'acf_elasticsearch_mappings_page' );
	submit_button('Create Mappings', '', 'acf_elasticsearch_mappings_submit');

	do_settings_sections( 'acf_elasticsearch_index_page' ); ?>
	<div class="acf-elasticsearch-button-container">
	<?php
	submit_button('Index Posts', '', 'acf_elasticsearch_index_submit');
	submit_button('Clear Index', '', 'acf_elasticsearch_clear_index_submit');
	?>
	</div>
	<?php
?>
 </form>
</div>