<?php

namespace Supra\Package\Cms\Pages\Twig;

use \Twig_NodeInterface;
use \Twig_Environment;
use \Twig_Node_Expression_Constant as ConstantExpression;
use \Twig_Node_Expression_Array as ArrayExpression;
use Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper;
use Supra\Package\Cms\Pages\Twig\Exception\NotConstantExpressionException;

/**
 * Visits BlockPropertyNodes in template and collects property definitions.
 */
class BlockPropertyNodeVisitor implements \Twig_NodeVisitorInterface
{
	/**
	 * @var PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @param PropertyMapper $propertyMapper
	 */
	public function __construct(PropertyMapper $propertyMapper)
	{
		$this->propertyMapper = $propertyMapper;
	}

	/**
	 * {@inheritDoc}
	 */
	public function enterNode(Twig_NodeInterface $node, Twig_Environment $env)
	{
		if ($node instanceof BlockPropertyNode) {

			$arguments = iterator_to_array($node->getNode('arguments'));

			if (count($arguments) < 2
					|| ($arguments[1] instanceof ConstantExpression 
						&& $arguments[1]->getAttribute('value') === null)) {

				// no editable defintion, leave
				return $node;
			}

			list($nameExpression, $editableExpression) = $arguments;

			if (! $nameExpression instanceof ConstantExpression) {
				throw new NotConstantExpressionException();
			}

			$name = $nameExpression->getAttribute('value');

			$editableDefinition = array();

			if ($editableExpression instanceof ArrayExpression) {

				foreach ($editableExpression->getKeyValuePairs() as $pair) {

					if (! $pair['key'] instanceof ConstantExpression
							|| ! $pair['value'] instanceof ConstantExpression) {
						
						throw new NotConstantExpressionException();
					}

					$editableDefinition[$pair['key']->getAttribute('value')] = $pair['value']->getAttribute('value');
				}
			} elseif ($editableExpression instanceof ConstantExpression) {

				$editableDefinition['name'] = $editableExpression->getAttribute('value');
			} else {
				throw new NotConstantExpressionException;
			}

			if (empty($editableDefinition['name'])) {
				throw new \RuntimeException('Editable name is not specified.');
			}

			$editableName = $editableDefinition['name'];
			unset($editableDefinition['name']);
			
			$this->propertyMapper->add($name, $editableName, $editableDefinition);
		}

		return $node;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPriority()
	{
		return 0;
	}

	/**
	 * {@inheritDoc}
	 */
	public function leaveNode(Twig_NodeInterface $node, Twig_Environment $env)
	{
		return $node;
	}

}
