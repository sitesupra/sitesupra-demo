<?php

namespace Supra\Package\Cms\Pages\Filter;

use Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Package\Cms\Html\HtmlTagAbstraction;

/**
 * Filters the value to enable Html editing for CMS
 */
class EditableHtmlFilter extends HtmlFilter
{
	/**
	 * Filters the editable content's data, adds Html Div node for CMS.
	 *
	 * @params string $content
	 * @return \Twig_Markup
	 */
	public function filter($content)
	{
		$wrap = '<div id="content_%s_%s" class="yui3-content-inline yui3-input-html-inline-content">%s</div>';
		
		return new \Twig_Markup(
				sprintf(
					$wrap,
					$this->blockProperty->getBlock()->getId(),
					$this->blockProperty->getName(),
					parent::filter($content)
				),
				'UTF-8'
		);
	}

	/**
	 * {@inheritDoc}
	 * Additionally, adds data-* attributes required for CMS editor.
	 */
	protected function parseSupraImage(ImageReferencedElement $imageElement)
	{
		$tag = parent::parseSupraImage($imageElement);

		if ($tag === null) {
			return null;
		}

		if (! $tag instanceof HtmlTagAbstraction) {
			throw new \UnexpectedValueException(sprintf(
					'Expecting HtmlTagAbstraction, [%s] recevied.',
					get_class($tag)
			));
		}

		// @FIXME: get the container!
		$fileStorage = $this->container->getFileStorage();

		$image = $fileStorage->findImage($imageElement->getImageId());
		
		if ($image !== null) {
			
			$exists = $fileStorage->fileExists($image);
			$tag->setAttribute('data-exists', $exists);

			if (! $exists) {
				$tag->setAttribute('width', null);
				$tag->setAttribute('height', null);
				$tag->setAttribute('src', null);
			}
		}

		return $tag;
	}
}
