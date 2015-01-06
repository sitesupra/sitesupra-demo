<?php

namespace Supra\Package\Cms\Pages\Twig;

class BlockPropertyListNode extends BlockPropertySetNode
{
    /**
     * {@inheritDoc}
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->raw(
            '$this->env->getExtension(\'supraPage\')->getPropertyValue(\'' . $this->getPropertyName() . "')"
        );
    }

    /**
     * @return string
     */
    public function getPropertyName()
    {
        $arguments = iterator_to_array($this->getNode('arguments'));

        if (! empty($arguments)) {

            if ($arguments[0] instanceof \Twig_Node_Expression_Constant) {
                return $arguments[0]->getAttribute('value');

            } elseif ($arguments[0] instanceof BlockPropertyNode) {
                return $arguments[0]->getPropertyName() . 'List';

            } else {
                throw new \UnexpectedValueException(sprintf(
                    'Collection can have only name and/or property definition arguments, [%s] received.',
                    get_class($arguments[0])
                ));
            }
        }

        // @TODO: correct lame error messages

        throw new \UnexpectedValueException('Failed to obtain list name.');
    }

    public function getListItemNode()
    {
        $arguments = iterator_to_array($this->getNode('arguments'));

        foreach ($arguments as $argument) {
            if ($argument instanceof BlockPropertyNode) {
                return $argument;
            }
        }

        throw new \UnexpectedValueException('List is missing for property definition.');
    }
}