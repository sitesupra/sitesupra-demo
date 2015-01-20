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

			$listConfig = $this->propertyMapper->createPropertyList(
				$node->getPropertyNameValue(),
				$node->getLabelValue(),
				$this->getPropertyConfigForNode($node->getListItemNode())
			);

			$node->setNode('arguments', new \Twig_Node());

			return $listConfig;

		} elseif ($node instanceof BlockPropertySetNode) {

			$setItems = array();

			foreach ($node->getNode('arguments') as $argumentNode) {
				if ($argumentNode instanceof BlockPropertyNode) {
					$config = $this->getPropertyConfigForNode($argumentNode);
					$setItems[$config->name] = $config;
				}
			}

			$setConfig = $this->propertyMapper->createPropertySet(
				$node->getPropertyNameValue(),
				$node->getLabelValue(),
				$setItems
			);

			$node->setNode('arguments', new \Twig_Node());

			return $setConfig;

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

					if (! $pair['key'] instanceof ConstantExpression
						|| !$pair['value'] instanceof ConstantExpression
					) {

						throw new NotConstantExpressionException();
					}

					$editableDefinition[$pair['key']->getAttribute('value')] = $pair['value']->getAttribute(
						'value'
					);
				}
			} elseif ($arguments[1] instanceof ConstantExpression) {
				// @FIXME: dev
				$editableDefinition['type'] = $arguments[1]->getAttribute('value');

			} else {
				throw new NotConstantExpressionException;
			}

			// @FIXME: dev

			if (! isset($editableDefinition['name'])
					&& ! isset($editableDefinition['type'])) {

				throw new \RuntimeException('Editable type is not specified.');
			}

			// @FIXME: dev
			$editableName = isset($editableDefinition['type']) ? $editableDefinition['type'] : $editableDefinition['name'];
			unset($editableDefinition['name']);

			return $this->propertyMapper->createProperty($node->getPropertyNameValue(), $editableName, $editableDefinition);
		}
	}
}
