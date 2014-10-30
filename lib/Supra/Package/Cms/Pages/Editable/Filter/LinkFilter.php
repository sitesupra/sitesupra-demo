<?php

namespace Supra\Package\Cms\Pages\Editable\Filter;

use Doctrine\ORM\EntityManager;
use Supra\Core\Locale\LocaleInterface;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\ReferencedElement\ReferencedElementUtils;
use Supra\Package\Cms\Entity\ReferencedElement\LinkReferencedElement;
use Supra\Package\Cms\Html\HtmlTag;

class LinkFilter implements FilterInterface
{
	/**
	 * @var BlockProperty
	 */
	public $blockProperty;

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @var LocaleInterface
	 */
	protected $currentLocale;

	/**
	 * @param EntityManager $entityManager
	 * @param LocaleInterface $currentLocale
	 */
	public function __construct(
			EntityManager $entityManager,
			LocaleInterface $currentLocale
	) {
		$this->entityManager = $entityManager;
		$this->currentLocale = $currentLocale;
	}

	public function filter($content)
	{
		if ($this->blockProperty->getMetadata()->offsetExists('link')) {

			$element = $this->blockProperty
					->getMetadata()
					->get('link')
					->getReferencedElement();

			if ($element !== null) {
				/* @var $element LinkReferencedElement */

				if (! $element instanceof LinkReferencedElement) {
					// @TODO: any exception should be thrown probably
					return null;
				}

				// @TODO: the same code is inside HtmlFilter, should combine somehow.

				$title = ReferencedElementUtils::getLinkReferencedElementTitle(
						$element,
						$this->entityManager,
						$this->currentLocale
				);

				// @TODO: what if we failed to obtain the URL?
				$url = ReferencedElementUtils::getLinkReferencedElementUrl(
						$element,
						$this->entityManager,
						$this->currentLocale
				);

				$tag = new HtmlTag('a', $title ? $title : $url);

				$tag->setAttribute('target', $element->getTarget())
						->setAttribute('title', $title)
						->setAttribute('href', $url)
						->setAttribute('class', $element->getClassName())
				;

				switch ($element->getResource()) {
					case LinkReferencedElement::RESOURCE_FILE:
						$tag->setAttribute('target', '_blank');
						break;
				}

				return $tag;
			}
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setBlockProperty(BlockProperty $blockProperty)
	{
		$this->blockProperty = $blockProperty;
	}
}
