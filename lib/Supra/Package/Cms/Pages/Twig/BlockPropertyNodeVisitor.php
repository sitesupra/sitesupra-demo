<?php

namespace Supra\Package\Cms\Pages\Twig;

use Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper;
use \Twig_Node_Expression_Constant as ConstantExpression;
use \Twig_Node_Expression_Array as ArrayExpression;

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
	public function enterNode(\Twig_NodeInterface $node, \Twig_Environment $env)
	{
		if ($node instanceof BlockPropertyNode) {

			$arguments = iterator_to_array($node->getNode('arguments'));

			if (count($arguments) > 1) {

				$label = null;
				$defaultValue = null;

				if (! $arguments[0] instanceof ConstantExpression) {
					throw new \RuntimeException('Name definition should be constant expression only.');
				}

				$name = $arguments[0]->getAttribute('value');

				if (! $arguments[1] instanceof ConstantExpression) {
					throw new \RuntimeException('Editable definition should be constant expression only.');
				}

				$editable = $arguments[1]->getAttribute('value');

				if (isset($arguments[2])) {
					if (! $arguments[2] instanceof ConstantExpression) {
						throw new \RuntimeException('Label definition should be constant expression only.');
					}

					$label = $arguments[2]->getAttribute('value');
				}

				if (isset($arguments[3])) {
					if (! $arguments[3] instanceof ConstantExpression
							&& ! $arguments[3] instanceof ArrayExpression) {

						throw new \RuntimeException('Default value definition should be constant expression or constant expression array.');
					}

					if ($arguments[3] instanceof ArrayExpression) {

						$defaultValue = array();

						foreach ($arguments[3]->getKeyValuePairs() as $pair) {

							if (! $pair['key'] instanceof ConstantExpression
									|| ! $pair['value'] instanceof ConstantExpression) {
								throw new \RuntimeException('Default value array definition should consist from constant expressions only.');
							}

							$defaultValue[$pair['key']->getAttribute('value')] = $pair['value']->getAttribute('value');
						}

					} else {
						$defaultValue = $arguments[3]->getAttribute('value');
					}
				}

				$this->propertyMapper->add($name, $editable, $label, $defaultValue);
			}
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
	public function leaveNode(\Twig_NodeInterface $node, \Twig_Environment $env)
	{
		return $node;
	}

}
