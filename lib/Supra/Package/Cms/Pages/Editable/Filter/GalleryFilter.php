<?php

namespace Supra\Package\Cms\Pages\Editable\Filter;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement;
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
	 * @return string
	 */
	public function filter($content, array $options = array())
	{
		$itemTemplate = ! empty($options['itemTemplate']) ? (string) $options['itemTemplate'] : '';
		$wrapperTemplate = ! empty($options['wrapperTemplate']) ? (string) $options['wrapperTemplate'] : '';

		$output = '';

		$fileStorage = $this->container['cms.file_storage'];
		/* @var $fileStorage \Supra\Package\Cms\FileStorage\FileStorage */

		foreach ($this->blockProperty->getMetadata() as $metadata) {
			/* @var $metadata \Supra\Package\Cms\Entity\BlockPropertyMetadata */

			$element = $metadata->getReferencedElement();

			if (! $element instanceof ImageReferencedElement
					|| $element->getSizeName() === null) {

				continue;
			}

			$image = $fileStorage->findImage($element->getImageId());

			if ($image === null) {
				continue;
			}

			$imageWebPath = $fileStorage->getImagePath($image, $element->getSizeName());

			$itemData = array(
				'image' 		=> '<img src="' . $imageWebPath . '" alt="' . $element->getAlternativeText() . '" />',
				'title' 		=> $element->getTitle(),
				'description' 	=> $element->getDescription(),
			);

			$output .= preg_replace_callback(
				'/{{\s*(?:image|title|description)\s*}}/g',
				function ($matches) use ($itemData) {
					return $itemData[$matches[0]];
				},
				$itemTemplate
			);
		}

		return preg_replace('/{{\s*items\s*/', $output, $wrapperTemplate);
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
