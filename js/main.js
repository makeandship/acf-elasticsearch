jQuery(document).ready(function($) {
	// endpoint URLs
	var CREATE_MAPPINGS_URI = '';
	var INDEX_POSTS_URI = '';
	var CLEAR_INDEX_URI = '';

	$('#create-mappings').click(function(e) {
		console.log('create mappings');
		var data = {
			'action': 'create_mappings'
		};
		adminAjax(
			data, 
			function() {
				console.log('success');
			},
			function() {
				console.log('error');
			}
		);
		return false;
	});

	$('#index-posts').click(function(e) {
		console.log('index posts');
		var data = {
			'action': 'index_posts'
		};
		adminAjax(
			data, 
			function() {
				console.log('success');
			},
			function() {
				console.log('error');
			}
		);
		return false;
	});

	$('#index-taxonomies').click(function(e) {
		console.log('index posts');
		var data = {
			'action': 'index_taxonomies'
		};
		adminAjax(
			data, 
			function() {
				console.log('success');
			},
			function() {
				console.log('error');
			}
		);
		return false;
	});

	$('#clear-index').click(function(e) {
		console.log('clear index');
		var data = {
			'action': 'clear_index'
		};
		adminAjax(
			data, 
			function() {
				console.log('success');
			},
			function() {
				console.log('error');
			}
		);
		return false;
	});

	function adminAjax(data, success, error) {
		var url = window.acfElasticsearchManager.ajaxUrl;
		$.ajax({
            url: url,
            type: 'post',
            data: data,
            success: success,
            error: error
        });
	}
});