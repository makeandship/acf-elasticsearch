jQuery(document).ready(function($) {
	// endpoint URLs
	var CREATE_MAPPINGS_URI = '';
	var INDEX_POSTS_URI = '';
	var CLEAR_INDEX_URI = '';

	$('#create-mappings').click(function(e) {
		console.log('create mappings');
		return false;
	});

	$('#index-posts').click(function(e) {
		console.log('index posts');
		return false;
	});

	$('#clear-index').click(function(e) {
		console.log('clear index');
		return false;
	});
});