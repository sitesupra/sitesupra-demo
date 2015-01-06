<?php

namespace Supra\Package\Cms\Pages\Twig;

class BlockPropertySetNode extends BlockPropertyNode
{
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->raw('$this->env->getExtension(\'supraPage\')->getPropertyValue(\'' . $this->getPropertyName() . "')");
    }

    /**
     * @return string
     */
    public function getPropertyName()
    {
        $arguments = iterator_to_array($this->getNode('arguments'));

        if ($arguments[0] instanceof \Twig_Node_Expression_Constant) {
            return $arguments[0]->getAttribute('value');
        }

        $nameParts = array();

        foreach ($arguments as $argument) {
            if ($argument instanceof BlockPropertyNode) {
                $nameParts[] = ucfirst($argument->getPropertyName());
            }
        }

        return implode($nameParts) . 'Set';
    }
}