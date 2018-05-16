<?php

require_once(__DIR__ . '/../../../acf-elasticsearch-autoloader.php');

use makeandship\elasticsearch\queries\QueryBuilder;

class QueryBuilderTest extends WP_UnitTestCase
{

    public function testSearchQuery()
    {
        $query = new QueryBuilder();
        $query = $query->freetext('keyword')
            ->with_fuzziness(1)
            ->weighted(array(
                'post_title' => 3,
                'post_content' => 3
            ))
            ->for_categories([])
            ->with_category_counts(array('category' => 5))
            ->paged(0, 10)
            ->to_array();

        $this->assertEquals($query['query']['bool']['must']['multi_match']['query'], 'keyword');
        $this->assertEquals($query['query']['bool']['must']['multi_match']['fuzziness'], 1);
        $this->assertEquals($query['query']['bool']['must']['multi_match']['fields'][0], '_all');
        $this->assertEquals($query['query']['bool']['must']['multi_match']['fields'][1], 'post_title^3');
        $this->assertEquals($query['query']['bool']['must']['multi_match']['fields'][2], 'post_content^3');
        $this->assertEquals($query['aggs']['category']['aggs']['facet']['terms']['field'], 'category');
        $this->assertEquals($query['aggs']['category']['aggs']['facet']['terms']['size'], 5);
        $this->assertEquals($query['from'], 0);
        $this->assertEquals($query['size'], 10);
    }

    public function testTaxonomySuggestionQuery()
    {
        $query = new QueryBuilder();
        $query = $query->freetext("Ace")
            ->for_categories([])
            ->for_taxonomies(array('category'))
            ->for_post_types(null)
            ->paged(0, 10)
            ->searching(array('name'))
            ->returning(array('name', 'title'))
            ->with_fuzziness(1)
            ->to_array();

        $this->assertEquals($query['query']['bool']['must']['multi_match']['query'], 'Ace');
        $this->assertEquals($query['query']['bool']['must']['multi_match']['fuzziness'], 1);
        $this->assertEquals($query['query']['bool']['must']['multi_match']['fields'][0], 'name');
        $this->assertEquals($query['aggs']['post_type']['aggs']['facet']['terms']['field'], 'post_type');
        $this->assertEquals($query['from'], 0);
        $this->assertEquals($query['size'], 10);
        $this->assertEquals($query['_source'][0], 'name');
        $this->assertEquals($query['_source'][1], 'title');
    }

    public function testPostTypeSuggestionQuery()
    {
        $query = new QueryBuilder();
        $query = $query->freetext("Ace")
            ->for_categories([])
            ->for_post_types(['article'])
            ->paged(0, 10)
            ->searching(array('name'))
            ->returning(array('name', 'title'))
            ->with_fuzziness(1)
            ->to_array();
        
        $this->assertEquals($query['query']['bool']['must']['multi_match']['query'], 'Ace');
        $this->assertEquals($query['query']['bool']['must']['multi_match']['fuzziness'], 1);
        $this->assertEquals($query['query']['bool']['must']['multi_match']['fields'][0], 'name');
        $this->assertEquals($query['query']['bool']['filter']['bool']['should'][0]['term']['type'], 'article');
        $this->assertEquals($query['aggs']['post_type']['aggs']['facet']['terms']['field'], 'post_type');
        $this->assertEquals($query['aggs']['post_type']['aggs']['facet']['terms']['size'], 1);
        $this->assertEquals($query['from'], 0);
        $this->assertEquals($query['size'], 10);
        $this->assertEquals($query['_source'][0], 'name');
        $this->assertEquals($query['_source'][1], 'title');
    }

    public function testSearchQueryWithEmptyText()
    {
        $query = new QueryBuilder();
        $query = $query->freetext('')
            ->with_fuzziness(1)
            ->weighted(array(
                'post_title' => 3,
                'post_content' => 3
            ))
            ->for_categories([])
            ->with_category_counts(array('category' => 5))
            ->paged(0, 10)
            ->to_array();

        $this->assertEquals($query['query']['bool']['must']['match_all'], (object) array());
        $this->assertEquals($query['aggs']['category']['aggs']['facet']['terms']['field'], 'category');
        $this->assertEquals($query['aggs']['category']['aggs']['facet']['terms']['size'], 5);
        $this->assertEquals($query['from'], 0);
        $this->assertEquals($query['size'], 10);
    }

}