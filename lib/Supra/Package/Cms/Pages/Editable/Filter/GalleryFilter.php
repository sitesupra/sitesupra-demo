<?php

namespace Supra\Package\Cms\Pages\Editable\Filter;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;
use Supra\Package\Cms\Pages\Html\ImageTag;

class GalleryFilter implements FilterInterface, BlockPropertyAware, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @param mixed $content irrelevant here
	 * @return HtmlTag[]
	 */
	public function filter($content)
	{
		$tags = array();

		$fileStorage = $this->container['cms.file_storage'];
		/* @var $fileStorage \Supra\Package\Cms\FileStorage\FileStorage */

		foreach ($this->blockProperty->getMetadata() as $metadata) {
			/* @var $metadata \Supra\Package\Cms\Entity\BlockPropertyMetadata */
			
			$element = $metadata->getReferencedElement();

			if (! $element instanceof ImageReferencedElement) {
				continue;
			}

			$imageId = $element->getImageId();

			$image = $fileStorage->findImage($imageId);

			if ($image) {

				$tag = new ImageTag($image, $fileStorage);

				$tags[] = $tag;
			}
		}

		return $tags;
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
