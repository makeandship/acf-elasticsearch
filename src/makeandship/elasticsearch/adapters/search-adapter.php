<?php

namespace makeandship\elasticsearch\adapters;

use makeandship\elasticsearch\queries\ElasticsearchQueryBuilder;
use makeandship\elasticsearch\transformer\SearchTransformer;
use makeandship\elasticsearch\Searcher;
use makeandship\elasticsearch\Config;

class SearchAdapter {

  private $search;
  private $filters;
  private $facets;
  private $musts;
  private $fields;
  private $numeric;
  private $scored;
  private $pageIndex;
  private $size;
  private $sortByDate;

  // a constructor and initializer
  public function __construct($search, $pageIndex, $size, $facets, $sortByDate)
  {
    $this->search = str_ireplace(array(' and ', ' or '), array(' AND ', ' OR '), $search);
    $this->facets = $facets;
    $this->pageIndex = $pageIndex;
    $this->size = $size;
    $this->sortByDate = $sortByDate;
    $this->initialise();
  }

  private function initialise()
  {
    // init variables
    $this->fields = Config::fields();
    $this->musts = array();
    $this->filters = array();
    $this->numeric = Config::option('numeric');
    $this->scored = array();
    $exclude = Config::apply_filters('searcher_query_exclude_fields', array('post_date'));

    foreach (Config::taxonomies() as $tax) {
      if ($this->search) {
        $score = Config::score('tax', $tax);

          if ($score > 0) {
            $scored[] = "{$tax}_name^$score";
          }
      }

      $this->filterBySelectedFacets($tax, 'term');
    }

    $this->searchField($this->fields, 'field', $exclude);
    $this->searchField(Config::meta_fields(), 'meta', $exclude);

    if (count($this->scored) > 0 && $this->search) {
      $qs = array(
        'fields' => $this->scored,
        'query' => $this->search
      );
      $fuzzy = Config::option('fuzzy');
      if ($fuzzy && strpos($this->search, "~") > -1) {
        $qs['fuzzy_min_sim'] = $this->fuzzy;
      }
      $qs = Config::apply_filters('searcher_query_string', $qs);
      $this->musts[] = array('query_string' => $qs);
    }

    if (in_array('post_type', $this->fields)) {
      $this->filterBySelectedFacets('post_type', 'term');
    }

    $this->searchField(Config::customFacets(), 'custom', $exclude);
  }

  private function filterBySelectedFacets($name, $type, $translate = array())
  {
    if (isset($this->facets[$name])) {
      $this->facets = $this->facets[$name];

      if (!is_array($this->facets)) {
        $this->facets = array($this->facets);
      }

      foreach ($this->facets as $operation => $facet) {
        if (is_string($operation) && $operation == 'or') {
          // use filters so faceting isn't affecting, allowing the user to select more "or" options
          $output = $this->filters;
        } else {
          $output = $this->musts;
        }

        if (is_array($facet)) {
          foreach ($facet as $value) {
            $output[] = array($type => array($name => isset($translate[$value]) ? $translate[$value] : $value));
          }

          continue;
        }

        $output[] = array($type => array($name => isset($translate[$facet]) ? $translate[$facet] : $facet));
      }
    }
  }

  private function searchField($fields, $type, $exclude)
  {
    foreach ($fields as $field) {
      if (in_array($field, $exclude)) {
        continue;
      }

      if ($this->search) {
        $score = Config::score($type, $field);
        $notanalyzed = Config::option('not_analyzed');

        if ($score > 0) {
          if (strpos($this->search, "~") > -1 || isset($notanalyzed[$field])) {
            // TODO: fuzzy doesn't work with english analyzer
            $this->scored[] = "$field^$score";
          } else {
            $this->scored[] = sprintf("$field^$score");
          }
        }
      }

      if (isset($this->numeric[$field]) && $this->numeric[$field]) {
        $ranges = Config::ranges($field);

        if (count($ranges) > 0) {
          $transformed = array();

          foreach ($ranges as $key => $range) {
            $transformed[$key] = array();

              if (isset($range['to'])) {
                $transformed[$key]['lt'] = $range['to'];
              }

              if (isset($range['from'])) {
                $transformed[$key]['gte'] = $range['from'];
              }
          }

          $this->filterBySelectedFacets($field, 'range', $transformed);
        }
      } elseif ($type == 'custom') {
          $this->filterBySelectedFacets($field, 'term');
      }
    }
  }

  public function search() 
  {
    $searcher = new Searcher();
    $transformer = new SearchTransformer();
    $args = new ElasticsearchQueryBuilder();

    $args = $args->query($this->filters, $this->musts)
                 ->field_aggs('post_type', $this->fields)
                 ->aggs($this->filters)
                 ->numeric_aggs($this->numeric)
                 ->filter()
                 ->from($this->pageIndex)
                 ->size($this->size)
                 ->sort($this->sortByDate);

    $query = Config::apply_filters('searcher_query_post_facet_filter', $args->getQuery());

    $response = $searcher->search($query);

    return $transformer->transform($response);
  }
}

?>