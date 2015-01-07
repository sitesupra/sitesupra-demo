<?php

namespace Supra\Package\Cms\Pages\Twig;

use Supra\Package\Cms\Pages\Block\Config\PropertyConfig;
use \Twig_NodeInterface;
use \Twig_Environment;
use \Twig_Node_Expression_Constant as ConstantExpression;
use \Twig_Node_Expression_Array as ArrayExpression;
use Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper;
use Supra\Package\Cms\Pages\Twig\Exception\NotConstantExpressionException;

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
			$config = $this->getPropertyConfigForNode($node);

			if ($config !== null) {
				$this->propertyMapper->addProperty($config);
			}
		}

		return $node;
	}

	/**
	 * {@inheritDoc}
	 */
	public function leaveNode(Twig_NodeInterface $node, Twig_Environment $env)
	{
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
	 * @param BlockPropertyNode $node
	 * @return PropertyConfig
	 */
	private function getPropertyConfigForNode(BlockPropertyNode $node)
	{
		if ($node instanceof BlockPropertyListNode) {

			$name = $node->getPropertyName();

			$listItemNode = $node->getListItemNode();

			$node->getNode('arguments')->setNode(0, null);

			$config = $this->getPropertyConfigForNode($listItemNode);

			return $this->propertyMapper->createPropertyList($name, $config);

		} elseif ($node instanceof BlockPropertySetNode) {

			$setItems = array();

			$name = $node->getPropertyName();

			foreach ($node->getNode('arguments') as $i => $argumentNode) {

				$config = $this->getPropertyConfigForNode($argumentNode);

				if ($config === null) {
					throw new \RuntimeException("Failed to create config for [#{$i}] argument in set.");
				}

				$setItems[$config->name] = $config;

				$node->getNode('arguments')->setNode($i, null);
			}

			return $this->propertyMapper->createPropertySet($name, $setItems);

		} elseif ($node instanceof BlockPropertyNode) {

			$arguments = iterator_to_array($node->getNode('arguments'));

			if (count($arguments) < 2
				|| ($arguments[1] instanceof ConstantExpression
					&& $arguments[1]->getAttribute('value') === null)
			) {
				// ignore
				return null;
			}

			$editableDefinition = array();

			if ($arguments[1] instanceof ArrayExpression) {

				foreach ($arguments[1]->getKeyValuePairs() as $pair) {

					if (!$pair['key'] instanceof ConstantExpression
						|| !$pair['value'] instanceof ConstantExpression
					) {

						throw new NotConstantExpressionException();
					}

					$editableDefinition[$pair['key']->getAttribute('value')] = $pair['value']->getAttribute(
						'value'
					);
				}
			} elseif ($arguments[1] instanceof ConstantExpression) {
				$editableDefinition['name'] = $arguments[1]->getAttribute('value');

			} else {
				throw new NotConstantExpressionException;
			}

			if (empty($editableDefinition['name'])) {
				throw new \RuntimeException('Editable name is not specified.');
			}

			$editableName = $editableDefinition['name'];
			unset($editableDefinition['name']);

			return $this->propertyMapper->createProperty($node->getPropertyName(), $editableName, $editableDefinition);
		}
	}
}
