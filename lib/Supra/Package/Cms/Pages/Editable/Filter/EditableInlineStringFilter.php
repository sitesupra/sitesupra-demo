<?php

namespace Supra\Package\Cms\Pages\Editable\Filter;

use Supra\Package\Cms\Entity\BlockProperty;

class EditableInlineStringFilter implements FilterInterface
{
	/**
	 * @param string $content
	 * @return \Twig_Markup
	 */
	public function filter($content)
	{
		$wrap = '<div id="content_%s_%s" class="yui3-content-inline yui3-input-string-inline">%s</div>';

		return new \Twig_Markup(
				sprintf(
					$wrap,
					$this->blockProperty->getBlock()->getId(),
					$this->blockProperty->getName(),
					htmlspecialchars($content, ENT_QUOTES, 'UTF-8')
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
