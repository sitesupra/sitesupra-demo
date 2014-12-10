<?php

namespace Supra\Package\Cms\Pages\Twig;

use Supra\Package\Cms\Pages\Block\BlockExecutionContext;
use Supra\Package\Cms\Pages\PageExecutionContext;
use Supra\Package\Cms\Pages\Request\PageRequestEdit;
use Supra\Package\Cms\Html\HtmlTag;

class PageExtension extends \Twig_Extension
{
	/**
	 * @var BlockExecutionContext
	 */
	private $blockExecutionContext;

	/**
	 * @var PageExecutionContext;
	 */
	private $pageExecutionContext;

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
	public function getFilters()
	{
		return array(
			new \Twig_SimpleFilter('decorate', array($this, 'decorateHtmlTag'), array('is_safe' => array('html'))),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getGlobals()
	{
		return array(
			'supraBlock' => $this,	// @TODO: remove?
			'supraPage'	=> $this,	// @TODO: remove?
			'supra'		=> $this,	// @TODO: leave?
		);
	}

	/**
	 * @param string $name
	 * @param array $options
	 * @return mixed
	 */
	public function getPropertyValue($name, array $options = array())
	{
		return $this->getBlockExecutionContext()
				->controller->getPropertyValue($name, $options);
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function isPropertyValueEmpty($name)
	{
		return $this->getBlockExecutionContext()
				->controller->isPropertyValueEmpty($name);
	}

	/**
	 * @return bool
	 */
	public function isCmsRequest()
	{
		if ($this->blockExecutionContext) {
			return $this->blockExecutionContext
					->request instanceof PageRequestEdit;
		}
		
		return $this->getPageExecutionContext()
				->request instanceof PageRequestEdit;
	}

	/**
	 * @internal
	 * @param BlockExecutionContext $context
	 */
	public function setBlockExecutionContext(BlockExecutionContext $context)
	{
		$this->blockExecutionContext = $context;
	}

	/**
	 * @internal
	 * @param PageExecutionContext $context
	 */
	public function setPageExecutionContext(PageExecutionContext $context)
	{
		$this->pageExecutionContext = $context;
	}

	/**
	 * @param HtmlTag $tag
	 * @param array $attributes
	 */
	public function decorateHtmlTag($tag, array $attributes)
	{
		if (! $tag instanceof HtmlTag) {
			return null;
		}

		foreach ($attributes as $name => $value) {
			$tag->setAttribute($name, $value);
		}

		return $tag;
	}

	/**
	 * @return PageExecutionContext
	 * @throws \LogicException
	 */
	private function getPageExecutionContext()
	{
		if ($this->pageExecutionContext === null) {
			throw new \LogicException('Not in page controller execution context.');
		}
		return $this->pageExecutionContext;
	}

	/**
	 * @return BlockExecutionContext
	 * @throws \LogicException
	 */
	private function getBlockExecutionContext()
	{
		if ($this->blockExecutionContext === null) {
			throw new \LogicException('Not in block controller execution context.');
		}
		return $this->blockExecutionContext;
	}
}
