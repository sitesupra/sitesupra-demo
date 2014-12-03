<?php

namespace Supra\Package\Cms\Pages\Twig;

use Supra\Package\Cms\Pages\BlockController;
use Supra\Package\Cms\Pages\Block\BlockExecutionContext;

/**
 * {{ property('name') }}				to get block property value
 * {{ property('name', 'editable') }}	to get and define block property
 * {{ isEmptyProperty('name') }}		to test property value for emptiness
 */
class PageExtension extends \Twig_Extension
{
	/**
	 * @var BlockExecutionContext
	 */
	private $blockExecutionContext;

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
			new \Twig_SimpleFunction('isPropertyEmpty', null, array('node_class' => 'Supra\Package\Cms\Pages\Twig\BlockPropertyValueTestNode', 'is_safe' => array('html'))),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getGlobals()
	{
		return array(
			'supraBlock' => $this,
		);
	}

	public function getPropertyValue($name, array $options = array())
	{
		if ($this->blockExecutionContext === null) {
			throw new \LogicException('Not in block controller execution context.');
		}

		return $this->blockExecutionContext->controller->getPropertyValue($name, $options);
	}

	public function isPropertyValueEmpty($name)
	{
		if ($this->blockExecutionContext === null) {
			throw new \LogicException('Not in block controller execution context.');
		}

		return $this->blockExecutionContext->controller->isPropertyValueEmpty($name);
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
