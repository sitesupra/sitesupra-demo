<?php

namespace Supra\Package\Cms\Pages\Editable\Filter;

use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

/**
 * Wraps the content with additional, CMS specific, div.
 */
class EditableInlineTextareaFilter implements FilterInterface, BlockPropertyAware
{
	/**
	 * @var BlockProperty 
	 */
	protected $blockProperty;

	public function filter($content, array $options = array())
	{
		$wrap = '<div id="content_%s_%s" class="yui3-content-inline yui3-input-textarea-inline">%s</div>';

		return sprintf(
					$wrap,
					$this->blockProperty->getBlock()->getId(),
					str_replace('.', '_', $this->blockProperty->getHierarchicalName()),
					$content
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
