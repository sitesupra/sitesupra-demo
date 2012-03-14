<?php

namespace Supra\Search\Solarium;

class TermVectorSelectResult implements \IteratorAggregate, \Countable
{

    /**
     * Result array
     *
     * @var array
     */
    protected $_results;

    /**
     * @param array $results
     * @return void
     */
    public function __construct($results)
    {
        $this->_results = $results;
    }

    /**
     * @param mixed $key
     * @return array|null
     */
    public function getResult($key)
    {
        if (isset($this->_results[$key])) {
            return $this->_results[$key];
        } else {
            return null;
        }
    }

    /**
     * @return array
     */
    public function getResults()
    {
        return $this->_results;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_results);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->_results);
    }

}