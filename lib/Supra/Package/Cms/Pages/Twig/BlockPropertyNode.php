<?php

namespace Supra\Package\Cms\Pages\Twig;

class BlockPropertyNode extends \Twig_Node_Expression_Function
{
	public function compile(\Twig_Compiler $compiler)
	{
		$arguments = $this->getNode('arguments');

        if (($count = $arguments->count()) > 0) {

			$arguments = iterator_to_array($arguments->getIterator());

			$compiler->raw('$this->env->getExtension(\'supraPage\')->getPropertyValue(');
			$compiler->subcompile($arguments[0]);

			if ($count === 3) {
				$compiler->raw(',');
				$compiler->subcompile($arguments[2]);
			}

			$compiler->raw(')');
		}
	}
}