<?php

namespace Supra\Package\Cms\Pages\Twig;

use Supra\Package\Cms\Pages\Block\Config\PropertyConfig;
use Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper;
use Twig_NodeInterface;
use Twig_Environment;
use Twig_Node_Expression_Array as ArrayNode;
use Twig_Node_Expression_Constant as ConstantNode;

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
		if ($node instanceof AbstractPropertyFunctionNode) {
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
	 * @param AbstractPropertyFunctionNode $node
	 * @return null|PropertyConfig
	 */
	private function getPropertyConfigForNode(AbstractPropertyFunctionNode $node)
	{
		$name = $node->getNameOptionValue();

		$options = $node->getOptions();

		if ($node instanceof BlockPropertyListNode) {

			if (! isset($options['item'])) {
				throw new \RuntimeException(sprintf(
					'Missing item definition for property list [%s] at line [%u].',
					$name,
					$node->getLine()
				));
			}

			if (! $options['item'] instanceof AbstractPropertyFunctionNode) {
				throw new \RuntimeException(sprintf(
					'Item defined for property list [%s] at line [%u] is not a property.',
					$name,
					$node->getLine()
				));
			}

			$listConfig = $this->propertyMapper->createPropertyList(
				$name,
				$node->getLabelOptionValue(),
				$this->getPropertyConfigForNode($options['item'])
			);

			$node->setNode('arguments', new \Twig_Node());

			return $listConfig;

		} elseif ($node instanceof BlockPropertySetNode) {

			$setItems = array();

			if (! isset($options['items']) || ! $options['items'] instanceof ArrayNode) {
				throw new \RuntimeException(sprintf(
					'Missing items definition for property set [%s] at line [%u].',
					$name,
					$node->getLine()
				));
			}

			$items = $options['items'];
			/* @var $items ArrayNode */

			foreach ($items->getKeyValuePairs() as $i => $pair) {

				$itemNode = $pair['value'];

				if (!$itemNode instanceof AbstractPropertyFunctionNode) {
					throw new \RuntimeException(
						sprintf(
							'Item [%i] defined for property set [%s] at line [%u] is not a property.',
							$name,
							$node->getLine()
						)
					);
				}

				$config = $this->getPropertyConfigForNode($itemNode);

				if ($config !== null) {
					$setItems[$config->name] = $config;
				}
			}

			$setConfig = $this->propertyMapper->createPropertySet(
				$name,
				$node->getLabelOptionValue(),
				$setItems
			);

			$node->setNode('arguments', new \Twig_Node());

			return $setConfig;

		} elseif ($node instanceof BlockPropertyNode) {

			$optionsArgumentNode = $node->getOptionsArgumentNode();

			if (! $optionsArgumentNode instanceof ArrayNode) {
				return null;
			}

			$constantOptions = $this->collectConstantValues($optionsArgumentNode);

			if (empty($constantOptions['type'])) {
				return null;
			}

			return $this->propertyMapper->createProperty($name, $constantOptions['type'], $constantOptions);

		} else {

			throw new \UnexpectedValueException();
		}
	}

	/**
	 * @param ArrayNode $node
	 * @return array
	 */
	private function collectConstantValues(ArrayNode $node)
	{
		$values = array();

		foreach ($node->getKeyValuePairs() as $pair) {
			if (! $pair['key'] instanceof ConstantNode) {
				continue;
			}

			$value = null;

			if ($pair['value'] instanceof ConstantNode) {
				$value = $pair['value']->getAttribute('value');
			} elseif ($pair['value'] instanceof ArrayNode) {
				$value = $this->collectConstantValues($pair['value']);
			}

			$values[$pair['key']->getAttribute('value')] = $value;
		}

		return $values;
	}
}
