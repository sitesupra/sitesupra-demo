<?php

namespace Supra\Package\Cms\Pages\Twig;

use Twig_Node_Expression_Array as ArrayNode;
use Twig_Node_Expression_Constant as ConstantNode;

abstract class AbstractPropertyFunctionNode extends \Twig_Node_Expression_Function
{
    /**
     * @return null|\Twig_Node
     */
    public function getOptionsArgumentNode()
    {
        return $this->getNode('arguments')->hasNode(0)
            ? $this->getNode('arguments')->getNode(0) : null;
    }

    /**
     * @return string
     */
    public function getNameOptionValue()
    {
        $options = $this->getOptions();

        if (! isset($options['name'])) {
            throw new \RuntimeException('Name option is not set.');
        }

        if (! $options['name'] instanceof ConstantNode) {
            throw new \RuntimeException('Expecting name option to be constant expression.');
        }

        return $options['name']->getAttribute('value');
    }

    /**
     * @return array
     */
    abstract public function getOptions();

    /**
     * @return string
     */
    abstract function getType();

    /**
     * @param ArrayNode $node
     * @return array
     */
    protected function nodeToArray(ArrayNode $node)
    {
        $array = array();

        foreach ($node->getKeyValuePairs() as $pair) {

            if (! $pair['key'] instanceof ConstantNode) {
                continue;
            }

            $array[ $pair['key']->getAttribute('value') ] = $pair['value'];
        }

        return $array;
    }
}