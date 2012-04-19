<?php

namespace Supra\Search\Solarium;

use Supra\Search\Exception;

class TermVectorComponentResponseParser
{

    private function crawlData($data)
    {
        $result = array();

        while ( ! empty($data)) {

            $name = array_shift($data);

            $value = array_shift($data);

            if (is_array($value)) {
                $value = $this->crawlData($value);
            }

            $result[$name] = $value;
        }

        return $result;
    }

    public function parse($query, $component, $data)
    {
        $results = array();

        if (empty($data['termVectors'])) {
            throw new Exception\RuntimeException('No termVectors key in data');
        }

        $termVectors = $this->crawlData($data['termVectors']);

        if (isset($termVectors['warnings'])) {
            unset($termVectors['warnings']);
        }

        if (isset($termVectors['uniqueKeyFieldName']))
            unset($termVectors['uniqueKeyFieldName']);

        foreach ($termVectors as $docData) {
            $results[$docData['uniqueKey']] = $docData;
        }

        //\Log::debug('TTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTT: ', $termVectors);

        return new TermVectorSelectResult($results);
    }

    static function CN()
    {
        return get_called_class();
    }

}
