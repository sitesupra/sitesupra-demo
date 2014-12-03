<?php

namespace Supra\Package\Cms\Pages\Twig;

class BlockPropertyValueTestNode extends \Twig_Node_Expression_Function
{
	public function compile(\Twig_Compiler $compiler)
	{
		$arguments = $this->getNode('arguments');

        if ($arguments->count() === 1) {
			$compiler->raw('$this->env->getExtension(\'supraPage\')->isPropertyValueEmpty(');
			$compiler->subcompile($arguments->getIterator()->current());
			$compiler->raw(')');
		}
	}
}