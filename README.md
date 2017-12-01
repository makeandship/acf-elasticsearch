# acf-elasticsearch

Improve wordpress search performance/accuracy and enable secure search against
ElasticSearch server with searchguard plugin.

## Installation

1. Upload plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Click the 'ElasticSearch' menu item and follow the instructions on each
   section to configure the plugin. (be sure to save on each section)
4. If you are using searchguard, define your private username and password.
5. Select "Save" on "Server Settings" when you are ready for it to go live.

## Tests

1. Install phpunit

```
wget https://phar.phpunit.de/phpunit-6.2.phar
chmod +x phpunit-6.2.phar
sudo mv phpunit-6.2.phar /usr/local/bin/phpunit
```

2. Create mysql test user

```
mysql -u root
CREATE USER 'es_plugin_test'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON *.* TO 'es_plugin_test'@'localhost';
```

3. Setup the test environment (Run it after each system restart)

```
bash bin/install-wp-tests.sh wordpress_test es_plugin_test 'password' 127.0.0.1 latest
```

4. Start local elasticsearch server

```
brew services start elasticsearch
```

5. Run the tests

   5.1. singlesite tests

   ```
   phpunit
   ```

   5.2. multisite tests

   ```
   phpunit -c phpunit-multisite.xml
   ```
   
6. Configuration

In the wordpress admin console, go to Settings->ACF Elasticsearch to configure the plugin:

   6.1. Enter the server address in Server field (address must be terminated by /) e.g. https://your-es-host:9200/
   
   6.2. Enter the primary index to be created by the plugin in the Primary Index field
   
   6.3. Enter the secondary index to be created by the plugin in the Secondary Index field (secondary index will be used                 when the primary is indexing)
   
   6.4. Enter the private primary index to be created by the plugin in the Private Primary Index field (private primary index will contain all the data including private objects and fields).
   
   6.5. Enter the private secondary index to be created by the plugin in the Private Secondary Index field (private secondary index will be used when the private primary is indexing).
   
   6.6. Read/Write timeouts are configured in the Read Timeout and Write Timeout fields respectively.
   
   6.7. If you are using searchguard or any other security plugin in your elasticsearch cluster, enter the username and password in the Username and Password fields.
   
   6.8. Check the list of post types to be indexed and mapped, by default all post types are checked.
   
   6.9. For each selected post type you can exclude fields from indexing by entering them in the "Exclude fields from indexing" textarea, fields are separated by new line.
   
   6.10. For each selected post type you can indicate private fields to be added to the private index only in the "Fields for private searches only" textarea, fields are separated by new line.
   
   6.11. Enter the capability name which grants access to the private indexes in the capability field, if the user role has this capability the search will be against private indexes only. Make sure the capability is added to your wordpress config and granted to the right roles.
   
   6.12. Enter the list of searched fields in the Search Fields textarea, fields are separated by new line.
   
   6.13. Enter the list of weightings in the Weightings textarea, fields are separated by new line and each line has a format of field^weight e.g. post_content^3.
   
## Implementation in the theme

1. Disable wp search

You need to disable default wordpress search which slows down your site, so use the following hook in your theme function:

```
function _cancel_query( $query ) {
 
    if ( !is_admin() && !is_feed() && is_search() ) {
        $query = false;
    }
 
    return $query;
}
 
add_action( 'posts_request', '_cancel_query' );
```

2. Examples

   2.1. Posts search:
   
```
use makeandship\elasticsearch\queries\QueryBuilder;
use makeandship\elasticsearch\Searcher;

// search the first 10 posts with keyword foo
$query = new QueryBuilder();
$query = $query->freetext('foo')
    ->with_fuzziness(1)
    ->weighted()
    ->with_category_counts(array('category' => 10))
    ->paged(0, 10);
```
   
   2.2. Taxonomies search:
 
```
use makeandship\elasticsearch\queries\QueryBuilder;
use makeandship\elasticsearch\Searcher;

// search the first 10 taxonomies with keyword foo
$query = new QueryBuilder();
$query = $query->freetext('foo')
    ->for_taxonomies([....])
    ->paged(0, 10);
```

   2.3 Specific fields search:
   
```
use makeandship\elasticsearch\queries\QueryBuilder;
use makeandship\elasticsearch\Searcher;

// search only in post_title and post_content and return id, post_title and post_content
$query = new QueryBuilder();
$query = $query->freetext('foo')
    ->paged(0, 10)
    ->searching(['post_title', 'post_content'])
    ->returning(['id', 'post_title', 'post_content']);
```
