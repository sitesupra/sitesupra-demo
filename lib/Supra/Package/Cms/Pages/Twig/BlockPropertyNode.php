<?php

namespace Supra\Package\Cms\Pages\Twig;

use Twig_Node_Expression_Array as ArrayNode;
use Twig_Node_Expression_Constant as ConstantNode;

class BlockPropertyNode extends AbstractPropertyFunctionNode
{
	/**
	 * {@inheritDoc}
	 */
	public function getType()
	{
		return 'property';
	}

	/**
	 * {@inheritDoc}
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$arguments = $this->getNode('arguments');

        if (($count = $arguments->count()) > 0) {

			$arguments = iterator_to_array($arguments->getIterator());

			$compiler->raw('$this->env->getExtension(\'supraPage\')->getPropertyValue(\'' . $this->getNameOptionValue() . "'");

			if (! empty($arguments[1])) {
				$compiler->raw(',');
				$compiler->subcompile($arguments[1]);
			}

			$compiler->raw(')');
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getOptions()
	{
		$node = $this->getOptionsArgumentNode();

		if ($node instanceof ConstantNode) {
			return array('name' => $node);
		} elseif ($node instanceof ArrayNode) {
			return $this->nodeToArray($node);
		}

		throw new \UnexpectedValueException('Expecting only string and array nodes.');
	}

//	/**
//	 * @throws \RuntimeException
//	 */
//	public function validate()
//	{
//		$arguments = $this->getNode('arguments');
//
//		if ($arguments->count() > 2) {
//			throw new \RuntimeException('Property definition contains more arguments that expected.');
//		}
//
//		$propertyOptions = $this->getPropertyOptions();
//
//		if (empty($propertyOptions['name'])) {
//			throw new \RuntimeException('Property name cannot be empty.');
//		}
//
//		$filterOptionsNode = $this->getFilterOptionsNode();
//
//		if ($filterOptionsNode !== null
//			&& ! $filterOptionsNode instanceof ArrayNode) {
//			throw new \RuntimeException('Filter options should be an array.');
//		}
//	}
//
//	/**
//	 * @return \Twig_Node|null
//	 */
//	private function getFilterOptionsNode()
//	{
//		return $this->getNode('arguments')->hasNode(1)
//			? $this->getNode('arguments')->getNode(1) : null;
//	}
}