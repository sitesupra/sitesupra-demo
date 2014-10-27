<?php

namespace Supra\Package\Cms\Pages\Filter;

use Supra\Package\Cms\Entity\BlockProperty;

/**
 * Wraps the content with additional, CMS specific, div.
 */
class EditableInlineTextareaFilter implements FilterInterface
{
	/**
	 * @var BlockProperty 
	 */
	protected $blockProperty;

	public function filter($content)
	{
		$wrap = '<div id="content_%s_%s" class="yui3-content-inline yui3-input-textarea-inline">%s</div>';

		return new \Twig_Markup(
				sprintf(
					$wrap,
					$this->blockProperty->getBlock()->getId(),
					$this->blockProperty->getName(),
					$content
				),
				'UTF-8'
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function setBlockProperty(BlockProperty $blockProperty)
	{
		$this->blockProperty = $blockProperty;
	}
}
