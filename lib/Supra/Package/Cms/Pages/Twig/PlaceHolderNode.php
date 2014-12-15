<?php

namespace Supra\Package\Cms\Pages\Twig;

class PlaceHolderNode extends \Twig_Node_Expression_Function
{
	public function compile(\Twig_Compiler $compiler)
	{
		$arguments = $this->getNode('arguments');

        if (($count = $arguments->count()) === 1) {

			$arguments = iterator_to_array($arguments->getIterator());

			if ($arguments[0] instanceof \Twig_Node_Expression_Constant) {

				$name = $arguments[0]->getAttribute('value');

				$compiler->raw(sprintf('(isset($context[\'responses\'][\'%1$s\']) ? $context[\'responses\'][\'%1$s\'] : "")', $name));
			}
		}
	}
}