<?php

namespace Supra\Package\Cms\Pages\Twig;

class BlockPropertyNode extends \Twig_Node_Expression_Function
{
	public function compile(\Twig_Compiler $compiler)
	{
		$arguments = $this->getNode('arguments');

        if (($count = $arguments->count()) > 0) {

			$arguments = iterator_to_array($arguments->getIterator());

			$compiler->raw('$this->env->getExtension(\'supraPage\')->getPropertyValue(\'' . $this->getPropertyNameValue() . "'");

			if ($count === 3) {
				$compiler->raw(',');
				$compiler->subcompile($arguments[2]);
			}

			$compiler->raw(')');
		}
	}

	/**
	 * @return string
	 */
	public function getPropertyNameValue()
	{
		$arguments = iterator_to_array($this->getNode('arguments'));

		if (count($arguments) === 0) {
			throw new \RuntimeException('Property must have at least one argument.');
		}

		if (! $arguments[0] instanceof \Twig_Node_Expression_Constant) {
			throw new \UnexpectedValueException(sprintf(
				'Expecting property definition first argument to be constant expression, [%s] received.',
				get_class($arguments[0])
			));
		}

		return $arguments[0]->getAttribute('value');
	}
}