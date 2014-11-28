<?php

namespace Supra\Package\Cms\Pages\Twig;

class BlockPropertyNode extends \Twig_Node_Expression_Function
{
	public function compile(\Twig_Compiler $compiler)
	{
		$arguments = $this->getNode('arguments');

        if ($arguments->count() > 0) {

//			if (count($arguments) > 1) {
//				$compiler->raw('$this->env->getExtension(\'supraPage\')->addBlockPropertyConfiguration(');
//
//				$i = 0;
//				foreach ($arguments->getIterator() as $argument) {
//					if ($i ++) {
//						$compiler->raw(', ');
//					}
//
//					$compiler->subcompile($argument);
//				}
//
//				$compiler->raw(");\n echo ");
//			}

			$compiler->raw('$this->env->getExtension(\'supraPage\')->getBlockController()->getPropertyValue(');
            $compiler->subcompile($arguments->getIterator()->current());
			$compiler->raw(');');
		}
	}
}