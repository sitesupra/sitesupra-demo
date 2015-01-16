<?php

namespace Supra\Package\Cms\Pages\Editable\Filter;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Package\Cms\Html\HtmlTag;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

class GalleryFilter implements FilterInterface, BlockPropertyAware, ContainerAware
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
	 * @return \Supra\Package\Cms\Pages\Gallery\GalleryImage[]
	 */
	public function filter($content, array $options = array())
	{
		$output = '';

		$fileStorage = $this->container['cms.file_storage'];
		/* @var $fileStorage \Supra\Package\Cms\FileStorage\FileStorage */

		// @TODO: something not so hardcore

		$itemTemplate = isset($options['item_template'])
			? (string) $options['item_template']
			: '';

		$width = isset($options['width']) ? (int) $options['width'] : 300;
		$height = isset($options['height']) ? (int) $options['height'] : null;

		$crop = isset($options['crop']) ? $options['crop'] : false;

		foreach ($this->blockProperty->getMetadata() as $metadata) {
			/* @var $metadata \Supra\Package\Cms\Entity\BlockPropertyMetadata */

			$element = $metadata->getReferencedElement();

			if (! $element instanceof ImageReferencedElement) {
				continue;
			}

			$imageId = $element->getImageId();

			$image = $fileStorage->findImage($imageId);

			if ($image === null) {
				continue;
			}

			$imageWebPath = $fileStorage->createResizedImage($image, $width, $height, $crop === true);

			$image = new HtmlTag('img');
			$image->setAttribute('src', $imageWebPath);

			$output .= str_replace(
				array('{{ title }}', '{{ image }}'),
				array('Title', $image),
				$itemTemplate
			);
		}

		return $output;
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
