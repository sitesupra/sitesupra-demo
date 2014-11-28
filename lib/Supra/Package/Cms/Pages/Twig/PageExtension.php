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
