<?php

namespace makeandship\elasticsearch\wp;

use makeandship\elasticsearch\adapters\SearchAdapter;

class AcfElasticsearchHelper {
  public function search($search = '', $pageIndex = 0, $size = 10, $facets = array(), $sortByDate = false) {
    $adapter = new SearchAdapter($search, $pageIndex, $size, $facets, $sortByDate);
    return $adapter->search();
  }
}

?>