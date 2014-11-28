<?php

namespace Supra\Package\Cms\Pages\Twig;

use Supra\Package\Cms\Pages\BlockController;
use Supra\Package\Cms\Pages\Block\BlockPropertyConfiguration;
use Supra\Package\Cms\Editable\Editable;
use Supra\Package\Cms\Pages\Block\BlockExecutionContext;

/**
 * {{ property('name') }}				to get block property value
 * {{ property('name', 'editable') }}	to get and define block property
 * {{ isEmptyProperty('name') }}		to test property value for emptiness
 */
class PageExtension extends \Twig_Extension
{
	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return 'supraPage';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('property', null, array('node_class' => 'Supra\Package\Cms\Pages\Twig\BlockPropertyNode', 'is_safe' => array('html'))),
			new \Twig_SimpleFunction('isEmptyProperty', null, array('node_class' => 'Supra\Package\Cms\Pages\Twig\BlockPropertyValueTestNode')),
		);
	}

	/**
	 * Injects configuration discovered at runtime.
	 *
	 * @param string $name Property name
	 * @param string $editable Editable name
	 * @param string $label
	 * @param mixed $defaultValue
	 * @throws \RuntimeException
	 */
	public function addBlockPropertyConfiguration($name, $editable, $label = null, $defaultValue = null)
	{
		$config = $this->getBlockController()->getConfiguration();

		if (! $config->isPropertyAutoDiscoverEnabled()) {
			throw new \RuntimeException('Property auto-discovering is disabled.');
		}

		$config->addProperty(new BlockPropertyConfiguration(
				$name,
				Editable::getEditable($editable),
				$label,
				$defaultValue
		));
	}

	/**
	 * @return BlockController
	 * @throws \RuntimeException
	 */
	public function getBlockController()
	{
		if ($this->blockExecutionContext === null) {
			throw new \LogicException('Not in block controller execution context.');
		}

		return $this->blockExecutionContext->controller;
	}

	/**
	 * @param BlockExecutionContext $context
	 */
	public function setBlockExecutionContext(BlockExecutionContext $context)
	{
		$this->blockExecutionContext = $context;
	}

//	/**
//	 * @param PageExecutionContext $context
//	 */
//	public function setPageExecutionContext(PageExecutionContext $context)
//	{
//		$this->pageExecutionContext = $context;
//	}
}
