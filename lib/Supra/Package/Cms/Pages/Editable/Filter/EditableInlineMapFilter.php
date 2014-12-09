<?php

namespace Supra\Package\Cms\Pages\Editable\Filter;

use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Html\HtmlTag;

class EditableInlineMapFilter implements FilterInterface, BlockPropertyAware
{
	/**
	 * @var BlockProperty
	 */
	protected $blockProperty;

	public function filter($content, array $options = array())
	{
		if ($content instanceof HtmlTag) {
			$content->addClass('yui3-content-inline yui3-input-map-inline map')
					->setAttribute('id', sprintf('content_%s_%s', $this->blockProperty->getBlock()->getId(), $this->blockProperty->getName()));
		}

		return $content;
	}

	/**
	 * @param \Supra\Package\Cms\Entity\BlockProperty $blockProperty
	 */
	public function setBlockProperty(BlockProperty $blockProperty)
	{
		$this->blockProperty = $blockProperty;
	}
}
