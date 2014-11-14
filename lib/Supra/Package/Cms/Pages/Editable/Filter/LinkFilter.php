<?php

namespace Supra\Package\Cms\Pages\Editable\Filter;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\ReferencedElement\ReferencedElementUtils;
use Supra\Package\Cms\Entity\ReferencedElement\LinkReferencedElement;
use Supra\Package\Cms\Html\HtmlTag;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

class LinkFilter implements FilterInterface, BlockPropertyAware, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var BlockProperty
	 */
	protected $blockProperty;

	/**
	 * {@inheritDoc}
	 */
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
						$this->container->getDoctrine()->getManager(),
						$this->container->getLocaleManager()->getCurrentLocale()
				);

				// @TODO: what if we failed to obtain the URL?
				$url = ReferencedElementUtils::getLinkReferencedElementUrl(
						$element,
						$this->container->getDoctrine()->getManager(),
						$this->container->getLocaleManager()->getCurrentLocale()
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

	/**
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}
}
