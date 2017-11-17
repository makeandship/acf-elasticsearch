<?php

namespace makeandship\elasticsearch\queries;

class ESQueryBuilder {	
    private $query;
    
    public function __construct()
    {
        $this->query = "";
    }

    public function query($fields)
    {
        // implement fields query
        return $this;
    }

    public function fuzziness($value)
    {
        // add fuzziness
        return $this;
    }

    public function limit($value)
    {
        // add limit
        return $this;
    }

    public function sort($field)
    {
        // add sort
        return $this;
    }
}