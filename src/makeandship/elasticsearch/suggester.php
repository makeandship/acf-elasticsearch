<?php
namespace makeandship\elasticsearch;

use makeandship\elasticsearch\SettingsManager;

use \Elastica\Client;

/**
 * Returns a set of default values that are sufficient for indexing wordpress if the user does not set any values.
 *
 * @license http://opensource.org/licenses/MIT
 * @author Mark Thomsit <mark@makeandship.com>
 * @version 4.0.1
 **/
class Suggester
{
    public static function suggest($args)
    {
        $field = $args['field'];
        $text = $args['text'];
        $categories = $args['categories'];
        $size = $args['size'];
        $fields = $args['fields'];

        //$field, $text, $categories = array(), $size = 5

        $result = null;

        if (isset($text) && !empty($text)) {
            $settings_manager = new SettingsManager();
            $settings = $settings_manager->get_settings();

            $settings = Util::get_client_settings($settings);

            $client = new Client($settings);
            $name = get_option(Constants::OPTION_PRIMARY_INDEX);
            $index = $client->getIndex($name);

            $search = new \Elastica\Search($client);
            $search->addIndex($index);
            
            $query = array(
                            'query' => array(
                                'bool' => array(
                                    'must' => array(
                                        'match' => array(
                                            $field => array(
                                                'query' =>  strtolower($text),
                                                'fuzziness' => 1
                                            )
                                        )
                                       )
                                )
                            ),
                            '_source' => $fields
                        );


            if (isset($categories) && is_array($categories) && count($categories) > 0) {
                $query['query']['bool']['filter'] = array();
                foreach ($categories as $taxonomy => $filters) {
                    foreach ($filters as $operation => $filter) {
                        if (is_string($operation)) {
                            $query['query']['bool']['filter']['bool'] = array();

                            $bool_operator = $operation === 'or' ? 'should' : 'must';
                            if (!array_key_exists($bool_operator, $query['query']['filtered']['filter']['bool'])) {
                                $query['query']['bool']['filter']['bool'][$bool_operator] = array();
                            }

                            if (is_array($filter)) {
                                foreach ($filter as $value) {
                                    $query['query']['bool']['filter']['bool'][$bool_operator][] =
                                                    array(
                                                        'term' => array(
                                                            $taxonomy => $value
                                                        )
                                                    );
                                }
                            }
                        }
                    }
                }
            }
            /*
            $query = array(
                'query' => array(
                    'filtered' => array(
                        'query' => array(
                            'match' => array(
                                $field => array(
                                    'query' =>  strtolower($text),
                                    'fuzziness' => 1
                                )
                            )
                           )
                    )
                ),
                'fields' => $fields
            );

            if (isset($categories) && is_array($categories) && count($categories) > 0) {
                $query['query']['filtered']['filter'] = array();
                foreach ($categories as $taxonomy => $filters) {
                    foreach ($filters as $operation => $filter) {
                        if (is_string($operation)) {
                            $query['query']['filtered']['filter']['bool'] = array();

                            $bool_operator = $operation === 'or' ? 'should' : 'must';
                            if (!array_key_exists($bool_operator, $query['query']['filtered']['filter']['bool'])) {
                                $query['query']['filtered']['filter']['bool'][$bool_operator] = array();
                            }

                            if (is_array($filter)) {
                                foreach ($filter as $value) {
                                    $query['query']['filtered']['filter']['bool'][$bool_operator][] =
                                        array(
                                            'term' => array(
                                                $taxonomy => $value
                                            )
                                        );
                                }
                            }
                        }
                    }
                }
            }*/
            
            error_log(print_r(json_encode($query), true));
            
            $eq = new \Elastica\Query($query);
            $eq->setFrom(0);
            $eq->setSize($size);

            $response = $search->search($eq);

            try {
                $result = self::_parse_response($response, $fields);
            } catch (\Exception $ex) {
                error_log($ex);

                Config::do_action('searcher_exception', $ex);

                return self::_empty_result();
            }
        }

        error_log(print_r($result, true));
        return $result;
    }

    /**
     * Return an empty result for errors
     */
    public static function _empty_result()
    {
        return array();
    }

    /**
     * Return a valid response
     */
    public static function _parse_response($response, $fields)
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
