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

Elasticsearch settings can be configured in constants in your wp-config.php or 
using environment variables.

The following should be set

* ES_URL - The URL to your elasticsearch e.g. https://elasticsearch.example.com:9200/
* ES_USERNAME - Any username required to connect to elasticsearch
* ES_PASSWORD - Any password required to connect to elasticsearch
* ES_INDEX - An index name for the primary index
* ES_SECONDARY_INDEX - An index name for the secondary index
* ES_PRIVATE_INDEX - An index name for the private primary index
* ES_PRIVATE_SECONDARY_INDEX - An index name for the private secondary index

The variables can also be set as individual files which are pointed to by a variable 
of the same name with suffix `_FILE` e.g. `ES_PASSWORD_FILE` to support secret
injection via a vault.

In the wordpress admin console, go to Settings->ACF Elasticsearch to configure
the plugin:

Read/Write timeouts are configured in the Read Timeout and Write Timeout
fields respectively.

Check the list of post types to be indexed and mapped, by default all post
types are checked.

For each selected post type you can exclude fields from indexing by
entering them in the "Exclude fields from indexing" textarea, fields are
separated by new line.

For each selected post type you can indicate private fields to be added to
the private index only in the "Fields for private searches only" textarea,
fields are separated by new line.

Enter the capability name which grants access to the private indexes in
the capability field, if the user role has this capability the search will be
against private indexes only. Make sure the capability is added to your
wordpress config and granted to the right roles.

Enter the list of searched fields in the Search Fields textarea, fields
are separated by new line.

Enter the list of weightings in the Weightings textarea, fields are
separated by new line and each line has a format of field^weight e.g.
post_content^3.

Enter the fuzziness value in the Fuzziness field e.g 1.

## Implementation in the theme

1. Disable wp search

You need to disable default wordpress search which slows down your site, so use
the following hook in your theme function:

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

$searcher = new Searcher();

// search the first 10 posts with keyword foo
$query = new QueryBuilder();
$query = $query->freetext('foo')
    ->with_fuzziness()
    ->weighted()
    ->with_category_counts(array('category' => 10))
    ->paged(0, 10);

$result = $searcher->search($query->to_array());
```

2.2. Taxonomies search:

```
use makeandship\elasticsearch\queries\QueryBuilder;
use makeandship\elasticsearch\Searcher;

$searcher = new Searcher();

// search the first 10 taxonomies with keyword foo
$query = new QueryBuilder();
$query = $query->freetext('foo')
    ->for_taxonomies([....])
    ->paged(0, 10);

$result = $searcher->search($query->to_array());
```

2.3 Specific fields search:

```
use makeandship\elasticsearch\queries\QueryBuilder;
use makeandship\elasticsearch\Searcher;

$searcher = new Searcher();

// search only in post_title and post_content and return id, post_title and post_content
$query = new QueryBuilder();
$query = $query->freetext('foo')
    ->paged(0, 10)
    ->searching(['post_title', 'post_content'])
    ->returning(['id', 'post_title', 'post_content']);

$result = $searcher->search($query->to_array());
```
