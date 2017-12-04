<?php

namespace makeandship\elasticsearch\transformer;

use makeandship\elasticsearch\Util;

class SearchTransformer
{
    public function transform($response)
    {
        $val = array(
            'total' => $response->getTotalHits(),
            'facets' => array(),
            'ids' => array(),
            'results' => array()
        );

        foreach ($response->getAggregations() as $name => $agg) {
            if (isset($agg['facet']['buckets'])) {
                foreach ($agg['facet']['buckets'] as $bucket) {
                    $val['facets'][$name][$bucket['key']] = $bucket['doc_count'];
                }
            }

            if (isset($agg['range']['buckets'])) {
                foreach ($agg['range']['buckets'] as $bucket) {
                    $from = isset($bucket['from']) ? $bucket['from'] : '';
                    $to = isset($bucket['to']) && $bucket['to'] != '*' ? $bucket['to'] : '';

                    $val['facets'][$name][$from . '-' . $to] = $bucket['doc_count'];
                }
            }
        }

        foreach ($response->getResults() as $result) {
            $id = $result->getId();
            $source = $result->getSource();
            $hit = $result->getHit();
            $source['id'] = $id;
            
            $highlight = Util::safely_get_attribute($hit, 'highlight');
            if ($highlight) {
                $source['highlight'] = $highlight;
            }
            
            $val['ids'][] = $id;
            $val['results'][] = $source;
        }

        return Util::apply_filters('searcher_results', $val, $response);
    }
}
