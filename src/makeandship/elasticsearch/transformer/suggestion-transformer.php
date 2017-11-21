<?php

namespace makeandship\elasticsearch\transformer;

class SuggestionTransformer {	
	public function transform($response, $fields)
    {
        $results = array();
        $total = $response->getTotalHits();

        $hits = $response->getResults();

        if ($total > 0 && count($hits) > 0) {
            foreach ($hits as $item) {
                $hit = $item->getHit();
                $hit_fields = $hit['_source'];

                $id = $item->getId();

                $result = array(
                    'id' => $id
                );

                foreach ($fields as $field) {
                    $item = $hit_fields[$field];
                    if (is_array($item) && count($item) == 1) {
                        $item = $item[0];
                    }
                    $result[$field] = $item;
                }

                $results[] = $result;
            }
        }

        return array(
            'total' => $total,
            'results' => $results
        );
    }
}