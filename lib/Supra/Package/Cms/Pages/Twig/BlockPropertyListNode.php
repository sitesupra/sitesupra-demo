<?php

namespace Supra\Package\Cms\Pages\Twig;

use Twig_Node_Expression_Constant as ConstantNode;
use Twig_Node_Expression_Array as ArrayNode;

class BlockPropertyListNode extends BlockPropertySetNode
{
    /**
     * @var array|null
     */
    private $options;

    /**
     * {@inheritDoc}
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->raw(
            '$this->env->getExtension(\'supraPage\')->getPropertyValue(\'' . $this->getPropertyNameValue() . "')"
        );
    }

    /**
     * @return string
     */
    public function getPropertyNameValue()
    {
        $options = $this->getOptions();

        if (isset($options['name'])) {
            return $options['name'];
        }

        // if name isn't set, we will generate it automatically
        return $this->getListItemNode()->getPropertyNameValue() . 'List';
    }

    /**
     * @return BlockPropertyNode
     * @throws \UnexpectedValueException if list item definition node not fount.
     */
    public function getListItemNode()
    {
        foreach ($this->getNode('arguments') as $node) {
            if ($node instanceof BlockPropertyNode) {
                return $node;
            }
        }

        throw new \UnexpectedValueException('List item definition node not found.');
    }

    /**
     * @return string|null
     */
    public function getLabelValue()
    {
        $options = $this->getOptions();

        return isset($options['label']) ? $options['label'] : null;
    }

    /**
     * @return array
     */
    private function getOptions()
    {
        if ($this->options === null) {

            $this->options = array();

            foreach ($this->getNode('arguments') as $argumentNode) {

                if ($argumentNode instanceof ConstantNode) {
                    $this->options['name'] = $argumentNode->getAttribute('value');
                    break;

                } elseif ($argumentNode instanceof ArrayNode) {

                    foreach ($argumentNode->getKeyValuePairs() as $pair) {

                        if (! $pair['key'] instanceof ConstantNode
                            || !$pair['value'] instanceof ConstantNode) {

                            continue;
                        }

                        $this->options[$pair['key']->getAttribute('value')] = $pair['value']->getAttribute('value');
                    }

                    break;
                }
            }
        }

        return $this->options;
    }
}