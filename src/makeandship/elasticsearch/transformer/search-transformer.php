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
            $aggs = $this->transform_aggs($name, $agg);

            $val['facets'] = array_merge($val['facets'], $aggs);
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

    private function transform_aggs($name, $agg)
    {
        $result = array();

        if ($name && $agg) {
            // if it looks like an aggregate, extract buckets
            if (
                array_key_exists('doc_count_error_upper_bound', $agg) &&
                array_key_exists('sum_other_doc_count', $agg) &&
                array_key_exists('buckets', $agg)
            ) {
                $buckets = array();
                if (isset($agg['buckets'])) {
                    foreach ($agg['buckets'] as $bucket) {
                        $buckets[$bucket['key']] = $bucket['doc_count'];
                    }
                }
                $result[$name] = $buckets;
            } else {
                // otherwise check each attribute for sub-aggregates, ranges or facets
                foreach ($agg as $key => $value) {
                    switch ($key) {
                        case 'facet':
                            // looks like a normal aggregation - process it
                            $result = array_merge($result, $this->transform_aggs($name, $value));
                            break;
                        case 'range':
                            $buckets = array();
                            foreach ($value['buckets'] as $bucket) {
                                $from = Util::safely_get_attribute($bucket, 'from');
                                $to = Util::safely_get_attribute($bucket, 'to');

                                $to = $to === '*' ? null : $to;

                                $buckets[$from . '-' . $to] = $bucket['doc_count'];
                            }
                            $result[$name] = $buckets;
                            break;
                        default:
                            // extract sub-aggregation into a key of e.g. parent.child
                            if ($value && is_array($value) && count($value) > 0) {
                                $maybe_name = implode(".", array($name, $key));
                                $maybe_an_agg = $this->transform_aggs($maybe_name, $value);

                                if ($maybe_an_agg) {
                                    $result = array_merge($result, $maybe_an_agg);
                                }
                            }
                            break;
                    }
                }
            }
        }

        return $result;
    }
}
