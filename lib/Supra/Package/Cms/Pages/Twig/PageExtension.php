<?php

namespace Supra\Package\Cms\Pages\Twig;

use Supra\Package\Cms\Entity\Abstraction\Localization;
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
			// @TODO: collection()
			// @TODO: set()
			new \Twig_SimpleFunction('property', null, array('node_class' => 'Supra\Package\Cms\Pages\Twig\BlockPropertyNode', 'is_safe' => array('html'))),
			new \Twig_SimpleFunction('isPropertyEmpty', null, array('node_class' => 'Supra\Package\Cms\Pages\Twig\BlockPropertyValueTestNode', 'is_safe' => array('html'))),
			new \Twig_SimpleFunction('placeHolder', null, array('node_class' => 'Supra\Package\Cms\Pages\Twig\PlaceHolderNode', 'is_safe' => array('html'))),
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
			'supraPage'	=> $this
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
				->controller->getPropertyViewValue($name, $options);
	}

	/**
	 * Gets if specified property value is empty.
	 * 
	 * You cannot test the value directly in twig, since in CMS view mode,
	 * properties with inline editable always will have additional wrappers.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function isPropertyValueEmpty($name)
	{
		$value = $this->getBlockExecutionContext()
				->controller->getProperty($name)->getValue();

		return empty($value);
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
	 * @param BlockExecutionContext $context
	 */
	public function setBlockExecutionContext(BlockExecutionContext $context)
	{
		$this->blockExecutionContext = $context;
	}

	/**
	 * @param PageExecutionContext $context
	 */
	public function setPageExecutionContext(PageExecutionContext $context)
	{
		$this->pageExecutionContext = $context;
	}

	/**
	 * @param HtmlTag $tag
	 * @param array $attributes
	 *
	 * @return null|HtmlTag
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
	 * @return Localization
	 */
	public function getPage()
	{
		return $this->getPageExecutionContext()->request->getLocalization();
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
