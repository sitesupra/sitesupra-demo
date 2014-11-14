<?php

namespace Supra\Package\Cms\Pages\Editable\Filter;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Package\Cms\Html\HtmlTag;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

class ImageFilter implements FilterInterface, BlockPropertyAware, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public function filter($content)
	{
		if ($this->blockProperty->getMetadata()->offsetExists('image')) {

			$element = $this->blockProperty
					->getMetadata()
					->get('image')
					->getReferencedElement();

			if ($element !== null) {
				/* @var $element ImageReferencedElement */

				if (! $element instanceof ImageReferencedElement) {
					// @TODO: any exception should be thrown probably
					return null;
				}

				$imageId = $element->getImageId();

				$fileStorage = $this->container['cms.file_storage'];
				/* @var $fileStorage \Supra\Package\Cms\FileStorage\FileStorage */
				
				$image = $fileStorage->findImage($imageId);

				if ($image) {

					$tag = new HtmlTag('img');

					$tag->setAttribute('src', $fileStorage->getWebPath($image));

					return $tag;
				}
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
	 * {@inheritDoc}
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}
}
