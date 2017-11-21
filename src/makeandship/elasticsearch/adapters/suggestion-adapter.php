<?php

namespace makeandship\elasticsearch\adapters;
use makeandship\elasticsearch\queries\ElasticsearchQueryBuilder;
use makeandship\elasticsearch\transformer\SuggestionTransformer;
use makeandship\elasticsearch\Searcher;

class SuggestionAdapter {
  private $fields;
  private $field;
  private $text;
  private $categories;
  private $size;

  // a constructor and initializer
  public function __construct($args)
  {
    $this->field = $args['field'];
    $this->text = $args['text'];
    $this->categories = $args['categories'];
    $this->size = $args['size'];
    $this->fields = $args['fields'];
  }

  public function search() {
    $searcher = new Searcher();
    $transformer = new SuggestionTransformer();
    $args = new ElasticsearchQueryBuilder();

    $args = $args->match($this->fields, $this->field, $this->text)
                 ->fuzziness($this->field, 1)
                 ->filter_categories($this->categories)
                 ->from(0)
                 ->size($this->size);
    
    $response = $searcher->search($args->getQuery());
    
    return $transformer->transform($response, $this->fields);
  }
}

?>